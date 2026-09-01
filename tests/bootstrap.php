<?php
/**
 * Minimal WordPress-function stubs for isolated unit tests.
 *
 * @package TomAwesomeReviewWidgets
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'WP_UNINSTALL_PLUGIN', 'tomawesome-review-widgets' );

/**
 * Returns a deterministic UTC timestamp for repository tests.
 *
 * @return string
 */
function current_time() {
	return '2026-09-01 12:00:00';
}

/**
 * Keeps fixture URLs intact; WordPress URL sanitization is not under test.
 *
 * @param string $url Fixture URL.
 * @return string
 */
function esc_url_raw( $url ) {
	return (string) $url;
}

/**
 * Reads an isolated option fixture.
 *
 * @param string $name Option name.
 * @param mixed  $default Default value.
 * @return mixed
 */
function get_option( $name, $default = false ) {
	return $GLOBALS['tarw_test_options'][ $name ] ?? $default;
}

/**
 * Returns plugin-owned post fixtures for uninstall tests.
 *
 * @return array<int,int>
 */
function get_posts() {
	return $GLOBALS['tarw_test_post_ids'] ?? array();
}

/**
 * Records post removal without touching a WordPress site.
 *
 * @param int  $post_id Post ID.
 * @param bool $force_delete Whether deletion bypasses trash.
 * @return void
 */
function wp_delete_post( $post_id, $force_delete = false ) {
	$GLOBALS['tarw_test_deleted_posts'][] = array( $post_id, $force_delete );
}

/**
 * Records deletion of isolated options.
 *
 * @param string $name Option name.
 * @return void
 */
function delete_option( $name ) {
	$GLOBALS['tarw_test_deleted_options'][] = $name;
	unset( $GLOBALS['tarw_test_options'][ $name ] );
}

/**
 * Records scheduled-event removal.
 *
 * @param string $hook Scheduled hook.
 * @return void
 */
function wp_clear_scheduled_hook( $hook ) {
	$GLOBALS['tarw_test_cleared_hooks'][] = $hook;
}

/** Returns whether the current fixture represents a revision. */
function wp_is_post_revision() {
	return $GLOBALS['tarw_test_is_revision'] ?? false;
}

/** Returns the fixture's administrator permission. */
function current_user_can( $capability ) {
	return 'manage_options' === $capability && ! empty( $GLOBALS['tarw_test_is_admin'] );
}

/** Removes slashes from a test nonce. */
function wp_unslash( $value ) {
	return stripslashes( $value );
}

/** Records nonce checks and models WordPress's two valid time windows. */
function wp_verify_nonce( $nonce, $action ) {
	$GLOBALS['tarw_test_nonce_calls'][] = array( $nonce, $action );
	if ( 'tarw_save_widget' !== $action ) {
		return false;
	}
	if ( 'valid-current' === $nonce ) {
		return 1;
	}
	return 'valid-previous' === $nonce ? 2 : false;
}

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
require_once dirname( __DIR__ ) . '/includes/class-review-repository.php';
require_once dirname( __DIR__ ) . '/includes/class-admin.php';
require_once __DIR__ . '/DatabaseDouble.php';
