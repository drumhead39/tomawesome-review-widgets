<?php
/**
 * WordPress administrator interface.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Manages sources, widget settings, Google connection, and privacy review.
 */
final class Admin {

	/**
	 * Plugin settings service.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Review storage service.
	 *
	 * @var Review_Repository
	 */
	private $repository;

	/**
	 * Google OAuth service.
	 *
	 * @var OAuth
	 */
	private $oauth;

	/**
	 * Review synchronization service.
	 *
	 * @var Sync_Service
	 */
	private $sync;

	/**
	 * Constructor.
	 *
	 * @param Settings          $settings Settings service.
	 * @param Review_Repository $repository Repository service.
	 * @param OAuth             $oauth OAuth service.
	 * @param Sync_Service      $sync Synchronization service.
	 */
	public function __construct( Settings $settings, Review_Repository $repository, OAuth $oauth, Sync_Service $sync ) {
		$this->settings   = $settings;
		$this->repository = $repository;
		$this->oauth      = $oauth;
		$this->sync       = $sync;
	}

	/**
	 * Registers all administrator hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_tarw_widget', array( $this, 'save_widget' ) );
		add_action( 'save_post_tarw_source', array( $this, 'save_source' ) );
		add_action( 'admin_menu', array( $this, 'add_submenus' ) );
		add_action( 'admin_init', array( $this, 'maybe_activation_redirect' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'notices' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( TARW_FILE ), array( $this, 'plugin_action_links' ) );

		add_action( 'admin_post_tarw_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_tarw_connect_google', array( $this, 'connect_google' ) );
		add_action( 'admin_post_tarw_oauth_callback', array( $this, 'oauth_callback' ) );
		add_action( 'admin_post_tarw_disconnect_google', array( $this, 'disconnect_google' ) );
		add_action( 'admin_post_tarw_add_location', array( $this, 'add_location' ) );
		add_action( 'admin_post_tarw_sync_source', array( $this, 'sync_source' ) );
		add_action( 'admin_post_tarw_save_privacy_review', array( $this, 'save_privacy_review' ) );

		add_filter( 'manage_tarw_widget_posts_columns', array( $this, 'widget_columns' ) );
		add_action( 'manage_tarw_widget_posts_custom_column', array( $this, 'widget_column_content' ), 10, 2 );
		add_filter( 'manage_tarw_source_posts_columns', array( $this, 'source_columns' ) );
		add_action( 'manage_tarw_source_posts_custom_column', array( $this, 'source_column_content' ), 10, 2 );
	}

	/**
	 * Adds widget and source configuration boxes.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'tarw-widget-settings',
			__( 'Widget Configuration', 'tomawesome-review-widgets' ),
			array( $this, 'render_widget_box' ),
			'tarw_widget',
			'normal',
			'high'
		);
		add_meta_box(
			'tarw-widget-shortcode',
			__( 'Shortcode', 'tomawesome-review-widgets' ),
			array( $this, 'render_shortcode_box' ),
			'tarw_widget',
			'side',
			'high'
		);
		add_meta_box(
			'tarw-source-settings',
			__( 'Google Review Source', 'tomawesome-review-widgets' ),
			array( $this, 'render_source_box' ),
			'tarw_source',
			'normal',
			'high'
		);
	}

	/**
	 * Adds plugin administration pages.
	 *
	 * @return void
	 */
	public function add_submenus() {
		add_submenu_page(
			'edit.php?post_type=tarw_widget',
			__( 'Getting Started', 'tomawesome-review-widgets' ),
			__( 'Getting Started', 'tomawesome-review-widgets' ),
			'manage_options',
			'tarw-getting-started',
			array( $this, 'render_getting_started' )
		);
		add_submenu_page(
			'edit.php?post_type=tarw_widget',
			__( 'Review Library', 'tomawesome-review-widgets' ),
			__( 'Review Library', 'tomawesome-review-widgets' ),
			'manage_options',
			'tarw-review-library',
			array( $this, 'render_review_library' )
		);
		add_submenu_page(
			'edit.php?post_type=tarw_widget',
			__( 'Google Connection', 'tomawesome-review-widgets' ),
			__( 'Google Connection', 'tomawesome-review-widgets' ),
			'manage_options',
			'tarw-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Sends an administrator to onboarding immediately after plugin activation.
	 *
	 * WordPress redirects immediately after the activation hook, so activation
	 * stores a one-time flag that is handled on the following administrator load.
	 *
	 * @return void
	 */
	public function maybe_activation_redirect() {
		if ( ! get_option( 'tarw_activation_redirect' ) ) {
			return;
		}

		delete_option( 'tarw_activation_redirect' );

		if (
			! current_user_can( 'manage_options' )
			|| wp_doing_ajax()
			|| is_network_admin()
			|| isset( $_GET['activate-multi'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only redirect guard.
		) {
			return;
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=tarw_widget&page=tarw-getting-started' ) );
		exit;
	}

	/**
	 * Adds direct onboarding and connection links on the Plugins screen.
	 *
	 * @param string[] $links Existing plugin action links.
	 * @return string[]
	 */
	public function plugin_action_links( $links ) {
		$getting_started = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'edit.php?post_type=tarw_widget&page=tarw-getting-started' ) ),
			esc_html__( 'Getting Started', 'tomawesome-review-widgets' )
		);
		$connection      = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'edit.php?post_type=tarw_widget&page=tarw-settings' ) ),
			esc_html__( 'Google Connection', 'tomawesome-review-widgets' )
		);

		array_unshift( $links, $getting_started, $connection );
		return $links;
	}

	/**
	 * Loads plugin-only admin styling.
	 *
	 * @param string $hook_suffix Current admin screen hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$is_plugin_screen = in_array( $screen->post_type, array( 'tarw_widget', 'tarw_source' ), true )
			|| false !== strpos( $hook_suffix, 'tarw-' );
		if ( ! $is_plugin_screen ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'tarw-admin', TARW_URL . 'assets/css/admin.css', array( 'wp-color-picker' ), TARW_VERSION );
		wp_enqueue_script( 'tarw-admin', TARW_URL . 'assets/js/admin.js', array( 'wp-color-picker' ), TARW_VERSION, true );
	}

	/**
	 * Renders the complete widget settings form.
	 *
	 * @param \WP_Post $post Current widget.
	 * @return void
	 */
	public function render_widget_box( $post ) {
		wp_nonce_field( 'tarw_save_widget', 'tarw_widget_nonce' );
		$settings = $this->widget_settings( $post->ID );
		$style    = wp_parse_args( is_array( $settings['style'] ) ? $settings['style'] : array(), Widget_Display::style_defaults() );
		$sources  = get_posts(
			array(
				'post_type'      => 'tarw_source',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<div class="tarw-admin-grid">
			<p>
				<label for="tarw-source-id"><strong><?php esc_html_e( 'Review source', 'tomawesome-review-widgets' ); ?></strong></label>
				<select id="tarw-source-id" name="tarw_widget[source_id]" required>
					<option value="0"><?php esc_html_e( 'Select a source', 'tomawesome-review-widgets' ); ?></option>
					<?php foreach ( $sources as $source ) : ?>
						<option value="<?php echo esc_attr( $source->ID ); ?>" <?php selected( $settings['source_id'], $source->ID ); ?>><?php echo esc_html( $source->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="tarw-public-heading"><strong><?php esc_html_e( 'Public heading', 'tomawesome-review-widgets' ); ?></strong></label>
				<input class="widefat" id="tarw-public-heading" name="tarw_widget[public_heading]" type="text" value="<?php echo esc_attr( $settings['public_heading'] ); ?>" maxlength="200" placeholder="<?php esc_attr_e( 'Uses the business name from Google when blank', 'tomawesome-review-widgets' ); ?>">
				<span class="description"><?php esc_html_e( 'Optional. This appears in the business rating summary. The internal review-source title is never shown publicly.', 'tomawesome-review-widgets' ); ?></span>
			</p>
			<p>
				<label for="tarw-layout"><strong><?php esc_html_e( 'Layout', 'tomawesome-review-widgets' ); ?></strong></label>
				<select id="tarw-layout" name="tarw_widget[layout]">
					<?php
					foreach ( array(
						'grid'     => __( 'Grid', 'tomawesome-review-widgets' ),
						'list'     => __( 'List', 'tomawesome-review-widgets' ),
						'carousel' => __( 'Carousel', 'tomawesome-review-widgets' ),
						'featured' => __( 'Single featured review', 'tomawesome-review-widgets' ),
					) as $value => $label ) :
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['layout'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="tarw-limit"><strong><?php esc_html_e( 'Reviews displayed', 'tomawesome-review-widgets' ); ?></strong></label>
				<input id="tarw-limit" name="tarw_widget[limit]" type="number" min="1" max="50" value="<?php echo esc_attr( $settings['limit'] ); ?>">
			</p>
			<p>
				<label for="tarw-min-rating"><strong><?php esc_html_e( 'Minimum rating', 'tomawesome-review-widgets' ); ?></strong></label>
				<select id="tarw-min-rating" name="tarw_widget[min_rating]">
					<?php for ( $star = 1; $star <= 5; $star++ ) : ?>
						<option value="<?php echo esc_attr( $star ); ?>" <?php selected( $settings['min_rating'], $star ); ?>><?php echo esc_html( $star ); ?></option>
					<?php endfor; ?>
				</select>
			</p>
			<p>
				<label for="tarw-sort"><strong><?php esc_html_e( 'Order', 'tomawesome-review-widgets' ); ?></strong></label>
				<select id="tarw-sort" name="tarw_widget[sort]">
					<?php
					foreach ( array(
						'newest'  => __( 'Newest first', 'tomawesome-review-widgets' ),
						'oldest'  => __( 'Oldest first', 'tomawesome-review-widgets' ),
						'highest' => __( 'Highest rated first', 'tomawesome-review-widgets' ),
						'random'  => __( 'Random', 'tomawesome-review-widgets' ),
					) as $value => $label ) :
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['sort'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="tarw-max-chars"><strong><?php esc_html_e( 'Collapsed text length', 'tomawesome-review-widgets' ); ?></strong></label>
				<input id="tarw-max-chars" name="tarw_widget[max_chars]" type="number" min="0" max="2000" value="<?php echo esc_attr( $settings['max_chars'] ); ?>">
				<span class="description"><?php esc_html_e( 'Use 0 to show full text.', 'tomawesome-review-widgets' ); ?></span>
			</p>
		</div>

		<h3><?php esc_html_e( 'Responsive columns', 'tomawesome-review-widgets' ); ?></h3>
		<div class="tarw-admin-grid tarw-admin-grid-3">
			<?php
			foreach ( array(
				'columns_desktop' => __( 'Desktop', 'tomawesome-review-widgets' ),
				'columns_tablet'  => __( 'Tablet', 'tomawesome-review-widgets' ),
				'columns_mobile'  => __( 'Mobile', 'tomawesome-review-widgets' ),
			) as $key => $label ) :
				?>
				<p><label><strong><?php echo esc_html( $label ); ?></strong><input name="tarw_widget[<?php echo esc_attr( $key ); ?>]" type="number" min="1" max="4" value="<?php echo esc_attr( $settings[ $key ] ); ?>"></label></p>
			<?php endforeach; ?>
		</div>

		<h3><?php esc_html_e( 'Display options', 'tomawesome-review-widgets' ); ?></h3>
		<div class="tarw-checks">
			<?php
			foreach ( array(
				'text_only'     => __( 'Require written review text', 'tomawesome-review-widgets' ),
				'show_avatar'   => __( 'Show reviewer photo', 'tomawesome-review-widgets' ),
				'show_date'     => __( 'Show review date', 'tomawesome-review-widgets' ),
				'show_summary'  => __( 'Show business rating summary', 'tomawesome-review-widgets' ),
				'show_read_all' => __( 'Show “Read all reviews” link', 'tomawesome-review-widgets' ),
				'show_leave'    => __( 'Show “Leave a review” link', 'tomawesome-review-widgets' ),
			) as $key => $label ) :
				?>
				<label><input type="checkbox" name="tarw_widget[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?>> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php esc_html_e( 'Google Places sources always display every available reviewer photo, name, and profile link plus an individual “View this review on Google Maps” link. These required attributions override the optional reviewer-photo setting.', 'tomawesome-review-widgets' ); ?></p>
		<p class="description"><?php esc_html_e( 'Places widgets also show a short selection/filter notice beside the Google Maps attribution in the footer. The notice and other Places-only compliance elements disappear automatically when this widget uses a managed Business Profile source.', 'tomawesome-review-widgets' ); ?></p>

		<div class="tarw-privacy-panel">
			<label><input id="tarw-privacy-mode" type="checkbox" name="tarw_widget[privacy_mode]" value="1" <?php checked( ! empty( $settings['privacy_mode'] ) ); ?>> <strong><?php esc_html_e( 'Healthcare Privacy Mode (HIPAA-conscious)', 'tomawesome-review-widgets' ); ?></strong></label>
			<p><?php esc_html_e( 'Shows only reviews that an administrator has separately approved in the Review Library and supplied with privacy-reviewed display copy. It forcibly hides reviewer names, photos, profile links, exact dates, and owner responses. This is a technical safeguard—not legal advice, certification, or a guarantee of HIPAA compliance.', 'tomawesome-review-widgets' ); ?></p>
			<p><strong><?php esc_html_e( 'Not available for Google Places sources:', 'tomawesome-review-widgets' ); ?></strong> <?php esc_html_e( 'Google Maps requires Places reviews to retain author attribution and individual source links. The plugin automatically turns this mode off when a Places source is selected.', 'tomawesome-review-widgets' ); ?></p>
		</div>

		<div class="tarw-style-panel">
			<h3><?php esc_html_e( 'Basic styling', 'tomawesome-review-widgets' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Customize this widget without writing CSS. Leave any field blank to use the plugin or theme default.', 'tomawesome-review-widgets' ); ?></p>

			<h4><?php esc_html_e( 'Colors', 'tomawesome-review-widgets' ); ?></h4>
			<div class="tarw-admin-grid tarw-style-grid">
				<?php
				foreach ( array(
					'widget_background' => __( 'Widget background', 'tomawesome-review-widgets' ),
					'widget_text'       => __( 'Widget text', 'tomawesome-review-widgets' ),
					'accent'            => __( 'Links and buttons', 'tomawesome-review-widgets' ),
					'stars'             => __( 'Review stars', 'tomawesome-review-widgets' ),
					'widget_border'     => __( 'Widget border', 'tomawesome-review-widgets' ),
					'card_background'   => __( 'Review card background', 'tomawesome-review-widgets' ),
					'card_border'       => __( 'Review card border', 'tomawesome-review-widgets' ),
				) as $key => $label ) :
					$field_id = 'tarw-style-' . str_replace( '_', '-', $key );
					?>
					<p>
						<label for="<?php echo esc_attr( $field_id ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label>
						<input class="tarw-color-field" id="<?php echo esc_attr( $field_id ); ?>" name="tarw_widget[style][<?php echo esc_attr( $key ); ?>]" type="text" value="<?php echo esc_attr( $style[ $key ] ); ?>" data-default-color="">
					</p>
				<?php endforeach; ?>
			</div>

			<h4><?php esc_html_e( 'Spacing and borders', 'tomawesome-review-widgets' ); ?></h4>
			<div class="tarw-admin-grid tarw-admin-grid-3 tarw-style-grid">
				<?php
				foreach ( array(
					'widget_margin'        => array( __( 'Outer margin (top and bottom)', 'tomawesome-review-widgets' ), 300 ),
					'widget_padding'       => array( __( 'Widget padding', 'tomawesome-review-widgets' ), 200 ),
					'widget_border_width'  => array( __( 'Widget border width', 'tomawesome-review-widgets' ), 20 ),
					'widget_border_radius' => array( __( 'Widget corner radius', 'tomawesome-review-widgets' ), 100 ),
					'card_padding'         => array( __( 'Review card padding', 'tomawesome-review-widgets' ), 100 ),
					'card_border_radius'   => array( __( 'Review card corner radius', 'tomawesome-review-widgets' ), 100 ),
				) as $key => $field ) :
					$field_id = 'tarw-style-' . str_replace( '_', '-', $key );
					?>
					<p>
						<label for="<?php echo esc_attr( $field_id ); ?>"><strong><?php echo esc_html( $field[0] ); ?> (px)</strong></label>
						<input id="<?php echo esc_attr( $field_id ); ?>" name="tarw_widget[style][<?php echo esc_attr( $key ); ?>]" type="number" min="0" max="<?php echo esc_attr( $field[1] ); ?>" step="1" value="<?php echo esc_attr( $style[ $key ] ); ?>" placeholder="<?php esc_attr_e( 'Default', 'tomawesome-review-widgets' ); ?>">
					</p>
				<?php endforeach; ?>
			</div>
		</div>

		<p>
			<label for="tarw-custom-class"><strong><?php esc_html_e( 'Custom CSS classes (advanced)', 'tomawesome-review-widgets' ); ?></strong></label>
			<input class="widefat" id="tarw-custom-class" name="tarw_widget[custom_class]" type="text" value="<?php echo esc_attr( $settings['custom_class'] ); ?>">
			<span class="description"><?php esc_html_e( 'Optional class names for CSS defined in your theme, child theme, or WordPress Additional CSS screen.', 'tomawesome-review-widgets' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Renders a copyable shortcode.
	 *
	 * @param \WP_Post $post Current widget.
	 * @return void
	 */
	public function render_shortcode_box( $post ) {
		$shortcode = '[tomawesome_reviews id="' . absint( $post->ID ) . '"]';
		?>
		<p><?php esc_html_e( 'Publish the widget, then place this shortcode in a page, post, or page-builder shortcode element.', 'tomawesome-review-widgets' ); ?></p>
		<input class="widefat tarw-shortcode" type="text" readonly value="<?php echo esc_attr( $shortcode ); ?>" aria-label="<?php esc_attr_e( 'Widget shortcode', 'tomawesome-review-widgets' ); ?>">
		<?php
	}

	/**
	 * Renders source identifiers and links.
	 *
	 * @param \WP_Post $post Current source.
	 * @return void
	 */
	public function render_source_box( $post ) {
		wp_nonce_field( 'tarw_save_source', 'tarw_source_nonce' );
		$type = get_post_meta( $post->ID, '_tarw_source_type', true );
		if ( '' === $type ) {
			$type = 'business_profile';
		}
		?>
		<p>
			<label for="tarw-source-type"><strong><?php esc_html_e( 'Source type', 'tomawesome-review-widgets' ); ?></strong></label>
			<select id="tarw-source-type" name="tarw_source[source_type]">
				<option value="business_profile" <?php selected( $type, 'business_profile' ); ?>><?php esc_html_e( 'Google Business Profile — managed location, complete review list', 'tomawesome-review-widgets' ); ?></option>
				<option value="places" <?php selected( $type, 'places' ); ?>><?php esc_html_e( 'Google Places — public source, up to five Google-selected reviews', 'tomawesome-review-widgets' ); ?></option>
			</select>
		</p>
		<div class="tarw-source-business-profile">
			<p><label for="tarw-account-name"><strong><?php esc_html_e( 'Account resource name', 'tomawesome-review-widgets' ); ?></strong></label><input class="widefat" id="tarw-account-name" name="tarw_source[account_name]" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_tarw_account_name', true ) ); ?>" placeholder="accounts/123456789"></p>
			<p><label for="tarw-location-name"><strong><?php esc_html_e( 'Location resource name', 'tomawesome-review-widgets' ); ?></strong></label><input class="widefat" id="tarw-location-name" name="tarw_source[location_name]" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_tarw_location_name', true ) ); ?>" placeholder="locations/987654321"></p>
			<p class="description"><?php esc_html_e( 'Importing a connected location from Google Connection fills these identifiers automatically.', 'tomawesome-review-widgets' ); ?></p>
		</div>
		<div class="tarw-source-places">
			<p><label for="tarw-place-id"><strong><?php esc_html_e( 'Google Place ID', 'tomawesome-review-widgets' ); ?></strong></label><input class="widefat" id="tarw-place-id" name="tarw_source[place_id]" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_tarw_place_id', true ) ); ?>" placeholder="ChIJ..."></p>
			<p class="description">
				<?php esc_html_e( 'Places widgets include Google Maps attribution, author details, individual-review links, returned provider credits, and a selection/filter notice required for Places content. These Places-only elements disappear automatically when a widget is changed to a managed Business Profile source.', 'tomawesome-review-widgets' ); ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tarw_widget&page=tarw-getting-started#tarw-places-compliance' ) ); ?>"><?php esc_html_e( 'Why these elements appear', 'tomawesome-review-widgets' ); ?></a>
			</p>
		</div>
		<p><label for="tarw-review-url"><strong><?php esc_html_e( 'Read all reviews URL', 'tomawesome-review-widgets' ); ?></strong></label><input class="widefat" id="tarw-review-url" name="tarw_source[review_url]" type="url" value="<?php echo esc_attr( get_post_meta( $post->ID, '_tarw_review_url', true ) ); ?>"></p>
		<p><label for="tarw-leave-url"><strong><?php esc_html_e( 'Leave a review URL', 'tomawesome-review-widgets' ); ?></strong></label><input class="widefat" id="tarw-leave-url" name="tarw_source[leave_review_url]" type="url" value="<?php echo esc_attr( get_post_meta( $post->ID, '_tarw_leave_review_url', true ) ); ?>"></p>
		<p><label><input type="checkbox" name="tarw_source[enabled]" value="1" <?php checked( '0' !== (string) get_post_meta( $post->ID, '_tarw_enabled', true ) ); ?>> <?php esc_html_e( 'Include this source in daily synchronization', 'tomawesome-review-widgets' ); ?></label></p>
		<?php if ( $post->ID && 'auto-draft' !== $post->post_status ) : ?>
			<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tarw_sync_source&source_id=' . absint( $post->ID ) ), 'tarw_sync_source_' . absint( $post->ID ) ) ); ?>"><?php esc_html_e( 'Synchronize now', 'tomawesome-review-widgets' ); ?></a></p>
			<?php $this->source_status( $post->ID ); ?>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders the first-run checklist and beginner Google setup walkthrough.
	 *
	 * @return void
	 */
	public function render_getting_started() {
		$this->require_admin();

		$client_ready = '' !== $this->settings->get_secret( 'google_client_id' )
			&& '' !== $this->settings->get_secret( 'google_client_secret' );
		$places_ready = '' !== $this->settings->get_secret( 'places_api_key' );
		$sources      = get_posts(
			array(
				'post_type'      => 'tarw_source',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		$widgets      = get_posts(
			array(
				'post_type'      => 'tarw_widget',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		?>
		<div class="wrap tarw-admin-wrap tarw-onboarding">
			<h1><?php esc_html_e( 'Welcome to TomAwesome Review Widgets', 'tomawesome-review-widgets' ); ?></h1>
			<p class="tarw-lead"><?php esc_html_e( 'This plugin does not retrieve reviews immediately after activation. First choose a Google connection path, create your own Google credentials, add a review source, and synchronize it. You can return to this page at any time from Review Widgets > Getting Started.', 'tomawesome-review-widgets' ); ?></p>

			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'Google setup is required.', 'tomawesome-review-widgets' ); ?></strong> <?php esc_html_e( 'TomAwesome does not provide a shared API key, Google Cloud project, OAuth application, or shortcut around Google’s approval process. Places setup is the easier path but returns at most five Google-selected reviews. Managed Business Profile setup can retrieve the complete review list, but it is substantially more involved and may require waiting days or longer for Google to approve your project.', 'tomawesome-review-widgets' ); ?></p>
			</div>
			<p><a href="https://github.com/drumhead39/tomawesome-review-widgets/blob/main/docs/INSTALLATION.md" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open the complete beginner installation and Google setup guide', 'tomawesome-review-widgets' ); ?></a></p>

			<div class="tarw-admin-card">
				<h2><?php esc_html_e( 'Setup progress', 'tomawesome-review-widgets' ); ?></h2>
				<ul class="tarw-setup-progress">
					<?php $this->setup_status_item( is_ssl() && extension_loaded( 'openssl' ), __( 'Site is ready', 'tomawesome-review-widgets' ), __( 'HTTPS and PHP OpenSSL are required for the managed-business connection.', 'tomawesome-review-widgets' ) ); ?>
					<?php $this->setup_status_item( $client_ready || $places_ready, __( 'Google credentials saved', 'tomawesome-review-widgets' ), __( 'Save an OAuth client for managed businesses or an API key for Places.', 'tomawesome-review-widgets' ) ); ?>
					<?php $this->setup_status_item( ! empty( $sources ), __( 'Review source added', 'tomawesome-review-widgets' ), __( 'A source represents one business location.', 'tomawesome-review-widgets' ) ); ?>
					<?php $this->setup_status_item( ! empty( $widgets ), __( 'Widget created', 'tomawesome-review-widgets' ), __( 'Publish a widget to receive its shortcode.', 'tomawesome-review-widgets' ) ); ?>
				</ul>
			</div>

			<h2><?php esc_html_e( '1. Choose how to connect Google', 'tomawesome-review-widgets' ); ?></h2>
			<div class="tarw-path-grid">
				<div class="tarw-admin-card tarw-path-card">
					<p class="tarw-path-label"><?php esc_html_e( 'Easier setup — limited reviews', 'tomawesome-review-widgets' ); ?></p>
					<h3><?php esc_html_e( 'Google Places API', 'tomawesome-review-widgets' ); ?></h3>
					<p><?php esc_html_e( 'Choose this when five Google-selected reviews are enough or when you do not manage the Business Profile. This path does not require Business Profile API approval or OAuth.', 'tomawesome-review-widgets' ); ?></p>
					<p><strong><?php esc_html_e( 'What you need:', 'tomawesome-review-widgets' ); ?></strong> <?php esc_html_e( 'A billing-enabled Google Cloud project, Places API (New), an API key, and the business’s Place ID.', 'tomawesome-review-widgets' ); ?></p>
					<p><strong><?php esc_html_e( 'What you get:', 'tomawesome-review-widgets' ); ?></strong> <?php esc_html_e( 'At most five reviews selected by Google, required Google Maps attribution, and no Healthcare Privacy Mode.', 'tomawesome-review-widgets' ); ?></p>
					<p><a class="button button-primary" href="#tarw-places-setup"><?php esc_html_e( 'Follow the easier Places guide', 'tomawesome-review-widgets' ); ?></a></p>
				</div>
				<div class="tarw-admin-card tarw-path-card">
					<p class="tarw-path-label"><?php esc_html_e( 'Advanced setup — complete reviews', 'tomawesome-review-widgets' ); ?></p>
					<h3><?php esc_html_e( 'Managed Business Profile', 'tomawesome-review-widgets' ); ?></h3>
					<p><?php esc_html_e( 'Choose this only when your Google account owns or manages the Business Profile and you need the complete, paginated review list or Healthcare Privacy Mode.', 'tomawesome-review-widgets' ); ?></p>
					<p><strong><?php esc_html_e( 'What you need:', 'tomawesome-review-widgets' ); ?></strong> <?php esc_html_e( 'An eligible verified Business Profile, a dedicated Google Cloud project, Google’s Basic API Access approval, seven enabled APIs, a configured OAuth consent screen, and an OAuth web client.', 'tomawesome-review-widgets' ); ?></p>
					<p><strong><?php esc_html_e( 'Time expectation:', 'tomawesome-review-widgets' ); ?></strong> <?php esc_html_e( 'This is not a one-click setup. Google controls approval and does not guarantee a completion time, so setup may span multiple sessions and an external waiting period.', 'tomawesome-review-widgets' ); ?></p>
					<p><a class="button" href="#tarw-managed-setup"><?php esc_html_e( 'Follow the advanced managed-business guide', 'tomawesome-review-widgets' ); ?></a></p>
				</div>
			</div>

			<div id="tarw-managed-setup" class="tarw-admin-card tarw-guide-card">
				<h2><?php esc_html_e( 'Managed Business Profile: step-by-step setup', 'tomawesome-review-widgets' ); ?></h2>
				<div class="notice notice-warning inline"><p><strong><?php esc_html_e( 'Allow extra time for this path.', 'tomawesome-review-widgets' ); ?></strong> <?php esc_html_e( 'Creating the Cloud project is only the beginning. Google must separately approve that project for Business Profile API access before location discovery can work. The plugin cannot request, accelerate, or grant that approval. Menu names can change, so the linked Google documentation is authoritative if a screen differs from this guide.', 'tomawesome-review-widgets' ); ?></p></div>

				<h3><?php esc_html_e( 'Before you begin', 'tomawesome-review-widgets' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Use the Google account shown as an Owner or Manager under Business Profile settings > People and access.', 'tomawesome-review-widgets' ); ?></li>
					<li><?php esc_html_e( 'Google currently requires the managed Business Profile to be verified and active for at least 60 days and to have a matching business website.', 'tomawesome-review-widgets' ); ?></li>
					<li><?php esc_html_e( 'Keep the same Google Cloud project selected throughout every step. Approval belongs to the project number, not merely to your Google account.', 'tomawesome-review-widgets' ); ?></li>
				</ul>

				<ol class="tarw-numbered-guide">
					<li>
						<h3><?php esc_html_e( 'Create or select a Google Cloud project', 'tomawesome-review-widgets' ); ?></h3>
						<p><?php esc_html_e( 'Open Google Cloud Console, use the project selector in the top bar, and create a project dedicated to this website. Give it a recognizable name. On the project Dashboard, copy the numeric Project number; the access form asks for it.', 'tomawesome-review-widgets' ); ?></p>
						<p><a href="https://console.cloud.google.com/projectcreate" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Create a Google Cloud project', 'tomawesome-review-widgets' ); ?></a> · <a href="https://console.cloud.google.com/home/dashboard" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open the project Dashboard', 'tomawesome-review-widgets' ); ?></a></p>
					</li>
					<li>
						<h3><?php esc_html_e( 'Request basic Business Profile API access', 'tomawesome-review-widgets' ); ?></h3>
						<p><?php esc_html_e( 'With that exact project selected, open Google’s access form and choose “Application for Basic API Access.” The form asks for both the text Project ID and numeric Project number. Submit it using the email address that owns or manages the Business Profile. Do not create duplicate applications. Wait for Google’s approval email before continuing; enabling APIs and completing OAuth do not replace this approval.', 'tomawesome-review-widgets' ); ?></p>
						<p><a href="https://support.google.com/business/contact/api_default" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open the Business Profile API access form', 'tomawesome-review-widgets' ); ?></a> · <a href="https://developers.google.com/my-business/content/prereqs" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read Google’s current eligibility requirements', 'tomawesome-review-widgets' ); ?></a></p>
						<p class="description"><?php esc_html_e( 'To check approval later, inspect the My Business Account Management API quota. A Requests per minute limit of 0 means the project is still blocked. Google’s normal Basic API Access approval grants a 300 requests-per-minute quota.', 'tomawesome-review-widgets' ); ?></p>
					</li>
					<li>
						<h3><?php esc_html_e( 'Enable the Business Profile APIs', 'tomawesome-review-widgets' ); ?></h3>
						<p><?php esc_html_e( 'After approval, open APIs & Services > Library. Search for each exact name below, open it, and choose Enable. If the button says Manage, that API is already enabled. The Google My Business API may not appear until Google approves the project.', 'tomawesome-review-widgets' ); ?></p>
						<ul class="tarw-api-list">
							<li><?php esc_html_e( 'Google My Business API', 'tomawesome-review-widgets' ); ?></li>
							<li><?php esc_html_e( 'My Business Account Management API', 'tomawesome-review-widgets' ); ?></li>
							<li><?php esc_html_e( 'My Business Lodging API', 'tomawesome-review-widgets' ); ?></li>
							<li><?php esc_html_e( 'My Business Place Actions API', 'tomawesome-review-widgets' ); ?></li>
							<li><?php esc_html_e( 'My Business Notifications API', 'tomawesome-review-widgets' ); ?></li>
							<li><?php esc_html_e( 'My Business Verifications API', 'tomawesome-review-widgets' ); ?></li>
							<li><?php esc_html_e( 'My Business Business Information API', 'tomawesome-review-widgets' ); ?></li>
						</ul>
						<p class="description"><?php esc_html_e( 'Location discovery uses the Account Management and Business Information APIs. Review synchronization uses the separate Google My Business API. A location can therefore be discovered successfully while review synchronization still fails if Google My Business API was missed.', 'tomawesome-review-widgets' ); ?></p>
						<p><a href="https://console.cloud.google.com/apis/library" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open the API Library', 'tomawesome-review-widgets' ); ?></a> · <a href="https://console.cloud.google.com/apis/library/mybusiness.googleapis.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Google My Business API', 'tomawesome-review-widgets' ); ?></a> · <a href="https://developers.google.com/my-business/content/basic-setup#enable-the-apis" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'See Google’s current API list', 'tomawesome-review-widgets' ); ?></a></p>
					</li>
					<li>
						<h3><?php esc_html_e( 'Configure Google Auth Platform', 'tomawesome-review-widgets' ); ?></h3>
						<p><?php esc_html_e( 'Open Google Auth Platform in the same project. If Google shows “Get started,” enter the app name, a user-support email, choose External for the audience, add a developer contact email, and finish the initial form.', 'tomawesome-review-widgets' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Branding: enter an accurate homepage, privacy policy, terms link, and authorized domain for the website using the plugin.', 'tomawesome-review-widgets' ); ?></li>
							<li><?php esc_html_e( 'Audience: leave the app in Testing for the first connection. Under Test users, add the exact Google account that owns or manages the Business Profile.', 'tomawesome-review-widgets' ); ?></li>
							<li><?php esc_html_e( 'Data Access: add the scope https://www.googleapis.com/auth/business.manage.', 'tomawesome-review-widgets' ); ?></li>
						</ul>
						<p><a href="https://console.cloud.google.com/auth/overview" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Google Auth Platform', 'tomawesome-review-widgets' ); ?></a></p>
					</li>
					<li>
						<h3><?php esc_html_e( 'Create the OAuth web client', 'tomawesome-review-widgets' ); ?></h3>
						<p><?php esc_html_e( 'In Google Auth Platform, open Clients > Create client. Select Web application, give the client a recognizable name, and add the following value under Authorized redirect URIs. Do not put it under JavaScript origins.', 'tomawesome-review-widgets' ); ?></p>
						<p><code class="tarw-redirect-uri"><?php echo esc_html( $this->oauth->redirect_uri() ); ?></code></p>
						<p><?php esc_html_e( 'The protocol, domain, path, and query string must match exactly. Create the client, then copy its Client ID and Client secret.', 'tomawesome-review-widgets' ); ?></p>
						<p><a href="https://console.cloud.google.com/auth/clients" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Google Auth Platform clients', 'tomawesome-review-widgets' ); ?></a></p>
					</li>
					<li>
						<h3><?php esc_html_e( 'Save credentials and connect WordPress', 'tomawesome-review-widgets' ); ?></h3>
						<p><?php esc_html_e( 'Open Google Connection, paste the Client ID and Client secret, and save. Then select Connect Google Business Profile and sign in with the same owner or manager account that you added as a test user.', 'tomawesome-review-widgets' ); ?></p>
						<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=tarw_widget&page=tarw-settings' ) ); ?>"><?php esc_html_e( 'Open Google Connection', 'tomawesome-review-widgets' ); ?></a></p>
						<div class="notice notice-warning inline"><p><?php esc_html_e( 'Testing is appropriate for setup, but Google generally expires refresh tokens after seven days when an External app requests this scope. Before relying on daily synchronization, review Google’s production and verification requirements for your use case.', 'tomawesome-review-widgets' ); ?></p></div>
					</li>
					<li>
						<h3><?php esc_html_e( 'Import and synchronize a location', 'tomawesome-review-widgets' ); ?></h3>
						<p><?php esc_html_e( 'After the connection succeeds, select Discover managed locations. Choose Add as review source for each needed location. Importing creates an empty source; it does not download reviews automatically. Open the new source, verify its Google links, and select Synchronize now. Do not assign a widget to that source until synchronization reports success.', 'tomawesome-review-widgets' ); ?></p>
					</li>
				</ol>
			</div>

			<div id="tarw-places-setup" class="tarw-admin-card tarw-guide-card">
				<h2><?php esc_html_e( 'Public Places: step-by-step setup', 'tomawesome-review-widgets' ); ?></h2>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'Before publishing Places content, the website must provide publicly accessible Terms of Use and a Privacy Policy that incorporate Google’s Terms of Service and Privacy Policy. Places widgets must also keep the plugin’s Google Maps, author, provider, filter, and individual-review attributions visible.', 'tomawesome-review-widgets' ); ?></p></div>
				<ol class="tarw-numbered-guide">
					<li><h3><?php esc_html_e( 'Prepare Google Cloud', 'tomawesome-review-widgets' ); ?></h3><p><?php esc_html_e( 'Create or select a Google Cloud project, attach a billing account, then open APIs & Services > Library and enable Places API (New). Confirm the intended project name remains selected in the top bar before each step.', 'tomawesome-review-widgets' ); ?></p><p><a href="https://console.cloud.google.com/projectcreate" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Create a project', 'tomawesome-review-widgets' ); ?></a> · <a href="https://console.cloud.google.com/billing" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Billing', 'tomawesome-review-widgets' ); ?></a> · <a href="https://console.cloud.google.com/apis/library/places.googleapis.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Places API (New)', 'tomawesome-review-widgets' ); ?></a></p></li>
					<li><h3><?php esc_html_e( 'Create and restrict an API key', 'tomawesome-review-widgets' ); ?></h3><p><?php esc_html_e( 'Open APIs & Services > Credentials, choose Create credentials > API key, and restrict its API access to Places API (New). Requests come from the WordPress server, so a browser HTTP-referrer restriction will not work. Use a server IP restriction only if your host provides a stable outbound IP.', 'tomawesome-review-widgets' ); ?></p><p><a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Google Cloud credentials', 'tomawesome-review-widgets' ); ?></a></p></li>
					<li><h3><?php esc_html_e( 'Save the key', 'tomawesome-review-widgets' ); ?></h3><p><?php esc_html_e( 'Open Google Connection, paste the key into Places API key, and save.', 'tomawesome-review-widgets' ); ?></p><p><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=tarw_widget&page=tarw-settings' ) ); ?>"><?php esc_html_e( 'Open Google Connection', 'tomawesome-review-widgets' ); ?></a></p></li>
					<li><h3><?php esc_html_e( 'Add and synchronize the source', 'tomawesome-review-widgets' ); ?></h3><p><?php esc_html_e( 'Find the business’s Place ID with Google’s Place ID tool. Then open Review Sources > Add New, choose Google Places, paste the Place ID, publish, and select Synchronize now. Synchronize once after every plugin update that changes Places attribution handling.', 'tomawesome-review-widgets' ); ?></p><p><a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Find a Google Place ID', 'tomawesome-review-widgets' ); ?></a> · <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=tarw_source' ) ); ?>"><?php esc_html_e( 'Add a Review Source', 'tomawesome-review-widgets' ); ?></a></p></li>
				</ol>

				<div id="tarw-places-compliance">
					<h3><?php esc_html_e( 'Why Places widgets show extra attribution and a selection notice', 'tomawesome-review-widgets' ); ?></h3>
					<p><?php esc_html_e( 'These elements are not promotional text added by the plugin. They exist because Google’s Places policies require sites that republish Places reviews to identify the source, credit review authors and returned data providers, provide direct access to each source review, and clearly describe how reviews are selected, filtered, and ordered.', 'tomawesome-review-widgets' ); ?></p>
					<ul>
						<li><?php esc_html_e( 'Google Maps attribution identifies the provider of the Places content.', 'tomawesome-review-widgets' ); ?></li>
						<li><?php esc_html_e( 'Available reviewer names, profile links, and photos credit each author.', 'tomawesome-review-widgets' ); ?></li>
						<li><?php esc_html_e( 'The link on each card opens that individual review on Google Maps.', 'tomawesome-review-widgets' ); ?></li>
						<li><?php esc_html_e( 'Any data-provider credit returned by Google is displayed beside the Google Maps attribution.', 'tomawesome-review-widgets' ); ?></li>
						<li><?php esc_html_e( 'The footer notice explains Google’s relevance-selected sample and the widget’s active count, rating, written-text, and order filters.', 'tomawesome-review-widgets' ); ?></li>
					</ul>

					<h3><?php esc_html_e( 'What changes after switching to a managed Business Profile source', 'tomawesome-review-widgets' ); ?></h3>
					<p><?php esc_html_e( 'Do not convert the existing Places source. After Google approves your Business Profile API project, connect Google Business Profile, discover and synchronize the managed location, then edit each widget and select that new managed source. On the next page load, the plugin automatically removes the Places selection notice, Google Maps Places attribution, returned provider credit, and individual Places-review links. Managed sources can retrieve the complete review list, make reviewer photos optional, and allow Healthcare Privacy Mode.', 'tomawesome-review-widgets' ); ?></p>
				</div>
				<p><a href="https://developers.google.com/maps/documentation/places/web-service/policies" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Review Google’s current Places policies and attribution requirements', 'tomawesome-review-widgets' ); ?></a></p>
			</div>

			<div class="tarw-admin-card">
				<h2><?php esc_html_e( '2. Create and place a widget', 'tomawesome-review-widgets' ); ?></h2>
				<ol>
					<li><?php esc_html_e( 'Open Review Widgets > Add New and enter an internal title.', 'tomawesome-review-widgets' ); ?></li>
					<li><?php esc_html_e( 'Choose the synchronized source, layout, filters, responsive columns, and display options.', 'tomawesome-review-widgets' ); ?></li>
					<li><?php esc_html_e( 'Publish the widget and copy its shortcode.', 'tomawesome-review-widgets' ); ?></li>
					<li><?php esc_html_e( 'Paste the shortcode into a WordPress Shortcode block or your page builder’s shortcode element.', 'tomawesome-review-widgets' ); ?></li>
				</ol>
				<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=tarw_widget' ) ); ?>"><?php esc_html_e( 'Create a Review Widget', 'tomawesome-review-widgets' ); ?></a></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Saves a widget configuration.
	 *
	 * @param int $post_id Widget post ID.
	 * @return void
	 */
	public function save_widget( $post_id ) {
		if ( ! $this->can_save( $post_id, 'tarw_widget_nonce', 'tarw_save_widget' ) ) {
			return;
		}
		check_admin_referer( 'tarw_save_widget', 'tarw_widget_nonce' );

		$input       = isset( $_POST['tarw_widget'] ) && is_array( $_POST['tarw_widget'] )
			? map_deep( wp_unslash( $_POST['tarw_widget'] ), 'sanitize_text_field' )
			: array();
		$layout      = in_array( $input['layout'] ?? '', array( 'grid', 'list', 'carousel', 'featured' ), true ) ? $input['layout'] : 'grid';
		$sort        = in_array( $input['sort'] ?? '', array( 'newest', 'oldest', 'highest', 'random' ), true ) ? $input['sort'] : 'newest';
		$style_input = isset( $input['style'] ) && is_array( $input['style'] ) ? $input['style'] : array();

		$source_id = absint( $input['source_id'] ?? 0 );
		$is_places = $source_id && 'places' === get_post_meta( $source_id, '_tarw_source_type', true );

		$settings = array(
			'source_id'       => $source_id,
			'public_heading'  => substr( sanitize_text_field( $input['public_heading'] ?? '' ), 0, 200 ),
			'layout'          => $layout,
			'limit'           => min( 50, max( 1, absint( $input['limit'] ?? 6 ) ) ),
			'min_rating'      => min( 5, max( 1, absint( $input['min_rating'] ?? 1 ) ) ),
			'sort'            => $sort,
			'text_only'       => empty( $input['text_only'] ) ? 0 : 1,
			'max_chars'       => min( 2000, absint( $input['max_chars'] ?? 320 ) ),
			'show_avatar'     => ( $is_places || ! empty( $input['show_avatar'] ) ) ? 1 : 0,
			'show_date'       => empty( $input['show_date'] ) ? 0 : 1,
			'show_summary'    => empty( $input['show_summary'] ) ? 0 : 1,
			'show_read_all'   => empty( $input['show_read_all'] ) ? 0 : 1,
			'show_leave'      => empty( $input['show_leave'] ) ? 0 : 1,
			'columns_desktop' => min( 4, max( 1, absint( $input['columns_desktop'] ?? 3 ) ) ),
			'columns_tablet'  => min( 3, max( 1, absint( $input['columns_tablet'] ?? 2 ) ) ),
			'columns_mobile'  => min( 2, max( 1, absint( $input['columns_mobile'] ?? 1 ) ) ),
			'privacy_mode'    => ! $is_places && ! empty( $input['privacy_mode'] ) ? 1 : 0,
			'style'           => $this->sanitize_widget_style( $style_input ),
			'custom_class'    => implode( ' ', array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', (string) ( $input['custom_class'] ?? '' ) ) ) ) ),
		);

		update_post_meta( $post_id, '_tarw_widget_settings', $settings );
	}

	/**
	 * Sanitizes the optional visual-style controls saved with a widget.
	 *
	 * @param array<string,mixed> $input Submitted style fields.
	 * @return array<string,string>
	 */
	private function sanitize_widget_style( array $input ) {
		$style = Widget_Display::style_defaults();
		foreach ( array( 'widget_background', 'widget_text', 'accent', 'stars', 'widget_border', 'card_background', 'card_border' ) as $key ) {
			$color         = sanitize_hex_color( $input[ $key ] ?? '' );
			$style[ $key ] = is_string( $color ) ? $color : '';
		}

		foreach ( array(
			'widget_margin'        => 300,
			'widget_padding'       => 200,
			'widget_border_width'  => 20,
			'widget_border_radius' => 100,
			'card_padding'         => 100,
			'card_border_radius'   => 100,
		) as $key => $maximum ) {
			$value         = trim( (string) ( $input[ $key ] ?? '' ) );
			$style[ $key ] = preg_match( '/^\d+$/', $value ) ? (string) min( $maximum, absint( $value ) ) : '';
		}

		return $style;
	}

	/**
	 * Saves a source configuration.
	 *
	 * @param int $post_id Source post ID.
	 * @return void
	 */
	public function save_source( $post_id ) {
		if ( ! $this->can_save( $post_id, 'tarw_source_nonce', 'tarw_save_source' ) ) {
			return;
		}
		check_admin_referer( 'tarw_save_source', 'tarw_source_nonce' );

		$input = isset( $_POST['tarw_source'] ) && is_array( $_POST['tarw_source'] )
			? map_deep( wp_unslash( $_POST['tarw_source'] ), 'sanitize_text_field' )
			: array();
		$type  = 'places' === ( $input['source_type'] ?? '' ) ? 'places' : 'business_profile';

		update_post_meta( $post_id, '_tarw_source_type', $type );
		update_post_meta( $post_id, '_tarw_account_name', $this->resource_name( $input['account_name'] ?? '', 'accounts' ) );
		update_post_meta( $post_id, '_tarw_location_name', $this->resource_name( $input['location_name'] ?? '', 'locations' ) );
		update_post_meta( $post_id, '_tarw_place_id', preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $input['place_id'] ?? '' ) ) );
		update_post_meta( $post_id, '_tarw_review_url', esc_url_raw( $input['review_url'] ?? '' ) );
		update_post_meta( $post_id, '_tarw_leave_review_url', esc_url_raw( $input['leave_review_url'] ?? '' ) );
		update_post_meta( $post_id, '_tarw_enabled', empty( $input['enabled'] ) ? 0 : 1 );
	}

	/**
	 * Renders Google API connection setup and optional location discovery.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		$this->require_admin();
		$discover  = isset( $_GET['tarw_discover'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['tarw_discover'] ) );
		$locations = array();
		if ( $discover && check_admin_referer( 'tarw_discover_locations' ) ) {
			$locations = $this->sync->discover_locations();
		}
		?>
		<div class="wrap tarw-admin-wrap">
			<h1><?php esc_html_e( 'Google Connection', 'tomawesome-review-widgets' ); ?></h1>
			<p><?php esc_html_e( 'Credentials stay on this WordPress site and API requests are sent directly from this server to Google. TomAwesome does not operate a proxy or receive review data.', 'tomawesome-review-widgets' ); ?></p>
			<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tarw_widget&page=tarw-getting-started' ) ); ?>"><?php esc_html_e( 'Need help? Open the step-by-step Google setup guide.', 'tomawesome-review-widgets' ); ?></a></p>
			<?php if ( ! is_ssl() ) : ?>
				<div class="notice notice-error inline"><p><?php esc_html_e( 'HTTPS is required before connecting Google OAuth. Correct the WordPress Address and Site Address or the server HTTPS configuration first.', 'tomawesome-review-widgets' ); ?></p></div>
			<?php endif; ?>

			<div class="tarw-admin-card">
				<h2><?php esc_html_e( 'Google Business Profile API', 'tomawesome-review-widgets' ); ?></h2>
				<p><?php esc_html_e( 'Use this connection for businesses you own or are authorized to manage. It can retrieve the complete review list for multiple managed locations. Google requires an approved Business Profile API project and OAuth credentials.', 'tomawesome-review-widgets' ); ?></p>
				<p><strong><?php esc_html_e( 'Authorized redirect URI', 'tomawesome-review-widgets' ); ?></strong><br><code><?php echo esc_html( $this->oauth->redirect_uri() ); ?></code></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="tarw_save_settings">
					<?php wp_nonce_field( 'tarw_save_settings' ); ?>
					<table class="form-table" role="presentation">
						<tr><th scope="row"><label for="tarw-client-id"><?php esc_html_e( 'OAuth client ID', 'tomawesome-review-widgets' ); ?></label></th><td><input class="regular-text" id="tarw-client-id" name="google_client_id" type="password" autocomplete="new-password" placeholder="<?php echo $this->settings->get_secret( 'google_client_id' ) ? esc_attr__( 'Saved — enter a value only to replace it', 'tomawesome-review-widgets' ) : ''; ?>"></td></tr>
						<tr><th scope="row"><label for="tarw-client-secret"><?php esc_html_e( 'OAuth client secret', 'tomawesome-review-widgets' ); ?></label></th><td><input class="regular-text" id="tarw-client-secret" name="google_client_secret" type="password" autocomplete="new-password" placeholder="<?php echo $this->settings->get_secret( 'google_client_secret' ) ? esc_attr__( 'Saved — enter a value only to replace it', 'tomawesome-review-widgets' ) : ''; ?>"></td></tr>
						<tr><th scope="row"><label for="tarw-places-key"><?php esc_html_e( 'Places API key', 'tomawesome-review-widgets' ); ?></label></th><td><input class="regular-text" id="tarw-places-key" name="places_api_key" type="password" autocomplete="new-password" placeholder="<?php echo $this->settings->get_secret( 'places_api_key' ) ? esc_attr__( 'Saved — enter a value only to replace it', 'tomawesome-review-widgets' ) : ''; ?>"><p class="description"><?php esc_html_e( 'Optional. Used only for public Places sources, which return at most five Google-selected reviews.', 'tomawesome-review-widgets' ); ?></p></td></tr>
						<tr><th scope="row"><?php esc_html_e( 'Uninstall behavior', 'tomawesome-review-widgets' ); ?></th><td><label><input type="checkbox" name="delete_on_uninstall" value="1" <?php checked( $this->settings->get( 'delete_on_uninstall' ) ); ?>> <?php esc_html_e( 'Delete plugin settings, widgets, sources, and synchronized reviews when the plugin is uninstalled', 'tomawesome-review-widgets' ); ?></label></td></tr>
					</table>
					<?php submit_button( __( 'Save connection settings', 'tomawesome-review-widgets' ) ); ?>
				</form>

				<?php if ( $this->oauth->is_connected() ) : ?>
					<p><span class="tarw-status tarw-status-good"><?php esc_html_e( 'Google Business Profile is connected.', 'tomawesome-review-widgets' ); ?></span></p>
					<p>
						<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=tarw_widget&page=tarw-settings&tarw_discover=1' ), 'tarw_discover_locations' ) ); ?>"><?php esc_html_e( 'Discover managed locations', 'tomawesome-review-widgets' ); ?></a>
						<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tarw_disconnect_google' ), 'tarw_disconnect_google' ) ); ?>"><?php esc_html_e( 'Disconnect Google', 'tomawesome-review-widgets' ); ?></a>
					</p>
				<?php else : ?>
					<p><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tarw_connect_google' ), 'tarw_connect_google' ) ); ?>"><?php esc_html_e( 'Connect Google Business Profile', 'tomawesome-review-widgets' ); ?></a></p>
				<?php endif; ?>
			</div>

			<?php if ( is_wp_error( $locations ) ) : ?>
				<?php $this->render_business_profile_error( $locations ); ?>
			<?php elseif ( $discover ) : ?>
				<?php $this->render_locations( $locations ); ?>
			<?php endif; ?>

			<div class="tarw-admin-card">
				<h2><?php esc_html_e( 'Before connecting', 'tomawesome-review-widgets' ); ?></h2>
				<ol>
					<li><?php esc_html_e( 'Create a Google Cloud project owned by your organization.', 'tomawesome-review-widgets' ); ?></li>
					<li><?php esc_html_e( 'Apply for Google Business Profile API access and wait for approval.', 'tomawesome-review-widgets' ); ?></li>
					<li><?php esc_html_e( 'Enable the required Business Profile APIs and configure the OAuth consent screen.', 'tomawesome-review-widgets' ); ?></li>
					<li><?php esc_html_e( 'Create a Web application OAuth client and add the exact redirect URI shown above.', 'tomawesome-review-widgets' ); ?></li>
					<li><?php esc_html_e( 'Save the client credentials here, connect Google, discover locations, then synchronize each imported source.', 'tomawesome-review-widgets' ); ?></li>
				</ol>
				<p><a href="https://developers.google.com/my-business/content/prereqs" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google Business Profile API prerequisites', 'tomawesome-review-widgets' ); ?></a> · <a href="https://developers.google.com/my-business/content/policies" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google Business Profile API policies', 'tomawesome-review-widgets' ); ?></a></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders actionable help for Business Profile API failures.
	 *
	 * @param \WP_Error $error Google Business Profile error.
	 * @return void
	 */
	private function render_business_profile_error( \WP_Error $error ) {
		if ( 'tarw_business_profile_quota_exceeded' !== $error->get_error_code() ) {
			?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $error->get_error_message() ); ?></p></div>
			<?php
			return;
		}

		$data              = $error->get_error_data();
		$data              = is_array( $data ) ? $data : array();
		$technical_message = sanitize_text_field( $data['google_message'] ?? '' );
		$zero_quota        = isset( $data['quota_limit_value'] ) && '0' === (string) $data['quota_limit_value'];
		?>
		<div class="notice notice-error inline tarw-api-help">
			<?php if ( $zero_quota ) : ?>
				<p><strong><?php esc_html_e( 'Google has not granted Business Profile API access to this project', 'tomawesome-review-widgets' ); ?></strong></p>
				<p><?php esc_html_e( 'Google reports that this project’s Requests per minute limit is 0. Enabling the API and completing OAuth setup are separate from receiving Business Profile Basic API Access.', 'tomawesome-review-widgets' ); ?></p>
			<?php else : ?>
				<p><strong><?php esc_html_e( 'Google Business Profile API quota is unavailable', 'tomawesome-review-widgets' ); ?></strong></p>
				<p><?php esc_html_e( 'Google refused the managed-location request because the Account Management API quota is zero or temporarily exhausted. For a newly configured project, the usual cause is a Requests per minute limit of 0 because Google has not yet granted Basic API Access.', 'tomawesome-review-widgets' ); ?></p>
			<?php endif; ?>
			<ol>
				<li><?php esc_html_e( 'Open the Account Management API quota page in the same Google Cloud project used for these OAuth credentials and find Requests per minute.', 'tomawesome-review-widgets' ); ?></li>
				<li><?php esc_html_e( 'If the limit is 0, submit Google’s Basic API Access application and wait for approval. Enabling the API and completing OAuth setup do not grant this access by themselves.', 'tomawesome-review-widgets' ); ?></li>
				<li><?php esc_html_e( 'If the limit is above 0, wait at least one minute and try Discover managed locations again. If the error continues, confirm that the approved project and the OAuth client project are the same.', 'tomawesome-review-widgets' ); ?></li>
			</ol>
			<p>
				<a class="button button-primary" href="https://console.cloud.google.com/apis/api/mybusinessaccountmanagement.googleapis.com/quotas" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Check API quota in Google Cloud', 'tomawesome-review-widgets' ); ?></a>
				<a class="button" href="https://support.google.com/business/contact/api_default" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Apply for Basic API Access', 'tomawesome-review-widgets' ); ?></a>
			</p>
			<?php if ( '' !== $technical_message ) : ?>
				<details>
					<summary><?php esc_html_e( 'Google technical details', 'tomawesome-review-widgets' ); ?></summary>
					<p><code><?php echo esc_html( $technical_message ); ?></code></p>
				</details>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders discovered locations with import actions.
	 *
	 * @param array<int,array<string,mixed>> $locations Locations.
	 * @return void
	 */
	private function render_locations( array $locations ) {
		?>
		<div class="tarw-admin-card">
			<h2><?php esc_html_e( 'Managed locations', 'tomawesome-review-widgets' ); ?></h2>
			<?php if ( empty( $locations ) ) : ?>
				<p><?php esc_html_e( 'Google returned no locations for this account.', 'tomawesome-review-widgets' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Location', 'tomawesome-review-widgets' ); ?></th><th><?php esc_html_e( 'Account', 'tomawesome-review-widgets' ); ?></th><th><?php esc_html_e( 'Action', 'tomawesome-review-widgets' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $locations as $location ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $location['title'] ?? $location['name'] ?? '' ); ?></strong><br><code><?php echo esc_html( $location['name'] ?? '' ); ?></code></td>
							<td><?php echo esc_html( $location['tarw_account_label'] ?? $location['tarw_account_name'] ?? '' ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="tarw_add_location">
									<input type="hidden" name="account_name" value="<?php echo esc_attr( $location['tarw_account_name'] ?? '' ); ?>">
									<input type="hidden" name="location_name" value="<?php echo esc_attr( $location['name'] ?? '' ); ?>">
									<input type="hidden" name="location_title" value="<?php echo esc_attr( $location['title'] ?? $location['name'] ?? '' ); ?>">
									<input type="hidden" name="review_url" value="<?php echo esc_attr( $location['metadata']['mapsUri'] ?? '' ); ?>">
									<input type="hidden" name="leave_review_url" value="<?php echo esc_attr( $location['metadata']['newReviewUri'] ?? '' ); ?>">
									<?php wp_nonce_field( 'tarw_add_location' ); ?>
									<button class="button" type="submit"><?php esc_html_e( 'Add as review source', 'tomawesome-review-widgets' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders the review privacy approval workflow.
	 *
	 * @return void
	 */
	public function render_review_library() {
		$this->require_admin();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only administrator filters; values are sanitized below.
		$source_id = isset( $_GET['source_id'] ) ? absint( wp_unslash( $_GET['source_id'] ) ) : 0;
		$page      = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$reviews = $this->repository->get_admin_page( $source_id, $page, 25 );
		$total   = $this->repository->count( $source_id );
		$sources = get_posts(
			array(
				'post_type'      => 'tarw_source',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<div class="wrap tarw-admin-wrap">
			<h1><?php esc_html_e( 'Review Library', 'tomawesome-review-widgets' ); ?></h1>
			<div class="notice notice-warning inline"><p><strong><?php esc_html_e( 'Healthcare Privacy Mode is not a compliance certification.', 'tomawesome-review-widgets' ); ?></strong> <?php esc_html_e( 'Before approving copy, remove names, dates, diagnoses, treatments, relationships, locations, and other details that could identify a person. Confirm that your organization has any authorization or legal basis required to republish the content. The plugin cannot make that determination for you.', 'tomawesome-review-widgets' ); ?></p></div>

			<form method="get">
				<input type="hidden" name="post_type" value="tarw_widget">
				<input type="hidden" name="page" value="tarw-review-library">
				<label for="tarw-library-source" class="screen-reader-text"><?php esc_html_e( 'Filter by source', 'tomawesome-review-widgets' ); ?></label>
				<select id="tarw-library-source" name="source_id">
					<option value="0"><?php esc_html_e( 'All sources', 'tomawesome-review-widgets' ); ?></option>
					<?php
					foreach ( $sources as $source ) :
						?>
						<option value="<?php echo esc_attr( $source->ID ); ?>" <?php selected( $source_id, $source->ID ); ?>><?php echo esc_html( $source->post_title ); ?></option><?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Filter', 'tomawesome-review-widgets' ), 'secondary', '', false ); ?>
			</form>

			<?php if ( empty( $reviews ) ) : ?>
				<p><?php esc_html_e( 'No synchronized reviews were found.', 'tomawesome-review-widgets' ); ?></p>
			<?php else : ?>
				<div class="tarw-review-library">
					<?php
					foreach ( $reviews as $review ) :
						$star_label = sprintf(
							/* translators: %d: Number of stars in the review rating. */
							_n( '%d star', '%d stars', $review->rating, 'tomawesome-review-widgets' ),
							$review->rating
						);
						?>
						<form class="tarw-review-editor" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="tarw_save_privacy_review">
						<input type="hidden" name="review_id" value="<?php echo esc_attr( $review->id ); ?>">
						<input type="hidden" name="source_id" value="<?php echo esc_attr( $source_id ); ?>">
						<input type="hidden" name="paged" value="<?php echo esc_attr( $page ); ?>">
						<?php wp_nonce_field( 'tarw_privacy_review_' . absint( $review->id ) ); ?>
						<div class="tarw-review-original">
								<p><strong><?php echo esc_html( $review->reviewer_name ); ?></strong> · <?php echo esc_html( $star_label ); ?> · <?php echo esc_html( get_the_title( $review->source_id ) ); ?></p>
							<blockquote><?php echo nl2br( esc_html( $review->review_text ) ); ?></blockquote>
						</div>
						<div class="tarw-review-privacy-copy">
							<label for="tarw-excerpt-<?php echo esc_attr( $review->id ); ?>"><strong><?php esc_html_e( 'Privacy-reviewed display copy', 'tomawesome-review-widgets' ); ?></strong></label>
							<textarea id="tarw-excerpt-<?php echo esc_attr( $review->id ); ?>" name="privacy_excerpt" rows="5" maxlength="2000"><?php echo esc_textarea( $review->privacy_excerpt ); ?></textarea>
							<label><input type="checkbox" name="privacy_approved" value="1" <?php checked( $review->privacy_approved ); ?>> <?php esc_html_e( 'Approve this copy for Healthcare Privacy Mode after completing our organization’s privacy and authorization review', 'tomawesome-review-widgets' ); ?></label>
							<?php submit_button( __( 'Save privacy review', 'tomawesome-review-widgets' ), 'secondary', '', false ); ?>
						</div>
					</form>
				<?php endforeach; ?>
				</div>
				<?php
				$pages = (int) ceil( $total / 25 );
				if ( $pages > 1 ) {
					echo wp_kses_post(
						paginate_links(
							array(
								'base'    => add_query_arg( 'paged', '%#%' ),
								'format'  => '',
								'current' => $page,
								'total'   => $pages,
							)
						)
					);
				}
				?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Saves connection settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		$this->require_admin();
		check_admin_referer( 'tarw_save_settings' );

		$delete_on_uninstall = isset( $_POST['delete_on_uninstall'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['delete_on_uninstall'] ) );
		$result              = $this->settings->update(
			array(
				'google_client_id'     => sanitize_text_field( wp_unslash( $_POST['google_client_id'] ?? '' ) ),
				'google_client_secret' => sanitize_text_field( wp_unslash( $_POST['google_client_secret'] ?? '' ) ),
				'places_api_key'       => sanitize_text_field( wp_unslash( $_POST['places_api_key'] ?? '' ) ),
				'delete_on_uninstall'  => $delete_on_uninstall ? 1 : 0,
			),
			array( 'google_client_id', 'google_client_secret', 'places_api_key' )
		);

		$this->redirect_notice( is_wp_error( $result ) ? 'error' : 'settings_saved', is_wp_error( $result ) ? $result->get_error_message() : '' );
	}

	/**
	 * Starts OAuth authorization.
	 *
	 * @return void
	 */
	public function connect_google() {
		$this->require_admin();
		check_admin_referer( 'tarw_connect_google' );
		if ( ! is_ssl() ) {
			$this->redirect_notice( 'error', __( 'Google OAuth cannot be started until the WordPress administrator uses HTTPS.', 'tomawesome-review-widgets' ) );
		}
		$url = $this->oauth->authorization_url();
		if ( is_wp_error( $url ) ) {
			$this->redirect_notice( 'error', $url->get_error_message() );
		}

		if ( 'accounts.google.com' !== wp_parse_url( $url, PHP_URL_HOST ) ) {
			$this->redirect_notice( 'error', __( 'The generated Google authorization address was invalid.', 'tomawesome-review-widgets' ) );
		}

		wp_redirect( esc_url_raw( $url ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Host is verified immediately above.
		exit;
	}

	/**
	 * Completes OAuth authorization.
	 *
	 * @return void
	 */
	public function oauth_callback() {
		$this->require_admin();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Google returns this callback; OAuth::handle_callback() verifies the one-time state value.
		if ( isset( $_GET['error'] ) ) {
			$this->redirect_notice( 'error', sanitize_text_field( wp_unslash( $_GET['error_description'] ?? $_GET['error'] ) ) );
		}

		$code  = sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) );
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$result = $this->oauth->handle_callback( $code, $state );
		$this->redirect_notice( is_wp_error( $result ) ? 'error' : 'connected', is_wp_error( $result ) ? $result->get_error_message() : '' );
	}

	/**
	 * Disconnects Google locally.
	 *
	 * @return void
	 */
	public function disconnect_google() {
		$this->require_admin();
		check_admin_referer( 'tarw_disconnect_google' );
		$this->oauth->disconnect();
		$this->redirect_notice( 'disconnected' );
	}

	/**
	 * Imports a discovered managed location as a source.
	 *
	 * @return void
	 */
	public function add_location() {
		$this->require_admin();
		check_admin_referer( 'tarw_add_location' );

		$title    = sanitize_text_field( wp_unslash( $_POST['location_title'] ?? '' ) );
		$location = $this->resource_name( sanitize_text_field( wp_unslash( $_POST['location_name'] ?? '' ) ), 'locations' );
		$account  = $this->resource_name( sanitize_text_field( wp_unslash( $_POST['account_name'] ?? '' ) ), 'accounts' );
		if ( '' === $title || '' === $location || '' === $account ) {
			$this->redirect_notice( 'error', __( 'Google returned incomplete location information.', 'tomawesome-review-widgets' ) );
		}

		$existing = get_posts(
			array(
				'post_type'      => 'tarw_source',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_tarw_location_name', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Exact identifier lookup on a small administrator-managed source set.
				'meta_value'     => $location, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Paired with the exact source identifier above.
			)
		);
		if ( ! empty( $existing ) ) {
			wp_safe_redirect( get_edit_post_link( $existing[0], 'url' ) );
			exit;
		}

		$source_id = wp_insert_post(
			array(
				'post_type'   => 'tarw_source',
				'post_status' => 'publish',
				'post_title'  => $title,
			),
			true
		);
		if ( is_wp_error( $source_id ) ) {
			$this->redirect_notice( 'error', $source_id->get_error_message() );
		}

		update_post_meta( $source_id, '_tarw_source_type', 'business_profile' );
		update_post_meta( $source_id, '_tarw_account_name', $account );
		update_post_meta( $source_id, '_tarw_location_name', $location );
		update_post_meta( $source_id, '_tarw_business_name', $title );
		update_post_meta( $source_id, '_tarw_review_url', esc_url_raw( wp_unslash( $_POST['review_url'] ?? '' ) ) );
		update_post_meta( $source_id, '_tarw_leave_review_url', esc_url_raw( wp_unslash( $_POST['leave_review_url'] ?? '' ) ) );
		update_post_meta( $source_id, '_tarw_enabled', 1 );

		wp_safe_redirect( get_edit_post_link( $source_id, 'url' ) );
		exit;
	}

	/**
	 * Runs a manual source synchronization.
	 *
	 * @return void
	 */
	public function sync_source() {
		$this->require_admin();
		$source_id = absint( wp_unslash( $_GET['source_id'] ?? 0 ) );
		check_admin_referer( 'tarw_sync_source_' . $source_id );
		$result = $this->sync->sync_source( $source_id );

		$url = add_query_arg(
			array(
				'post'         => $source_id,
				'action'       => 'edit',
				'tarw_notice'  => is_wp_error( $result ) ? 'error' : 'synced',
				'tarw_message' => is_wp_error( $result ) ? rawurlencode( $result->get_error_message() ) : '',
			),
			admin_url( 'post.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Saves one review's privacy-reviewed copy.
	 *
	 * @return void
	 */
	public function save_privacy_review() {
		$this->require_admin();
		$review_id = absint( wp_unslash( $_POST['review_id'] ?? 0 ) );
		check_admin_referer( 'tarw_privacy_review_' . $review_id );
		$this->repository->set_privacy_review(
			$review_id,
			isset( $_POST['privacy_approved'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['privacy_approved'] ) ),
			sanitize_textarea_field( wp_unslash( $_POST['privacy_excerpt'] ?? '' ) )
		);

		$url = add_query_arg(
			array_filter(
				array(
					'post_type'   => 'tarw_widget',
					'page'        => 'tarw-review-library',
					'source_id'   => absint( wp_unslash( $_POST['source_id'] ?? 0 ) ),
					'paged'       => max( 1, absint( wp_unslash( $_POST['paged'] ?? 1 ) ) ),
					'tarw_notice' => 'privacy_saved',
				)
			),
			admin_url( 'edit.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Displays status messages after safe redirects.
	 *
	 * @return void
	 */
	public function notices() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only notice parameters are sanitized and never mutate data.
		if ( ! current_user_can( 'manage_options' ) || empty( $_GET['tarw_notice'] ) ) {
			return;
		}

		$key         = sanitize_key( wp_unslash( $_GET['tarw_notice'] ) );
		$raw_message = isset( $_GET['tarw_message'] ) ? sanitize_text_field( wp_unslash( $_GET['tarw_message'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$messages = array(
			'settings_saved' => __( 'Connection settings saved.', 'tomawesome-review-widgets' ),
			'connected'      => __( 'Google Business Profile connected.', 'tomawesome-review-widgets' ),
			'disconnected'   => __( 'The local Google connection was removed.', 'tomawesome-review-widgets' ),
			'synced'         => __( 'Reviews synchronized successfully.', 'tomawesome-review-widgets' ),
			'privacy_saved'  => __( 'Privacy review saved.', 'tomawesome-review-widgets' ),
		);
		$message  = 'error' === $key
			? sanitize_text_field( rawurldecode( $raw_message ) )
			: ( $messages[ $key ] ?? '' );
		if ( '' === $message ) {
			return;
		}

		$class = 'error' === $key ? 'notice notice-error is-dismissible' : 'notice notice-success is-dismissible';
		echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Adds useful widget list columns.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public function widget_columns( $columns ) {
		$columns['tarw_shortcode'] = __( 'Shortcode', 'tomawesome-review-widgets' );
		$columns['tarw_source']    = __( 'Source', 'tomawesome-review-widgets' );
		$columns['tarw_privacy']   = __( 'Privacy mode', 'tomawesome-review-widgets' );
		return $columns;
	}

	/**
	 * Renders widget list columns.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Widget post ID.
	 * @return void
	 */
	public function widget_column_content( $column, $post_id ) {
		$settings = $this->widget_settings( $post_id );
		if ( 'tarw_shortcode' === $column ) {
			echo '<code>[tomawesome_reviews id=&quot;' . esc_html( $post_id ) . '&quot;]</code>';
		} elseif ( 'tarw_source' === $column ) {
			$source_title = get_the_title( absint( $settings['source_id'] ) );
			echo esc_html( $source_title ? $source_title : '—' );
		} elseif ( 'tarw_privacy' === $column ) {
			echo ! empty( $settings['privacy_mode'] ) ? esc_html__( 'On', 'tomawesome-review-widgets' ) : esc_html__( 'Off', 'tomawesome-review-widgets' );
		}
	}

	/**
	 * Adds useful source list columns.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public function source_columns( $columns ) {
		$columns['tarw_type']  = __( 'Type', 'tomawesome-review-widgets' );
		$columns['tarw_sync']  = __( 'Last sync', 'tomawesome-review-widgets' );
		$columns['tarw_count'] = __( 'Stored reviews', 'tomawesome-review-widgets' );
		return $columns;
	}

	/**
	 * Renders source list columns.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Source post ID.
	 * @return void
	 */
	public function source_column_content( $column, $post_id ) {
		if ( 'tarw_type' === $column ) {
			echo 'places' === get_post_meta( $post_id, '_tarw_source_type', true ) ? esc_html__( 'Places', 'tomawesome-review-widgets' ) : esc_html__( 'Business Profile', 'tomawesome-review-widgets' );
		} elseif ( 'tarw_sync' === $column ) {
			$last_sync = get_post_meta( $post_id, '_tarw_last_sync', true );
			echo esc_html( $last_sync ? $last_sync : '—' );
		} elseif ( 'tarw_count' === $column ) {
			echo esc_html( $this->repository->count( $post_id ) );
		}
	}

	/**
	 * Returns saved widget settings for admin display.
	 *
	 * @param int $post_id Widget post ID.
	 * @return array<string,mixed>
	 */
	private function widget_settings( $post_id ) {
		$stored = get_post_meta( $post_id, '_tarw_widget_settings', true );
		return wp_parse_args(
			is_array( $stored ) ? $stored : array(),
			array(
				'source_id'       => 0,
				'public_heading'  => '',
				'layout'          => 'grid',
				'limit'           => 6,
				'min_rating'      => 4,
				'sort'            => 'newest',
				'text_only'       => 1,
				'max_chars'       => 320,
				'show_avatar'     => 1,
				'show_date'       => 1,
				'show_summary'    => 1,
				'show_read_all'   => 1,
				'show_leave'      => 1,
				'columns_desktop' => 3,
				'columns_tablet'  => 2,
				'columns_mobile'  => 1,
				'privacy_mode'    => 0,
				'style'           => array(),
				'custom_class'    => '',
			)
		);
	}

	/**
	 * Verifies a post-save request.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $nonce_field Nonce field.
	 * @param string $action Nonce action.
	 * @return bool
	 */
	private function can_save( $post_id, $nonce_field, $action ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}
		if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		$nonce = isset( $_POST[ $nonce_field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, $action ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Requires administrator capability.
	 *
	 * @return void
	 */
	private function require_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage review widgets.', 'tomawesome-review-widgets' ) );
		}
	}

	/**
	 * Normalizes a Google account or location resource name.
	 *
	 * @param string $value Input value.
	 * @param string $resource_type Resource prefix.
	 * @return string
	 */
	private function resource_name( $value, $resource_type ) {
		$value = trim( sanitize_text_field( $value ), '/' );
		if ( preg_match( '#(?:^|/)' . preg_quote( $resource_type, '#' ) . '/([A-Za-z0-9_-]+)$#', $value, $matches ) ) {
			return $resource_type . '/' . $matches[1];
		}
		if ( preg_match( '/^[A-Za-z0-9_-]+$/', $value ) ) {
			return $resource_type . '/' . $value;
		}
		return '';
	}

	/**
	 * Shows last synchronization status under a source.
	 *
	 * @param int $source_id Source post ID.
	 * @return void
	 */
	private function source_status( $source_id ) {
		$last_sync = get_post_meta( $source_id, '_tarw_last_sync', true );
		$error     = get_post_meta( $source_id, '_tarw_last_error', true );
		if ( $error ) {
			echo '<p class="tarw-status tarw-status-bad"><strong>' . esc_html__( 'Last error:', 'tomawesome-review-widgets' ) . '</strong> ' . esc_html( $error ) . '</p>';
		} elseif ( $last_sync ) {
			$last_sync_label = sprintf(
				/* translators: %s: Date and time of the last successful synchronization. */
				__( 'Last successful sync: %s UTC', 'tomawesome-review-widgets' ),
				$last_sync
			);
			echo '<p class="tarw-status tarw-status-good">' . esc_html( $last_sync_label ) . '</p>';
		}
	}

	/**
	 * Renders one item in the onboarding progress checklist.
	 *
	 * @param bool   $complete Whether the step is complete.
	 * @param string $label Step label.
	 * @param string $description Explanatory text.
	 * @return void
	 */
	private function setup_status_item( $complete, $label, $description ) {
		$status = $complete ? __( 'Complete', 'tomawesome-review-widgets' ) : __( 'Not complete', 'tomawesome-review-widgets' );
		$class  = $complete ? 'tarw-setup-complete' : 'tarw-setup-incomplete';
		$icon   = $complete ? 'yes-alt' : 'marker';
		?>
		<li class="<?php echo esc_attr( $class ); ?>">
			<span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
			<span><strong><?php echo esc_html( $label ); ?></strong><br><span class="description"><?php echo esc_html( $description ); ?></span></span>
			<span class="tarw-setup-state"><?php echo esc_html( $status ); ?></span>
		</li>
		<?php
	}

	/**
	 * Redirects back to settings with a notice.
	 *
	 * @param string $notice Notice key.
	 * @param string $message Optional message.
	 * @return void
	 */
	private function redirect_notice( $notice, $message = '' ) {
		$url = add_query_arg(
			array_filter(
				array(
					'post_type'    => 'tarw_widget',
					'page'         => 'tarw-settings',
					'tarw_notice'  => sanitize_key( $notice ),
					'tarw_message' => '' === $message ? '' : rawurlencode( sanitize_text_field( $message ) ),
				)
			),
			admin_url( 'edit.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
