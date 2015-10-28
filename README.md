# Sponsorship Manager

This plugin is designed for clients wishing to integrate WordPress and DFP to manage sponsored content (native ad) campaigns.

**Fieldmanager is required.**

## About sponsorship campaigns

Sponsorship campaigns are organized like this:

```
Sponsored Post (object in any WP post_type)
|-- Sponsorship Campaign (term in sponsorship_campaign hierarchical taxonomy)
    |-- Parent Campaign (may exist as parent term of primary campaign)
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

## Usage in templates

You can use `sponsorship_post_is_sponsored( $post )` to determine if a post has a sponsor or not. `$post` is optional, and can be an ID or WP_Post object.

### Campaign data

Most data you'll need is associated with the campaign selected for the post. To fetch it by key, you can use either `Sponsorship_Manager_Campaign::get()` or `Sponsorship_Manager_Post_Template::get_campaign()`. See (examples)[#examples] below.

Each of these methods takes 3 arguments:

1. Required key
1. Optional boolean. Defaults to `false`. If `true`, data will be fetched from the _parent campaign_, if one exists.
1. Optional image size. If your key refers to an image, use this to specify the size to fetch using `wp_get_attachment_image_src()`. Defaults to `'full'`.

Here are the keys:

| Key | Description |
|-----|-------------|
| WP Term Fields | Regular [term fields](https://codex.wordpress.org/Function_Reference/get_term_by#Return_Values) can be fetched by key |
| `description` | The standard WP term description field is **replaced by `richdescription`**, although this can be turned off. See [Filters](#filters). |
| `logo` | Returns array using `wp_get_attachment_image_src()`, remember to pass `$size` as the third parameter to the getter method |
| `featured-image` | Returns array using `wp_get_attachment_image_src()`, remember to pass `$size` as the third parameter to the getter method |
| `external-url` | External URL associated with the Campaign, e.g. the sponsor's website. |
| `hub` | Internal URL for the campaign, i.e. the term archive link |
| `tagline` | Defaults to "Sponsored by" but could be something like "Presented by", "Powered by", etc |
| `richdescription` | Returns contents of a `Fieldmanager_RichTextArea` field; overrides `description` by default. |

When fetching a key, the method looks first among the standard WP term fields (`description` is (the exception)[#sponsorship_manager_override_campaign_description]) then in metadata.

### Tracking pixels

Each sponsored post can have its own DFP tracking pixel set in post meta. This enables content to be served by WordPress but still logged in the ad server. The client would normally be responsible for inserting the pixel URL.

To trigger the pixel impression, use `Sponsorship_Manager_Post_Template::insert_tracking_pixel()`. This renders a script tag with no dependencies that requests the image after replacing the cache-busting parameter (`c`) with a new, unique integer.

The same thing works for campaigns. You can use `Sponsorship_Manager_Campaign::insert_tracking_pixel()` to log an impression of the campaign hub (landing page).

## Filters

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

### sponsorship_manager_dev_pixel_url

If you return a string here, it will override the custom field value. This can be used to prevent impressions from being logged on dev environments.

| Param | Type | Description |
|-------|------|-------------|
| `$new_url` | `bool|string` | Defaults to `false`. |
| `$old_url` | `string` | Original pixel URL |
| `$param` | `string` | URL parameter that will be replaced before triggering the pixel, e.g. `'c'` for DFP. |

### sponsorship_manager_hide_archiveless

Allows you to hide or unhide sponsored posts where the "Hide from queries" option is checked. This filter can be applied for specific `WP_Query` cases.

See `Sponsorship_Manager_Archiveless::posts_where()` for more info.

| Param | Type | Description |
|-------|------|-------------|
| `$hide` | `bool` | Return `true` to hide archiveless posts for this `$query`, or `false` to show them. |
| `$query` | `WP_Query` | Current query object |


## Examples

### In a post template like `single.php`
```
<?php if ( sponsorship_post_is_sponsored() ) : $sponsorship = new Sponsorship_Manager_Post_Template(); ?>
	<div class="sponsorship">
		<h3><?php echo esc_html( $sponsorship->get_campaign( 'name' ) ); ?></h3>
		<p><?php echo wp_kses_post( $sponsorship->get_campaign( 'richdescription' ) ); ?></p>
		<img src="<?php echo esc_url( $sponsorship->get_campaign( 'logo')[0] ); ?>" />
		<?php $sponsorship->insert_tracking_pixel(); ?>
	</div>
<?php endif; ?>
```

### In a term archive template like a generic `taxonomy.php`
```
<?php if ( is_tax( 'sponsorship_campaign' ) ) : $campaign = new Sponsorship_Manager_Campaign( get_queried_object() ); ?>
	<div class="sponsorship">
		<h3><?php echo esc_html( $campaign->get( 'name' ) ); ?></h3>
		<p><?php echo wp_kses_post( $campaign->get( 'richdescription' ) ); ?></p>
		<img src="<?php echo esc_url( $campaign->get( 'logo')[0] ); ?>" />
		<?php $campaign->insert_tracking_pixel(); ?>
	</div>
<?php endif; ?>
```


