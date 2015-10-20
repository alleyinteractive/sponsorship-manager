# Sponsorship Manager

This should be a good start towards getting everything you need in order to get sponsorship info into the templates. Stuff to do...

* `scaffold dir ./scaffold --theme=./` and requiring `functions.php` is pretty hacky. Would be nice to get the scaffolder working more intuitively in the context of a plugin.
* WordPress allows you to register a taxonomy with `null` as the `$object_type`, but this feature is mising in the scaffolder which is why you see `"object_types" : ["faking-it"]`
* Simplify post type support (see below)
* I've stubbed out functions in `template-tags.php` that should get filled in, these will enable you to get the data you'll need in the templates
* Make sure the "Hide from..." checkboxes are working correctly to hide posts from various queries

### post type support

You'll need a way to apply this plugin to post types of your choosing. That means two things

1. `register_taxonomy_for_object_type( 'sponsorship_campaign', $post_type );`
2. `add_action( 'fm_post_' . $post_type, 'sponsorship_manager_fm_sponsorship_campaign_post_fields' );`
