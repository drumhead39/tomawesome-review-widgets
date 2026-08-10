<?php
/**
 * Front-end shortcode renderer.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders independently configured review widgets.
 */
final class Shortcode {

	/**
	 * Review persistence service.
	 *
	 * @var Review_Repository
	 */
	private $repository;

	/**
	 * Constructor.
	 *
	 * @param Review_Repository $repository Repository service.
	 */
	public function __construct( Review_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Registers the shortcode and cleanup hook.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'tomawesome_reviews', array( $this, 'render' ) );
		add_action( 'before_delete_post', array( $this, 'delete_source_reviews' ) );
	}

	/**
	 * Deletes cached API content when its source is permanently removed.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function delete_source_reviews( $post_id ) {
		if ( 'tarw_source' === get_post_type( $post_id ) ) {
			$this->repository->delete_source( $post_id );
		}
	}

	/**
	 * Renders one saved widget.
	 *
	 * @param array<string,mixed> $attributes Shortcode attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		$attributes = shortcode_atts( array( 'id' => 0 ), $attributes, 'tomawesome_reviews' );
		$widget_id  = absint( $attributes['id'] );

		if ( ! $widget_id || 'tarw_widget' !== get_post_type( $widget_id ) || 'publish' !== get_post_status( $widget_id ) ) {
			return current_user_can( 'manage_options' )
				? '<p class="tarw-message">' . esc_html__( 'Review widget not found or not published.', 'tomawesome-review-widgets' ) . '</p>'
				: '';
		}

		$settings  = $this->widget_settings( $widget_id );
		$source_id = absint( $settings['source_id'] );
		if ( ! $source_id || 'tarw_source' !== get_post_type( $source_id ) ) {
			return current_user_can( 'manage_options' )
				? '<p class="tarw-message">' . esc_html__( 'Choose a review source in this widget’s settings.', 'tomawesome-review-widgets' ) . '</p>'
				: '';
		}

		$reviews = $this->repository->get_for_widget( $source_id, $settings );
		if ( empty( $reviews ) ) {
			if ( ! empty( $settings['privacy_mode'] ) && current_user_can( 'manage_options' ) ) {
				return '<p class="tarw-message">' . esc_html__( 'Healthcare Privacy Mode is on, but no synchronized reviews have approved privacy copy.', 'tomawesome-review-widgets' ) . '</p>';
			}
			return '';
		}

		wp_enqueue_style( 'tarw-frontend', TARW_URL . 'assets/css/frontend.css', array(), TARW_VERSION );
		wp_enqueue_script( 'tarw-frontend', TARW_URL . 'assets/js/frontend.js', array(), TARW_VERSION, true );

		return $this->widget_markup( $widget_id, $source_id, $settings, $reviews );
	}

	/**
	 * Returns sanitized widget settings.
	 *
	 * @param int $widget_id Widget post ID.
	 * @return array<string,mixed>
	 */
	private function widget_settings( $widget_id ) {
		$stored = get_post_meta( $widget_id, '_tarw_widget_settings', true );
		$stored = is_array( $stored ) ? $stored : array();
		return wp_parse_args(
			$stored,
			array(
				'source_id'       => 0,
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
				'custom_class'    => '',
			)
		);
	}

	/**
	 * Builds complete widget markup.
	 *
	 * @param int                 $widget_id Widget post ID.
	 * @param int                 $source_id Source post ID.
	 * @param array<string,mixed> $settings Widget settings.
	 * @param array<int,object>   $reviews Review records.
	 * @return string
	 */
	private function widget_markup( $widget_id, $source_id, array $settings, array $reviews ) {
		$layout       = in_array( $settings['layout'], array( 'grid', 'list', 'carousel', 'featured' ), true ) ? $settings['layout'] : 'grid';
		$privacy_mode = ! empty( $settings['privacy_mode'] );
		$classes      = array( 'tarw-widget', 'tarw-layout-' . $layout );
		foreach ( preg_split( '/\s+/', (string) $settings['custom_class'] ) as $class ) {
			$class = sanitize_html_class( $class );
			if ( '' !== $class ) {
				$classes[] = $class;
			}
		}

		$columns     = sprintf(
			'--tarw-columns-desktop:%d;--tarw-columns-tablet:%d;--tarw-columns-mobile:%d;',
			min( 4, max( 1, absint( $settings['columns_desktop'] ) ) ),
			min( 3, max( 1, absint( $settings['columns_tablet'] ) ) ),
			min( 2, max( 1, absint( $settings['columns_mobile'] ) ) )
		);
		$source_name = get_the_title( $source_id );
		/* translators: %s: Review source or business name. */
		$aria_label = sprintf( __( 'Reviews for %s', 'tomawesome-review-widgets' ), $source_name );

		ob_start();
		?>
		<section class="<?php echo esc_attr( implode( ' ', array_unique( $classes ) ) ); ?>" style="<?php echo esc_attr( $columns ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>" data-tarw-widget="<?php echo esc_attr( $widget_id ); ?>">
			<?php if ( ! empty( $settings['show_summary'] ) ) : ?>
				<?php echo $this->summary_markup( $source_id, $source_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<div class="tarw-reviews"<?php echo 'carousel' === $layout ? ' role="region" aria-roledescription="carousel"' : ''; ?>>
				<?php foreach ( $reviews as $review ) : ?>
					<?php echo $this->review_markup( $review, $settings, $privacy_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>

			<?php if ( 'carousel' === $layout && count( $reviews ) > 1 ) : ?>
				<div class="tarw-carousel-controls">
					<button type="button" class="tarw-carousel-prev" aria-label="<?php esc_attr_e( 'Previous review', 'tomawesome-review-widgets' ); ?>">&larr;</button>
					<button type="button" class="tarw-carousel-next" aria-label="<?php esc_attr_e( 'Next review', 'tomawesome-review-widgets' ); ?>">&rarr;</button>
				</div>
			<?php endif; ?>

			<?php echo $this->footer_markup( $source_id, $settings, $privacy_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $privacy_mode ) : ?>
				<p class="tarw-privacy-note"><?php esc_html_e( 'Reviewer identity and identifying details have been suppressed by this site.', 'tomawesome-review-widgets' ); ?></p>
			<?php endif; ?>
		</section>
		<?php
		return (string) apply_filters( 'tarw_widget_html', ob_get_clean(), $widget_id, $source_id, $settings, $reviews );
	}

	/**
	 * Builds one review card.
	 *
	 * @param object              $review Review record.
	 * @param array<string,mixed> $settings Widget settings.
	 * @param bool                $privacy_mode Whether privacy mode is enforced.
	 * @return string
	 */
	private function review_markup( $review, array $settings, $privacy_mode ) {
		$name       = $privacy_mode ? Privacy::anonymous_label() : (string) $review->reviewer_name;
		$text       = $privacy_mode ? (string) $review->privacy_excerpt : (string) $review->review_text;
		$profile    = $privacy_mode ? '' : (string) $review->reviewer_profile_url;
		$show_image = ! $privacy_mode && ! empty( $settings['show_avatar'] ) && ! empty( $review->reviewer_photo_url );
		$show_date  = ! $privacy_mode && ! empty( $settings['show_date'] ) && ! empty( $review->create_time );
		$max_chars  = min( 2000, max( 0, absint( $settings['max_chars'] ) ) );
		$short_text = $max_chars ? wp_html_excerpt( $text, $max_chars, '…' ) : $text;
		$trimmed    = $short_text !== $text;

		ob_start();
		?>
		<article class="tarw-review">
			<header class="tarw-review-header">
				<?php if ( $show_image ) : ?>
					<img class="tarw-avatar" src="<?php echo esc_url( $review->reviewer_photo_url ); ?>" alt="" width="48" height="48" loading="lazy" referrerpolicy="no-referrer">
				<?php endif; ?>
				<div class="tarw-reviewer">
					<?php if ( '' !== $profile ) : ?>
						<a href="<?php echo esc_url( $profile ); ?>" rel="nofollow noopener noreferrer" target="_blank"><?php echo esc_html( $name ); ?></a>
					<?php else : ?>
						<span><?php echo esc_html( $name ); ?></span>
					<?php endif; ?>
					<?php if ( $show_date ) : ?>
						<time datetime="<?php echo esc_attr( mysql2date( 'c', $review->create_time, false ) ); ?>"><?php echo esc_html( mysql2date( get_option( 'date_format' ), $review->create_time, false ) ); ?></time>
					<?php endif; ?>
				</div>
			</header>

			<?php echo $this->stars_markup( absint( $review->rating ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( '' !== $text ) : ?>
				<div class="tarw-review-text">
					<span class="tarw-review-short"><?php echo nl2br( esc_html( $short_text ) ); ?></span>
					<?php if ( $trimmed ) : ?>
						<span class="tarw-review-full" hidden><?php echo nl2br( esc_html( $text ) ); ?></span>
						<button type="button" class="tarw-read-more" aria-expanded="false" data-more="<?php esc_attr_e( 'Read more', 'tomawesome-review-widgets' ); ?>" data-less="<?php esc_attr_e( 'Show less', 'tomawesome-review-widgets' ); ?>"><?php esc_html_e( 'Read more', 'tomawesome-review-widgets' ); ?></button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</article>
		<?php
		return ob_get_clean();
	}

	/**
	 * Builds accessible star markup.
	 *
	 * @param int $rating Rating from 0 to 5.
	 * @return string
	 */
	private function stars_markup( $rating ) {
		$rating = min( 5, max( 0, $rating ) );
		/* translators: %d: Rating out of five stars. */
		$star_label = sprintf( _n( '%d out of 5 star', '%d out of 5 stars', $rating, 'tomawesome-review-widgets' ), $rating );
		return sprintf(
			'<div class="tarw-stars" aria-label="%1$s"><span aria-hidden="true">%2$s%3$s</span></div>',
			esc_attr( $star_label ),
			esc_html( str_repeat( '★', $rating ) ),
			esc_html( str_repeat( '☆', 5 - $rating ) )
		);
	}

	/**
	 * Builds the optional business summary.
	 *
	 * @param int    $source_id Source post ID.
	 * @param string $source_name Source name.
	 * @return string
	 */
	private function summary_markup( $source_id, $source_name ) {
		$rating = (float) get_post_meta( $source_id, '_tarw_rating', true );
		$total  = absint( get_post_meta( $source_id, '_tarw_total_review_count', true ) );

		if ( $rating <= 0 ) {
			return '';
		}
		/* translators: %d: Total number of Google reviews. */
		$review_count_label = sprintf( _n( '%d Google review', '%d Google reviews', $total, 'tomawesome-review-widgets' ), $total );

		return sprintf(
			'<header class="tarw-summary"><strong>%1$s</strong><span>%2$s</span><span>%3$s</span></header>',
			esc_html( $source_name ),
			esc_html( number_format_i18n( $rating, 1 ) . ' / 5' ),
			esc_html( $review_count_label )
		);
	}

	/**
	 * Builds attribution and configured calls to action.
	 *
	 * @param int                 $source_id Source post ID.
	 * @param array<string,mixed> $settings Widget settings.
	 * @param bool                $privacy_mode Whether privacy mode is enforced.
	 * @return string
	 */
	private function footer_markup( $source_id, array $settings, $privacy_mode ) {
		$reviews_url = esc_url( get_post_meta( $source_id, '_tarw_review_url', true ) );
		$leave_url   = esc_url( get_post_meta( $source_id, '_tarw_leave_review_url', true ) );

		ob_start();
		?>
		<footer class="tarw-footer">
			<span class="tarw-attribution"><?php esc_html_e( 'Reviews from Google', 'tomawesome-review-widgets' ); ?></span>
			<?php if ( ! $privacy_mode ) : ?>
			<span class="tarw-actions">
				<?php if ( ! empty( $settings['show_read_all'] ) && $reviews_url ) : ?>
					<a href="<?php echo esc_url( $reviews_url ); ?>" target="_blank" rel="nofollow noopener noreferrer"><?php esc_html_e( 'Read all reviews', 'tomawesome-review-widgets' ); ?></a>
				<?php endif; ?>
				<?php if ( ! empty( $settings['show_leave'] ) && $leave_url ) : ?>
					<a href="<?php echo esc_url( $leave_url ); ?>" target="_blank" rel="nofollow noopener noreferrer"><?php esc_html_e( 'Leave a review', 'tomawesome-review-widgets' ); ?></a>
				<?php endif; ?>
			</span>
			<?php endif; ?>
		</footer>
		<?php
		return ob_get_clean();
	}
}
