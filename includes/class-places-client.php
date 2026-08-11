<?php
/**
 * Google Places API (New) client.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves public Place details and Google's selected review sample.
 */
final class Places_Client {

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
	 * Gets one Place and its review sample.
	 *
	 * @param string $place_id Google Place ID.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_place( $place_id ) {
		$api_key = $this->settings->get_secret( 'places_api_key' );
		if ( '' === $api_key ) {
			return new \WP_Error( 'tarw_places_key_missing', __( 'Save a Places API key before synchronizing this source.', 'tomawesome-review-widgets' ) );
		}

		$place_id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $place_id );
		if ( '' === $place_id ) {
			return new \WP_Error( 'tarw_place_id_invalid', __( 'The Google Place ID is invalid.', 'tomawesome-review-widgets' ) );
		}

		$response = wp_safe_remote_get(
			'https://places.googleapis.com/v1/places/' . rawurlencode( $place_id ),
			array(
				'timeout' => 25,
				'headers' => array(
					'Accept'           => 'application/json',
					'X-Goog-Api-Key'   => $api_key,
					'X-Goog-FieldMask' => 'id,displayName,rating,userRatingCount,reviews,googleMapsUri,attributions',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 || ! is_array( $data ) ) {
			$message = is_array( $data ) && ! empty( $data['error']['message'] )
				? sanitize_text_field( $data['error']['message'] )
				/* translators: %d: HTTP status code returned by Google Places. */
				: sprintf( __( 'Google Places returned HTTP %d.', 'tomawesome-review-widgets' ), $status );
			return new \WP_Error( 'tarw_places_api_error', $message, array( 'status' => $status ) );
		}

		return $data;
	}
}
