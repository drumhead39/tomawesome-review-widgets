<?php
/**
 * Healthcare Privacy Mode helpers.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Applies deliberately conservative display transformations.
 */
final class Privacy {

	/**
	 * Generic identity used instead of an imported reviewer's name.
	 *
	 * @return string
	 */
	public static function anonymous_label() {
		return __( 'Google Reviewer', 'tomawesome-review-widgets' );
	}

	/**
	 * Sanitizes an administrator-reviewed excerpt.
	 *
	 * @param string $excerpt Proposed privacy-safe text.
	 * @return string
	 */
	public static function sanitize_excerpt( $excerpt ) {
		$excerpt = sanitize_textarea_field( $excerpt );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $excerpt, 0, 2000 );
		}
		return substr( $excerpt, 0, 2000 );
	}

	/**
	 * Confirms the minimum privacy workflow requirements for display.
	 *
	 * @param object|array<string,mixed> $review Review record.
	 * @return bool
	 */
	public static function is_ready( $review ) {
		$data = is_object( $review ) ? get_object_vars( $review ) : $review;
		return ! empty( $data['privacy_approved'] ) && ! empty( trim( (string) ( $data['privacy_excerpt'] ?? '' ) ) );
	}

	/**
	 * Prevents instantiation.
	 */
	private function __construct() {}
}
