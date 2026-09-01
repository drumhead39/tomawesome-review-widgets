<?php
/**
 * Optional destructive uninstall routine.
 *
 * @package TomAwesomeReviewWidgets
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$tarw_settings = get_option( 'tarw_settings', array() );
if ( empty( $tarw_settings['delete_on_uninstall'] ) ) {
	return;
}

global $wpdb;

$tarw_post_ids = get_posts(
	array(
		'post_type'      => array( 'tarw_widget', 'tarw_source' ),
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);

foreach ( $tarw_post_ids as $tarw_post_id ) {
	wp_delete_post( $tarw_post_id, true );
}

$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- The plugin's custom table must be removed directly during an administrator-approved destructive uninstall.
	$wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'tarw_reviews' ) // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Only removes the plugin-owned table after the explicit delete-on-uninstall opt-in.
);

delete_option( 'tarw_settings' );
delete_option( 'tarw_db_version' );
delete_option( 'tarw_activation_redirect' );
wp_clear_scheduled_hook( 'tarw_daily_sync' );

$tarw_state_transients = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall must find all plugin-owned OAuth state transients; the result is not reusable.
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( '_transient_tarw_oauth_state_' ) . '%'
	)
);
foreach ( $tarw_state_transients as $tarw_transient_option ) {
	delete_option( $tarw_transient_option );
	delete_option( str_replace( '_transient_', '_transient_timeout_', $tarw_transient_option ) );
}
