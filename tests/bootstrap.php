<?php
/**
 * Minimal WordPress-function stubs for isolated unit tests.
 *
 * @package TomAwesomeReviewWidgets
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

if ( ! function_exists( '__' ) ) {
	/**
	 * Returns untranslated test strings.
	 *
	 * @param string $text Source text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	/**
	 * Approximates WordPress textarea sanitization for isolated tests.
	 *
	 * @param string $text Input text.
	 * @return string
	 */
	function sanitize_textarea_field( $text ) {
		$text = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
		return trim( strip_tags( $text ) );
	}
}

require_once dirname( __DIR__ ) . '/includes/class-privacy.php';
