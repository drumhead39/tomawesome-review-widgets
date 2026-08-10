<?php
/**
 * Google OAuth 2.0 flow.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Connects a WordPress administrator's Google Business Profile account.
 */
final class OAuth {

	/**
	 * Google Business Profile OAuth scope.
	 */
	const SCOPE = 'https://www.googleapis.com/auth/business.manage';

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings service.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Returns the exact redirect URI users must register in Google Cloud.
	 *
	 * @return string
	 */
	public function redirect_uri() {
		return admin_url( 'admin-post.php?action=tarw_oauth_callback' );
	}

	/**
	 * Creates a time-limited Google authorization URL.
	 *
	 * @return string|\WP_Error
	 */
	public function authorization_url() {
		$client_id     = $this->settings->get_secret( 'google_client_id' );
		$client_secret = $this->settings->get_secret( 'google_client_secret' );

		if ( '' === $client_id || '' === $client_secret ) {
			return new \WP_Error(
				'tarw_missing_oauth_credentials',
				__( 'Save a Google OAuth client ID and client secret before connecting.', 'tomawesome-review-widgets' )
			);
		}

		$state = wp_generate_password( 48, false, false );
		set_transient( $this->state_key(), hash( 'sha256', $state ), 10 * MINUTE_IN_SECONDS );

		return add_query_arg(
			array(
				'client_id'              => $client_id,
				'redirect_uri'           => $this->redirect_uri(),
				'response_type'          => 'code',
				'scope'                  => self::SCOPE,
				'access_type'            => 'offline',
				'prompt'                 => 'consent',
				'include_granted_scopes' => 'true',
				'state'                  => $state,
			),
			'https://accounts.google.com/o/oauth2/v2/auth'
		);
	}

	/**
	 * Exchanges an authorization code for tokens.
	 *
	 * @param string $code OAuth authorization code.
	 * @param string $state Returned state value.
	 * @return true|\WP_Error
	 */
	public function handle_callback( $code, $state ) {
		$expected = get_transient( $this->state_key() );
		delete_transient( $this->state_key() );

		if ( ! is_string( $expected ) || ! hash_equals( $expected, hash( 'sha256', $state ) ) ) {
			return new \WP_Error(
				'tarw_invalid_oauth_state',
				__( 'The Google authorization request expired or could not be verified. Try connecting again.', 'tomawesome-review-widgets' )
			);
		}

		$response = wp_safe_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body'    => array(
					'code'          => $code,
					'client_id'     => $this->settings->get_secret( 'google_client_id' ),
					'client_secret' => $this->settings->get_secret( 'google_client_secret' ),
					'redirect_uri'  => $this->redirect_uri(),
					'grant_type'    => 'authorization_code',
				),
			)
		);

		$token = $this->parse_token_response( $response );
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		return $this->store_token( $token );
	}

	/**
	 * Gets a valid access token, refreshing it when needed.
	 *
	 * @return string|\WP_Error
	 */
	public function access_token() {
		$token = $this->token();

		if ( empty( $token['access_token'] ) ) {
			return new \WP_Error(
				'tarw_google_not_connected',
				__( 'Connect a Google Business Profile account first.', 'tomawesome-review-widgets' )
			);
		}

		$expires_at = isset( $token['expires_at'] ) ? absint( $token['expires_at'] ) : 0;
		if ( $expires_at > time() + 60 ) {
			return (string) $token['access_token'];
		}

		return $this->refresh( $token );
	}

	/**
	 * Reports whether a refreshable Google connection exists.
	 *
	 * @return bool
	 */
	public function is_connected() {
		$token = $this->token();
		return ! empty( $token['access_token'] ) && ! empty( $token['refresh_token'] );
	}

	/**
	 * Removes locally stored OAuth tokens.
	 *
	 * @return void
	 */
	public function disconnect() {
		$this->settings->delete( 'google_token' );
	}

	/**
	 * Refreshes an expired access token.
	 *
	 * @param array<string,mixed> $current Existing token data.
	 * @return string|\WP_Error
	 */
	private function refresh( array $current ) {
		if ( empty( $current['refresh_token'] ) ) {
			return new \WP_Error(
				'tarw_refresh_token_missing',
				__( 'Google did not provide a refresh token. Disconnect and reconnect the account.', 'tomawesome-review-widgets' )
			);
		}

		$response = wp_safe_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 20,
				'body'    => array(
					'client_id'     => $this->settings->get_secret( 'google_client_id' ),
					'client_secret' => $this->settings->get_secret( 'google_client_secret' ),
					'refresh_token' => (string) $current['refresh_token'],
					'grant_type'    => 'refresh_token',
				),
			)
		);

		$fresh = $this->parse_token_response( $response );
		if ( is_wp_error( $fresh ) ) {
			return $fresh;
		}

		$fresh['refresh_token'] = (string) $current['refresh_token'];
		$stored                 = $this->store_token( $fresh );
		return is_wp_error( $stored ) ? $stored : (string) $fresh['access_token'];
	}

	/**
	 * Parses a token endpoint response.
	 *
	 * @param array|\WP_Error $response WordPress HTTP response.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function parse_token_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 || ! is_array( $data ) || empty( $data['access_token'] ) ) {
			$message = is_array( $data ) && ! empty( $data['error_description'] )
				? sanitize_text_field( $data['error_description'] )
				: __( 'Google did not accept the OAuth token request.', 'tomawesome-review-widgets' );

			return new \WP_Error( 'tarw_oauth_token_failed', $message );
		}

		$data['expires_at'] = time() + max( 60, absint( $data['expires_in'] ?? 3600 ) );
		unset( $data['expires_in'] );
		return $data;
	}

	/**
	 * Stores encrypted token JSON.
	 *
	 * @param array<string,mixed> $token Token data.
	 * @return true|\WP_Error
	 */
	private function store_token( array $token ) {
		$encoded = wp_json_encode( $token );
		if ( false === $encoded ) {
			return new \WP_Error( 'tarw_token_encode_failed', __( 'The Google token could not be encoded.', 'tomawesome-review-widgets' ) );
		}

		return $this->settings->update( array( 'google_token' => $encoded ), array( 'google_token' ) );
	}

	/**
	 * Gets decoded token data.
	 *
	 * @return array<string,mixed>
	 */
	private function token() {
		$decoded = json_decode( $this->settings->get_secret( 'google_token' ), true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Returns the user-specific transient key.
	 *
	 * @return string
	 */
	private function state_key() {
		return 'tarw_oauth_state_' . get_current_user_id();
	}
}
