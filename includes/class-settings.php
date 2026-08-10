<?php
/**
 * Plugin settings and encrypted credentials.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Provides one controlled interface to plugin options.
 */
final class Settings {

	/**
	 * WordPress option name.
	 */
	const OPTION = 'tarw_settings';

	/**
	 * Gets all non-secret settings.
	 *
	 * @return array<string,mixed>
	 */
	public function all() {
		$stored = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), $this->defaults() );
	}

	/**
	 * Gets one setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function get( $key, $fallback = null ) {
		$settings = $this->all();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
	}

	/**
	 * Gets a decrypted setting.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	public function get_secret( $key ) {
		$value = $this->get( $key, '' );
		return is_string( $value ) ? Crypto::decrypt( $value ) : '';
	}

	/**
	 * Updates regular and secret settings.
	 *
	 * Empty secret inputs retain their existing value.
	 *
	 * @param array<string,mixed> $values Values to store.
	 * @param string[]            $secret_keys Secret setting keys.
	 * @return true|\WP_Error
	 */
	public function update( array $values, array $secret_keys = array() ) {
		$settings = $this->all();

		foreach ( $values as $key => $value ) {
			if ( in_array( $key, $secret_keys, true ) ) {
				if ( '' === $value ) {
					continue;
				}

				$encrypted = Crypto::encrypt( (string) $value );
				if ( is_wp_error( $encrypted ) ) {
					return $encrypted;
				}

				$settings[ $key ] = $encrypted;
				continue;
			}

			$settings[ $key ] = $value;
		}

		update_option( self::OPTION, $settings, false );
		return true;
	}

	/**
	 * Deletes a secret without changing other settings.
	 *
	 * @param string $key Secret key.
	 * @return void
	 */
	public function delete( $key ) {
		$settings = $this->all();
		unset( $settings[ $key ] );
		update_option( self::OPTION, $settings, false );
	}

	/**
	 * Default values.
	 *
	 * @return array<string,mixed>
	 */
	private function defaults() {
		return array(
			'google_client_id'     => '',
			'google_client_secret' => '',
			'google_token'         => '',
			'places_api_key'       => '',
			'delete_on_uninstall'  => 0,
		);
	}
}
