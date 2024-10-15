<?php
/**
 * Plugin Name:     Disney Practical Plugin V1
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     A plugin to show or hide the author and add custom post meta.
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     disney-practical-plugin-v1
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Disney_Practical_Plugin_V1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Add settings page to the admin menu.
 */
function sha_add_settings_page() {
	add_options_page( 'Show/Hide Author', 'Show/Hide Author', 'manage_options', 'sha-settings', 'sha_render_settings_page' );
}
add_action( 'admin_menu', 'sha_add_settings_page' );

/**
 * Render the settings page in the admin.
 */
function sha_render_settings_page() {
	?>
	<div class="wrap">
		<h1>Show/Hide Author Settings</h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'sha-settings-group' );
			do_settings_sections( 'sha-settings-group' );
			?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Hide Author on Posts</th>
					<td><input type="checkbox" name="sha_hide_author" value="1" <?php checked( 1, get_option( 'sha_hide_author' ), true ); ?> /></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Register the setting so it can be saved.
 */
function sha_register_settings() {
	register_setting( 'sha-settings-group', 'sha_hide_author' );
}
add_action( 'admin_init', 'sha_register_settings' );

/**
 * Enqueue inline debugging script to output console log based on the 'sha_hide_author' option value.
 */
function sha_enqueue_inline_script() {
	$sha_hide_author = get_option( 'sha_hide_author', 0 ); // Default to 0 if option is not set.

	// Register a dummy script to add inline JS to.
	wp_register_script( 'sha-inline-js', '' );

	// Enqueue the script.
	wp_enqueue_script( 'sha-inline-js' );

	// Pass the PHP value to JavaScript.
	wp_localize_script(
		'sha-inline-js',
		'shaOptions',
		array(
			'hideAuthor' => $sha_hide_author,
		)
	);

	// Add the inline script.
	wp_add_inline_script(
		'sha-inline-js',
		"
		if ( shaOptions.hideAuthor == 1 ) {
			console.log('author off');
		} else {
			console.log('author on');
		}
	"
	);
}
add_action( 'wp_enqueue_scripts', 'sha_enqueue_inline_script' );

/**
 * Remove the entire post-meta template part.
 *
 * @param string $block_content The block content.
 * @param array  $block The block data.
 * @return string The modified block content.
 */
function sha_remove_post_meta_template_part( $block_content, $block ) {
	// Check if the block being rendered is the post-meta template part.
	if ( isset( $block['blockName'] ) && 'core/template-part' === $block['blockName'] && isset( $block['attrs']['slug'] ) && 'post-meta' === $block['attrs']['slug'] ) {
		return ''; // Remove the block entirely by returning an empty string.
	}

	// Return the original block content if the conditions are not met.
	return $block_content;
}


/**
 * Add a new custom post-meta block group to replace the old one, excluding the author.
 *
 * @param string $block_content The block content.
 * @param array  $block The block data.
 * @return string The modified block content with the new post meta.
 */
function sha_add_custom_post_meta_in_place( $block_content, $block ) {
	$sha_hide_author = get_option( 'sha_hide_author', 0 ); // Get toggle value.

	// Check if the block being rendered is the post-meta template part.
	if ( isset( $block['blockName'] ) && 'core/template-part' === $block['blockName'] && isset( $block['attrs']['slug'] ) && 'post-meta' === $block['attrs']['slug'] ) {
		// Build the new post-meta section, excluding the author if toggled off.
		$new_post_meta = '
            <div class="wp-block-template-part">
                <div class="wp-block-group has-global-padding is-layout-constrained wp-block-group-is-layout-constrained">
                    <div class="wp-block-group is-content-justification-left is-layout-flex wp-container-core-group-is-layout-6 wp-block-group-is-layout-flex">
                        <div class="wp-block-post-date">
                            <time datetime="' . get_the_date( DATE_W3C ) . '">
                                <a href="' . get_permalink() . '">' . get_the_date( __( 'M d, Y' ) ) . '</a>
                            </time>
                        </div>
                        ' . ( '1' === $sha_hide_author ? '' : '' ) . ' <!-- Author omitted -->
                        <div class="taxonomy-category wp-block-post-terms">
                            <span class="wp-block-post-terms__prefix">in </span>' . get_the_category_list( ', ' ) . '
                        </div>
                    </div>
                </div>
            </div>
        ';

		// Return the custom post meta to replace the default one.
		return $new_post_meta;
	}

	return $block_content;
}


/**
 * Conditionally run the functions to remove and replace the post meta template part.
 */
function sha_maybe_replace_post_meta() {
	$sha_hide_author = get_option( 'sha_hide_author', 0 );

	if ( '1' === $sha_hide_author ) {
		add_filter( 'render_block', 'sha_remove_post_meta_template_part', 10, 2 );
		add_filter( 'render_block', 'sha_add_custom_post_meta_in_place', 10, 2 );
	}
}
add_action( 'wp', 'sha_maybe_replace_post_meta' );
