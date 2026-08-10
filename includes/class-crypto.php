<?php
/**
 * Local secret encryption.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Encrypts OAuth tokens and API credentials with a site-specific key.
 */
final class Crypto {

	/**
	 * Cipher identifier stored with encrypted values.
	 */
	const PREFIX = 'tarw:v1:';

	/**
	 * Tests whether secure encryption is available.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' );
	}

	/**
	 * Encrypts a secret.
	 *
	 * @param string $plaintext Secret value.
	 * @return string|\WP_Error
	 */
	public static function encrypt( $plaintext ) {
		if ( '' === $plaintext ) {
			return '';
		}

		if ( ! self::is_available() ) {
			return new \WP_Error(
				'tarw_crypto_unavailable',
				__( 'OpenSSL is required to store Google credentials securely.', 'tomawesome-review-widgets' )
			);
		}

		try {
			$iv = random_bytes( 12 );
		} catch ( \Exception $exception ) {
			return new \WP_Error(
				'tarw_random_failed',
				__( 'A cryptographically secure random value could not be generated.', 'tomawesome-review-widgets' )
			);
		}

		$tag        = '';
		$ciphertext = openssl_encrypt(
			$plaintext,
			'aes-256-gcm',
			self::key(),
			OPENSSL_RAW_DATA,
			$iv,
			$tag
		);

		if ( false === $ciphertext ) {
			return new \WP_Error(
				'tarw_encrypt_failed',
				__( 'The credential could not be encrypted.', 'tomawesome-review-widgets' )
			);
		}

		return self::PREFIX . base64_encode( $iv . $tag . $ciphertext ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encoding this plugin's authenticated encryption envelope for storage.
	}

	/**
	 * Decrypts a stored secret.
	 *
	 * @param string $encrypted Encrypted value.
	 * @return string
	 */
	public static function decrypt( $encrypted ) {
		if ( '' === $encrypted || 0 !== strpos( $encrypted, self::PREFIX ) || ! self::is_available() ) {
			return '';
		}

		$decoded = base64_decode( substr( $encrypted, strlen( self::PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decoding this plugin's authenticated encryption envelope.
		if ( false === $decoded || strlen( $decoded ) < 29 ) {
			return '';
		}

		$iv         = substr( $decoded, 0, 12 );
		$tag        = substr( $decoded, 12, 16 );
		$ciphertext = substr( $decoded, 28 );
		$plaintext  = openssl_decrypt(
			$ciphertext,
			'aes-256-gcm',
			self::key(),
			OPENSSL_RAW_DATA,
			$iv,
			$tag
		);

		return false === $plaintext ? '' : $plaintext;
	}

	/**
	 * Derives an encryption key from WordPress salts.
	 *
	 * @return string
	 */
	private static function key() {
		return hash( 'sha256', wp_salt( 'auth' ) . '|tomawesome-review-widgets', true );
	}
}
