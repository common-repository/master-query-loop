<?php

if ( ! defined( 'MQL_VIEW_COUNT' ) ) {
	define('MQL_VIEW_COUNT', '_mql_view_count');
}

/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */

/**
 * custom option and settings
 */
function mql_settings_init() {
	// Register a new setting for "master-query-loop" page.
	register_setting( 'master-query-loop', 'mql_options' );

	// Register a new section in the "master-query-loop" page.
	add_settings_section(
		'mql_section_popular_posts',
		__( 'Popular Posts', 'master-query-loop' ), 'mql_section_popular_posts_callback',
		'master-query-loop'
	);

	// Register a new field in the "mql_section_popular_posts" section, inside the "master-query-loop" page.
	add_settings_field(
		'mql_field_post_view_count_enable', // As of WP 4.6 this value is used only internally.
		                        // Use $args' label_for to populate the id inside the callback.
			__( 'Enable Post View Count', 'master-query-loop' ),
		'mql_field_post_view_count_enable_cb',
		'master-query-loop',
		'mql_section_popular_posts',
		array(
			'label_for'         => 'mql_field_post_view_count_enable',
			'class'             => 'mql_row',
			'mql_custom_data' => 'custom',
		)
	);
}

/**
 * Register our mql_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'mql_settings_init' );


/**
 * Custom option and settings:
 *  - callback functions
 */


/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function mql_section_popular_posts_callback( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Essential settings for popular posts.', 'master-query-loop' ); ?></p>
	<?php
}

/**
 * Pill field callbakc function.
 *
 * WordPress has magic interaction with the following keys: label_for, class.
 * - the "label_for" key value is used for the "for" attribute of the <label>.
 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
 * Note: you can add custom key value pairs to be used inside your callbacks.
 *
 * @param array $args
 */
function mql_field_post_view_count_enable_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$options = get_option( 'mql_options' );
	?>

    <input 
        type="checkbox"
        <?php echo isset( $options[ $args['label_for'] ] ) ? ( checked( $options[ $args['label_for'] ], 'on', false ) ) : ( '' ); ?>
        name="mql_options[<?php echo esc_attr( $args['label_for'] ); ?>]" 
        id="<?php echo esc_attr( $args['label_for'] ); ?>"
    >
    <label for="mql_options[<?php echo esc_attr( $args['label_for'] ); ?>]">
        <?php esc_html_e( 'Turn on counting post views.', 'master-query-loop' ); ?>
    </label><br>

	<p class="description">
		<?php esc_html_e( 'This information will be saved in the meta_key called: ', 'master-query-loop' ); ?>
		<b><?php esc_html_e( MQL_VIEW_COUNT, 'master-query-loop' ); ?></b>
	</p>

	<?php
}

/**
 * Add the top level menu page.
 */
function mql_options_page() {
    // Add below `tools` menu
    add_management_page(
        'Master Query Loop',
		'Master Query Loop',
		'manage_options',
		'master-query-loop',
		'mql_options_page_html'
    );
}


/**
 * Register our mql_options_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'mql_options_page' );


/**
 * Top level menu callback function
 */
function mql_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// add error/update messages

	// check if the user have submitted the settings
	// WordPress will add the "settings-updated" $_GET parameter to the url
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		add_settings_error( 'mql_messages', 'mql_message', __( 'Settings Saved', 'master-query-loop' ), 'updated' );
	}

	// show error/update messages
	settings_errors( 'mql_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			// output security fields for the registered setting "master-query-loop"
			settings_fields( 'master-query-loop' );
			// output setting sections and their fields
			// (sections are registered for "master-query-loop", each field is registered to a specific section)
			do_settings_sections( 'master-query-loop' );
			// output save settings button
			submit_button( 'Save Settings' );
			?>
		</form>
	</div>
	<?php
}