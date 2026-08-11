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

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Approximates WordPress single-line text sanitization for isolated tests.
	 *
	 * @param string $text Input text.
	 * @return string
	 */
	function sanitize_text_field( $text ) {
		return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $text ) ) );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Returns a non-negative integer.
	 *
	 * @param mixed $value Input value.
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WordPress error object for isolated tests.
	 */
	class WP_Error {

		/**
		 * Error code.
		 *
		 * @var string
		 */
		private $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Error data.
		 *
		 * @var mixed
		 */
		private $data;

		/**
		 * Constructor.
		 *
		 * @param string $code Error code.
		 * @param string $message Error message.
		 * @param mixed  $data Error data.
		 */
		public function __construct( $code, $message, $data = null ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * Returns the error code.
		 *
		 * @return string
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * Returns the error message.
		 *
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}

		/**
		 * Returns the error data.
		 *
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}

require_once dirname( __DIR__ ) . '/includes/class-privacy.php';
require_once dirname( __DIR__ ) . '/includes/class-widget-display.php';
require_once dirname( __DIR__ ) . '/includes/class-business-profile-client.php';
