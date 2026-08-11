<?php
/**
 * Visitor-facing widget display helpers.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves public widget labels without exposing administrator-only titles.
 */
final class Widget_Display {

	/**
	 * Resolves whether privacy mode may be used for a source.
	 *
	 * Places reviews must retain Google Maps author attribution, so the
	 * de-identifying privacy presentation is available only to managed Business
	 * Profile sources.
	 *
	 * @param bool $requested Whether the widget requested privacy mode.
	 * @param bool $is_places Whether the selected source uses Places API (New).
	 * @return bool
	 */
	public static function privacy_mode( $requested, $is_places ) {
		return ! $is_places && (bool) $requested;
	}

	/**
	 * Determines whether an available reviewer photo must be displayed.
	 *
	 * @param bool   $is_places Whether the selected source uses Places API (New).
	 * @param bool   $privacy_mode Whether privacy mode is active.
	 * @param bool   $show_avatar Saved optional photo setting.
	 * @param string $photo_url Google-provided reviewer photo URL.
	 * @return bool
	 */
	public static function show_reviewer_photo( $is_places, $privacy_mode, $show_avatar, $photo_url ) {
		return ! $privacy_mode && '' !== trim( (string) $photo_url ) && ( $is_places || (bool) $show_avatar );
	}

	/**
	 * Describes the Places sample and every widget-level review filter.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return string
	 */
	public static function places_filter_notice( array $settings ) {
		$limit      = min( 5, max( 1, absint( $settings['limit'] ?? 5 ) ) );
		$min_rating = min( 5, max( 1, absint( $settings['min_rating'] ?? 1 ) ) );
		$order      = array(
			'newest'  => __( 'newest first', 'tomawesome-review-widgets' ),
			'oldest'  => __( 'oldest first', 'tomawesome-review-widgets' ),
			'highest' => __( 'highest rated first', 'tomawesome-review-widgets' ),
			'random'  => __( 'in random order', 'tomawesome-review-widgets' ),
		);
		$sort       = $order[ (string) ( $settings['sort'] ?? '' ) ] ?? $order['newest'];
		$text_rule  = ! empty( $settings['text_only'] )
			? __( ' Written text is required.', 'tomawesome-review-widgets' )
			: '';

		/* translators: 1: Maximum displayed reviews. 2: Minimum star rating. 3: Sort description. 4: Optional written-text filter sentence. */
		$format = __( 'Google Maps supplies up to five reviews selected by relevance. This widget displays up to %1$d reviews rated %2$d stars or higher, ordered %3$s.%4$s', 'tomawesome-review-widgets' );

		return sprintf(
			$format,
			$limit,
			$min_rating,
			$sort,
			$text_rule
		);
	}

	/**
	 * Returns blank defaults for the optional visual-style controls.
	 *
	 * @return array<string,string>
	 */
	public static function style_defaults() {
		return array(
			'widget_background'    => '',
			'widget_text'          => '',
			'accent'               => '',
			'stars'                => '',
			'widget_border'        => '',
			'card_background'      => '',
			'card_border'          => '',
			'widget_margin'        => '',
			'widget_padding'       => '',
			'widget_border_width'  => '',
			'widget_border_radius' => '',
			'card_padding'         => '',
			'card_border_radius'   => '',
		);
	}

	/**
	 * Chooses the public heading for a review widget.
	 *
	 * @param string $custom_heading Optional per-widget heading.
	 * @param string $google_name Synchronized business name from Google.
	 * @return string
	 */
	public static function business_name( $custom_heading, $google_name ) {
		$custom_heading = trim( sanitize_text_field( (string) $custom_heading ) );
		if ( '' !== $custom_heading ) {
			return $custom_heading;
		}

		$google_name = trim( sanitize_text_field( (string) $google_name ) );
		if ( '' !== $google_name ) {
			return $google_name;
		}

		return __( 'Google Reviews', 'tomawesome-review-widgets' );
	}

	/**
	 * Converts validated visual controls to scoped CSS custom properties.
	 *
	 * Invalid or empty values are omitted so existing widgets keep the plugin
	 * and active theme defaults.
	 *
	 * @param array<string,mixed> $style Saved visual-style controls.
	 * @return string
	 */
	public static function inline_style( array $style ) {
		$declarations = array();
		$colors       = array(
			'widget_background' => '--tarw-widget-background',
			'widget_text'       => '--tarw-widget-text',
			'accent'            => '--tarw-accent',
			'stars'             => '--tarw-star',
			'widget_border'     => '--tarw-widget-border',
			'card_background'   => '--tarw-card-background',
			'card_border'       => '--tarw-card-border',
		);
		foreach ( $colors as $key => $property ) {
			$value = trim( (string) ( $style[ $key ] ?? '' ) );
			if ( preg_match( '/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $value ) ) {
				$declarations[] = $property . ':' . strtolower( $value );
			}
		}

		$pixels = array(
			'widget_margin'        => array( '--tarw-widget-margin', 300 ),
			'widget_padding'       => array( '--tarw-widget-padding', 200 ),
			'widget_border_width'  => array( '--tarw-widget-border-width', 20 ),
			'widget_border_radius' => array( '--tarw-widget-border-radius', 100 ),
			'card_padding'         => array( '--tarw-card-padding', 100 ),
			'card_border_radius'   => array( '--tarw-card-border-radius', 100 ),
		);
		foreach ( $pixels as $key => $property ) {
			$value = trim( (string) ( $style[ $key ] ?? '' ) );
			if ( preg_match( '/^\d+$/', $value ) ) {
				$declarations[] = $property[0] . ':' . min( $property[1], absint( $value ) ) . 'px';
			}
		}

		return empty( $declarations ) ? '' : implode( ';', $declarations ) . ';';
	}

	/**
	 * Prevents instantiation.
	 */
	private function __construct() {}
}
