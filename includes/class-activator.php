<?php
/**
 * Activation and deactivation routines.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the review table and scheduled events.
 */
final class Activator {

	/**
	 * Creates storage and schedules synchronization.
	 *
	 * @param bool $network_wide Whether WordPress activated the plugin network-wide.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		self::create_table();

		if ( ! wp_next_scheduled( 'tarw_daily_sync' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'tarw_daily_sync' );
		}

		update_option( 'tarw_db_version', TARW_VERSION, false );

		if ( ! $network_wide ) {
			update_option( 'tarw_activation_redirect', 1, false );
		}

		flush_rewrite_rules();
	}

	/**
	 * Clears plugin scheduling without deleting user data.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'tarw_daily_sync' );
		flush_rewrite_rules();
	}

	/**
	 * Creates or upgrades the reviews table.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = $wpdb->prefix . 'tarw_reviews';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source_id bigint(20) unsigned NOT NULL,
			external_id varchar(191) NOT NULL,
			reviewer_name varchar(255) NOT NULL DEFAULT '',
			reviewer_photo_url text NOT NULL,
			reviewer_profile_url text NOT NULL,
			rating tinyint(1) unsigned NOT NULL DEFAULT 0,
			review_text longtext NOT NULL,
			review_url text NOT NULL,
			create_time datetime DEFAULT NULL,
			update_time datetime DEFAULT NULL,
			privacy_approved tinyint(1) unsigned NOT NULL DEFAULT 0,
			privacy_excerpt longtext NOT NULL,
			privacy_approved_at datetime DEFAULT NULL,
			synced_at datetime NOT NULL,
			expires_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY source_external (source_id, external_id),
			KEY source_rating (source_id, rating),
			KEY expires_at (expires_at),
			KEY privacy_approved (privacy_approved)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}
