# Sponsorship Manager

This plugin is designed for sites wishing to integrate WordPress and DFP to manage sponsored content (native ad) campaigns.

**Fieldmanager is required.**

## About sponsorship campaigns

Sponsorship campaigns are organized like this:

```
Sponsored Post (object in any WP post_type)
|-- Sponsorship Campaign (term in sponsorship_campaign hierarchical taxonomy)
    |-- Parent Campaign (optional, may exist as parent term of primary campaign)
```

A post can only be associated with _one_ term in the `sponsorship_campaign` taxonomy, and that term may have 0 or 1 ancestors, i.e. no more than 2 levels of hierarchy.

Note that Sponsorship Campaigns should not represent _sponsors_, just _campaigns_. A taxonomy for _sponsors_ will be added later, and mapped differently to data in DFP.

So when you are setting up a campaign term that has a parent campaign, this would be a correct hierarchy:

```
MasterCard City Guides
|-- London
```

And this would be an incorrect hierarchy:

```
MasterCard
|-- City Guides
   |-- London
```

(also, you would not be able to select `London` when editing a post because of the hierarchy depth limit)

## Checking if the plugin is installed

The plugin gets set up at the `after_setup_theme` [action](#https://codex.wordpress.org/Plugin_API/Action_Reference/after_setup_theme), which fires _after_ the theme's `functions.php` is loaded. If you need to check for the plugin before then, use

```
if ( function_exists( 'sponsorship_manager_setup' ) ) {
	// do stuff
```

## Usage in templates

There are two classes that are used in templating:

1. `Sponsorship_Manager_Campaign` represents the sponsorship campaign (i.e. the term in the `sponsorship_campaign` taxonomy)
2. `Sponsorship_Manager_Post_Template` represents a piece of content (i.e. a `WP_Post` object) that is part of a sponsorship campaign.

**In general, if you are templating a representation of the campaign (e.g. a landing page for the sponsorship), you'll want to instantiate `Sponsorship_Manager_Campaign` and use its methods.**

**If you are templating a single sponsored article (e.g. a `single.php` template or a thumbnail/headline in a homepage grid), you'll want to instantiate `Sponsorship_Manager_Post_Template` and use its methods.**

You can use `sponsorship_post_is_sponsored( $post )` to determine if a post has a sponsor or not. `$post` is optional, and can be an ID or WP_Post object.

### Campaign data

Most of the data you'll need is associated with the campaign term selected for the post. To fetch it by key, you can use either `Sponsorship_Manager_Campaign::get()` or `Sponsorship_Manager_Post_Template::get_campaign()`. See [examples](#examples) below.

Each of these methods takes 3 arguments:

| Arg | Type | Description |
|-----|------|-------------|
| `$key` | `string` | Required key to fetch |
| `$parent` | `boolean` | Optional. Defaults to `false`. If `true`, data will be fetched from the _parent campaign_, if one exists. |
| `$img_size` | `string` | Optional. If `$key` refers to an image, specify the size to fetch using `wp_get_attachment_image_src()`. Defaults to `'full'`. |

Here are the keys:

| Key | Description |
|-----|-------------|
| WP Term Fields | Regular [term fields](https://codex.wordpress.org/Function_Reference/get_term_by#Return_Values) can be fetched by key |
| `description` | The standard WP term description field is **replaced by `richdescription`**, although this can be turned off. See [Filters](#filters). |
| `logo-primary` | Returns array using `wp_get_attachment_image_src()`, remember to pass `$size` as the third parameter to the getter method |
| `logo-secondary` | Returns array using `wp_get_attachment_image_src()`, remember to pass `$size` as the third parameter to the getter method |
| `featured-image` | Returns array using `wp_get_attachment_image_src()`, remember to pass `$size` as the third parameter to the getter method |
| `external-url` | External URL associated with the Campaign, e.g. the sponsor's website. |
| `hub` | Internal URL for the campaign, defaults to the term archive link but can be customized |
| `tagline` | Defaults to "Sponsored by" but could be something like "Presented by", "Powered by", etc |
| `richdescription` | Returns contents of a `Fieldmanager_RichTextArea` field; overrides `description` by default. |

When fetching a key, the method looks first among the standard WP term fields (`description` is [the exception](#sponsorship_manager_override_campaign_description) ) then in metadata.

### Tracking pixels

Each sponsored post can have its own DFP tracking pixel. The `Sponsorship_Tracking_Pixel` class generates these automatically, although individual posts can override with a custom field. The tracking pixel enables content to be served by WordPress but still logged in the ad server.

#### Configuring the tracking pixel

In your theme, you'll need to a filter like this:

```
add_filter( 'sponsorship_manager_tracking_pixel_config', function() {
	return array(
		'network' => '1234',
		'sponsorship_campaign' => array(
			'unit' => 'Campaign_Landing_Page',
			'size' => '1x1',
			'key' => 'wp_campaign_id',
		),
		'post' => array(
			'unit' => 'Sponsored_Post',
			'size' => '1x1',
			'key' => 'post_id',
		),
	);
} );
```

In the above array, `'network'` should be the numeric ID for the publisher on DFP. The other top-level array keys refer to the `sponsorship_campaign` taxonomy and _all_ post types where the plugin is enabled. Within each of those arrays, the ad unit name, size, and key (targeting parameter) are required. The plugin automatically uses the `$term->term_id` or `$post->ID` of the campaign or post as the value of the key. [More info here](https://support.google.com/dfp_premium/answer/2623168?rd=1); an ad ops person will recognize all of this if you ask them to provide this info.

So the archive page of a term in the `sponsorship_campaign` taxonomy with `$term->term_id === 5678` would have a tracking pixel URL like:
```
http://pubads.g.doubleclick.net/gampad/ad?iu=/1234/Campaign_Landing_Page&c=1446166093157185&sz=1x1&t=wp_campaign_id%3D5678
```

And a sponsored post with `$post->ID === 5678` would have a tracking pixel URL like:
```
http://pubads.g.doubleclick.net/gampad/ad?iu=/1234/Sponsored_Post&c=1446166093157185&sz=1x1&t=post_id%3D5678
```

When editing a sponsored post, the Sponsorship Campaign meta box will display the ad unit name, creative size, and key-value data required to set up impression tracking with a DFP line item.

#### Triggering the tracking pixel

To trigger the pixel impression, use `Sponsorship_Manager_Post_Template::insert_tracking_pixel()`. This renders a script tag with no dependencies that requests the image after replacing the cache-busting parameter (`c`) with a new, unique integer.

Note that, by default, pixel impressions are not counted for logged in users, although the pixel URL is console logged for debugging. You can change this behavior with a [filter](#sponsorship_manager_tracking_pixel_when_logged_in)

The same thing works for campaigns. You can use `Sponsorship_Manager_Campaign::insert_tracking_pixel()` to log an impression of the campaign hub (landing page).

**Note the distinction between `Sponsorship_Manager_Post_Template::insert_tracking_pixel()` for individual posts and `Sponsorship_Manager_Campaign::insert_tracking_pixel()` for campaign landing pages.**

### Ad slots

#### Overview

Let's say you're building a Recent Posts module where you always want to show four posts:

1. The most recent post
1. The second-most recent post
1. A sponsored post
1. The third-most recent post

The ad slots feature allows you to determine which sponsored posts are eligible to display in position 3 in the module, and will distribute views evenly across the eligible posts.

To bypass page caching, it uses client-side logic to pick the sponsored post to show and an AJAX request to retrieve it. And since the AJAX request is same-origin, it also bypasses ad blockers.

#### Setup

1. Pass an array of slot names to the `sponsorship_manager_ad_slots_list` filter
  1. When editing a sponsorable post, there will be a _Sponsorship Manager Ad Slots_ checkboxes array in the Sponsorship Campaign meta box. Use these checkboxes to target the post to one or more ad slots.
1. Optionally, use the `sponsorship_manager_ad_slots_query_config` filter to pass [WP_Query](https://codex.wordpress.org/Class_Reference/WP_Query) arguments to refine which targeted posts would be eligible to show in the ad slot
1. Use the `sponsorship_manager_slot_template_Sidebar_Recent_Module` filters to [create markup for your ad slot](#sponsorship_manager_slot_template_)
1. Render the ad slot by one of two methods
  1. Call `sponsorship_manager_ad_slot( 'Sidebar_Recent_Module' )` in a template; this function can also accept `WP_Query` args to refine eligibility
  1. Use the shortcode `[sponsorship-ad-slot slot="Sidebar_Recent_Module"]` inside _any_ post, even if it is not sponsored. Additional shortcode attributes that refine what posts are eligible to appear in the ad slot:
      1. `campaign=123` or `campaign="my-campaign": the slug or ID of a term in the `sponsorship_campaign` taxonomy
      1. `post=4567`: the ID of a specific sponsored post

## Filters

### sponsorship_manager_tracking_pixel_config

See [above](#configuring-the-tracking-pixel).

### sponsorship_manager_override_campaign_description

If `true`, WordPress default term description field is hidden and replaced by `richdescription`. This also means that `Sponsorship_Manager_Campaign::get( 'description' )` is converted to `Sponsorship_Manager_Campaign::get( 'richdescription' )`.

| Param | Type | Description |
|-------|------|-------------|
| `$override` | `bool` | Defaults to `true`. |

### sponsorship_manager_campaign_display_meta

Filter campaign metadata

| Param | Type | Description |
|-------|------|-------------|
| `$metadata` | `array` | Metadata array for campaign |
| `$term` | `object` | Term object for campaign |
| `$is_parent` | `bool` | Whether the term is the parent campaign |

### sponsorship_manager_enabled_post_types

Array of post types that can be sponsored.

| Param | Type | Description |
|-------|------|-------------|
| `$post_types` | `array` | Defaults to `array( 'post' )`. |

### sponsorship_manager_tracking_pixel_when_logged_in

If `true`, plugin will trigger pixel impressions for logged-in users.

| Param | Type | Description |
|-------|------|-------------|
| `$do_pixel` | `bool` | Defaults to `false`. |

### sponsorship_manager_override_pixel_url

If you return a string here, it will override the DFP pixel URL. This can be used to prevent impressions from being logged on dev environments.

| Param | Type | Description |
|-------|------|-------------|
| `$new_url` | `bool|string` | Defaults to `false`. |
| `$old_url` | `string` | Original pixel URL |
| `$param` | `string` | URL parameter that will be replaced before triggering the pixel, e.g. `'c'` for DFP. |

### sponsorship_manager_tracking_pixel_when_logged_in

If `false`, tracking pixels will not be shown for logged-in users but the URL will be console logged for debugging.

| Param | Type | Description |
|-------|------|-------------|
| `$show` | `bool` | Defaults to `false`. |

### sponsorship_manager_archiveless_post_status_args

Filters the arguments passed to `register_post_status()` for the `archiveless` status

| Param | Type | Description |
|-------|------|-------------|
| `$args` | `array` | See `Sponsorship_Manager_Archiveless::register_post_status()` |


### sponsorship_manager_hide_archiveless

This filter can be applied for specific `WP_Query` cases. Allows you to hide or unhide sponsored posts where the "Hide from queries" option is checked.

See `Sponsorship_Manager_Archiveless::posts_where()` for more info.

| Param | Type | Description |
|-------|------|-------------|
| `$hide` | `bool` | Return `true` to hide archiveless posts for this `$query`, or `false` to show them. |
| `$query` | `WP_Query` | Current query object |

### sponsorship_manager_post_fields

Applied to child fields in the `sponsorship-info` Fieldmanager Group for posts in enabled post types

| Param | Type | Description |
|-------|------|-------------|
| `$fields` | `array` | See `inc/fields.php` |

### sponsorship_manager_term_fields

Applied to child fields in the `sponsorship-campaign-display` Fieldmanager Group for terms in the `sponsorship_campaign` taxonomy

| Param | Type | Description |
|-------|------|-------------|
| `$fields` | `array` | See `inc/fields.php` |


### sponsorship_manager_skip_ad_slot_transients

If set to `true`, the queries that build the lists of eligible posts for each ad slot will run every time instead of caching in a transient.

| Param | Type | Description |
|-------|------|-------------|
| `$skip_transient` | `bool` | Defaults to `false` |

### sponsorship_manager_ad_slots_query_config

Used to refine eligbility for an ad slot, so that post targeted to an ad slot might be excluded in specific situations. For instance, if you wanted to make sure on a category archive page that the `Sidebar_Recent_Module` slot only showed sponsored posts with that specific category, you could use this filter.

| Param | Type | Description |
|-------|------|-------------|
| `$query_config` | `array` | Specify key-value pairs of ad slot name and WP_Query args. Defaults to empty array. |

### sponsorship_manager_ad_slot_params

Not sure how this is different from `sponsorship_manager_ad_slots_query_config`, need to investigate...

### sponsorship_manager_slot_posts

Modify the list of eligible posts for an ad slot before outputting.

| Param | Type | Description |
|-------|------|-------------|
| `$posts` | `array` | List of post IDs |
| `$slot_name` | `string` | Ad slot being rendered |
| `$args` | `array` | WP_Query args, but _only_ the ones passed to `Sponsorship_Manager_Ad_Slots::render_ad_slot()`, not args set with `sponsorship_manager_ad_slots_query_config`  |

### sponsorship_manager_slot_template_*

Returns the HTML output for an [ad slot](#ad-slots). Example: for the `Sidebar_Recent_Module` slot, the template filter would be `sponsorship_manager_slot_template_Sidebar_Recent_Module`

| Param | Type | Description |
|-------|------|-------------|
| `$slot_name` | `string` | Name of ad slot |
| `$post` | `WP_Post` | Post object being rendered in the slot |

### sponsorship_manager_ad_slots_list

Simple array of strings for each ad slot

| Param | Type | Description |
|-------|------|-------------|
| `$fields` | `array` | See `inc/fields.php` |

## Examples

### In a post template like `single.php`
```
<?php if ( sponsorship_post_is_sponsored() ) :
	$sponsorship = new Sponsorship_Manager_Post_Template(); ?>
	<div class="sponsorship">
		<h3><?php echo esc_html( $sponsorship->get_campaign( 'name' ) ); ?></h3>
		<p><?php echo wp_kses_post( $sponsorship->get_campaign( 'richdescription' ) ); ?></p>
		<img src="<?php echo esc_url( $sponsorship->get_campaign( 'logo-primary')[0] ); ?>" />
		<?php $sponsorship->insert_tracking_pixel(); ?>
	</div>
<?php endif; ?>
```

### In a term archive template like a generic `taxonomy.php`
```
<?php if ( is_tax( 'sponsorship_campaign' ) ) :
	$campaign = new Sponsorship_Manager_Campaign( get_queried_object() ); ?>
	<div class="sponsorship">
		<h3><?php echo esc_html( $campaign->get( 'name' ) ); ?></h3>
		<p><?php echo wp_kses_post( $campaign->get( 'richdescription' ) ); ?></p>
		<img src="<?php echo esc_url( $campaign->get( 'logo-primary')[0] ); ?>" />
		<?php $campaign->insert_tracking_pixel(); ?>
	</div>
<?php endif; ?>
```

### If you are using a custom campaign landing page other than the term archive

For instance, if you have the campaign landing page at a `page` like `mysite.com/cool-sponsor` instead of the default term archive link `mysite.com/sponsor/cool-sponsor`.

```
<?php if ( sponsorship_post_is_sponsored() ) :
	$sponsorship = new Sponsorship_Manager_Post_Template();
	$campaign = $sponsorship->get_campaign_object(); ?>
	<div class="sponsorship">
		<h3><?php echo esc_html( $campaign->get( 'name' ) ); ?></h3>
		<p><?php echo wp_kses_post( $campaign->get( 'richdescription' ) ); ?></p>
		<img src="<?php echo esc_url( $campaign->get( 'logo-primary')[0] ); ?>" />
		<?php $campaign->insert_tracking_pixel(); ?>
	</div>
<?php endif; ?>
```


