<?php
/**
 * Plugin Name:       TomAwesome Review Widgets
 * Plugin URI:        https://github.com/drumhead39/tomawesome-review-widgets
 * Description:       Create review widgets using your own Google Business Profile connection or Places API key.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            TomAwesome
 * Author URI:        https://profiles.wordpress.org/tomawesome/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tomawesome-review-widgets
 *
 * @package TomAwesomeReviewWidgets
 */

defined( 'ABSPATH' ) || exit;

define( 'TARW_VERSION', '1.0.0' );
define( 'TARW_FILE', __FILE__ );
define( 'TARW_DIR', plugin_dir_path( __FILE__ ) );
define( 'TARW_URL', plugin_dir_url( __FILE__ ) );

require_once TARW_DIR . 'includes/class-activator.php';
require_once TARW_DIR . 'includes/class-crypto.php';
require_once TARW_DIR . 'includes/class-settings.php';
require_once TARW_DIR . 'includes/class-oauth.php';
require_once TARW_DIR . 'includes/class-business-profile-client.php';
require_once TARW_DIR . 'includes/class-places-client.php';
require_once TARW_DIR . 'includes/class-review-repository.php';
require_once TARW_DIR . 'includes/class-privacy.php';
require_once TARW_DIR . 'includes/class-widget-display.php';
require_once TARW_DIR . 'includes/class-sync-service.php';
require_once TARW_DIR . 'includes/class-shortcode.php';
require_once TARW_DIR . 'includes/class-admin.php';
require_once TARW_DIR . 'includes/class-plugin.php';

register_activation_hook( TARW_FILE, array( 'TomAwesome_Review_Widgets\\Activator', 'activate' ) );
register_deactivation_hook( TARW_FILE, array( 'TomAwesome_Review_Widgets\\Activator', 'deactivate' ) );

/**
 * Starts the plugin after all active plugins have loaded.
 *
 * @return TomAwesome_Review_Widgets\Plugin
 */
function tarw_plugin() {
	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new TomAwesome_Review_Widgets\Plugin();
		$plugin->run();
	}

	return $plugin;
}

add_action( 'plugins_loaded', 'tarw_plugin' );
