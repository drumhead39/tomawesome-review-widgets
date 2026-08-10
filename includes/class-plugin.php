<?php
/**
 * Main plugin coordinator.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin services to WordPress.
 */
final class Plugin {

	/**
	 * Prevents duplicate hook registration.
	 *
	 * @var bool
	 */
	private $running = false;

	/**
	 * Registers plugin hooks.
	 *
	 * @return void
	 */
	public function run() {
		if ( $this->running ) {
			return;
		}

		$this->running = true;

		$settings   = new Settings();
		$repository = new Review_Repository();
		$oauth      = new OAuth( $settings );
		$sync       = new Sync_Service( $settings, $repository, $oauth );
		$shortcode  = new Shortcode( $repository );
		$admin      = new Admin( $settings, $repository, $oauth, $sync );

		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'tarw_daily_sync', array( $sync, 'sync_all' ) );
		add_action( 'admin_init', array( $this, 'maybe_upgrade' ) );
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );

		$shortcode->register();
		$admin->register();
	}

	/**
	 * Registers private admin content types.
	 *
	 * @return void
	 */
	public function register_post_types() {
		$capabilities = array(
			'edit_post'              => 'manage_options',
			'read_post'              => 'manage_options',
			'delete_post'            => 'manage_options',
			'edit_posts'             => 'manage_options',
			'edit_others_posts'      => 'manage_options',
			'publish_posts'          => 'manage_options',
			'read_private_posts'     => 'manage_options',
			'delete_posts'           => 'manage_options',
			'delete_private_posts'   => 'manage_options',
			'delete_published_posts' => 'manage_options',
			'delete_others_posts'    => 'manage_options',
			'edit_private_posts'     => 'manage_options',
			'edit_published_posts'   => 'manage_options',
			'create_posts'           => 'manage_options',
		);

		register_post_type(
			'tarw_widget',
			array(
				'labels'           => array(
					'name'          => __( 'Review Widgets', 'tomawesome-review-widgets' ),
					'singular_name' => __( 'Review Widget', 'tomawesome-review-widgets' ),
					'add_new_item'  => __( 'Add Review Widget', 'tomawesome-review-widgets' ),
					'edit_item'     => __( 'Edit Review Widget', 'tomawesome-review-widgets' ),
				),
				'public'           => false,
				'show_ui'          => true,
				'show_in_menu'     => true,
				'menu_icon'        => 'dashicons-star-filled',
				'menu_position'    => 58,
				'supports'         => array( 'title' ),
				'capabilities'     => $capabilities,
				'map_meta_cap'     => false,
				'show_in_rest'     => false,
				'can_export'       => true,
				'delete_with_user' => false,
			)
		);

		register_post_type(
			'tarw_source',
			array(
				'labels'           => array(
					'name'          => __( 'Review Sources', 'tomawesome-review-widgets' ),
					'singular_name' => __( 'Review Source', 'tomawesome-review-widgets' ),
					'add_new_item'  => __( 'Add Review Source', 'tomawesome-review-widgets' ),
					'edit_item'     => __( 'Edit Review Source', 'tomawesome-review-widgets' ),
				),
				'public'           => false,
				'show_ui'          => true,
				'show_in_menu'     => 'edit.php?post_type=tarw_widget',
				'supports'         => array( 'title' ),
				'capabilities'     => $capabilities,
				'map_meta_cap'     => false,
				'show_in_rest'     => false,
				'can_export'       => true,
				'delete_with_user' => false,
			)
		);
	}

	/**
	 * Runs database upgrades when needed.
	 *
	 * @return void
	 */
	public function maybe_upgrade() {
		if ( TARW_VERSION !== get_option( 'tarw_db_version' ) ) {
			Activator::create_table();
			update_option( 'tarw_db_version', TARW_VERSION, false );
		}
	}

	/**
	 * Suggests disclosure text in WordPress' Privacy Policy Guide.
	 *
	 * @return void
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = '<p>' . esc_html__( 'This site uses TomAwesome Review Widgets to retrieve and display public review content from Google services. Depending on widget settings, a visitor\'s browser may request a reviewer profile image from Google. Google may receive the visitor\'s IP address and browser information. Healthcare Privacy Mode prevents profile images and reviewer links from loading, but does not by itself establish compliance with any law.', 'tomawesome-review-widgets' ) . '</p>';

		wp_add_privacy_policy_content(
			__( 'TomAwesome Review Widgets', 'tomawesome-review-widgets' ),
			wp_kses_post( wpautop( $content ) )
		);
	}
}
