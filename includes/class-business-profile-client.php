<?php
/**
 * Google Business Profile API client.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves accounts, locations, and complete review lists for managed profiles.
 */
final class Business_Profile_Client {

	/**
	 * OAuth service.
	 *
	 * @var OAuth
	 */
	private $oauth;

	/**
	 * Constructor.
	 *
	 * @param OAuth $oauth OAuth service.
	 */
	public function __construct( OAuth $oauth ) {
		$this->oauth = $oauth;
	}

	/**
	 * Lists accessible Business Profile accounts.
	 *
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	public function list_accounts() {
		$data = $this->get_json( 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts' );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return isset( $data['accounts'] ) && is_array( $data['accounts'] ) ? $data['accounts'] : array();
	}

	/**
	 * Lists locations across all accessible accounts.
	 *
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	public function list_locations() {
		$accounts = $this->list_accounts();
		if ( is_wp_error( $accounts ) ) {
			return $accounts;
		}

		$locations = array();
		foreach ( $accounts as $account ) {
			if ( empty( $account['name'] ) ) {
				continue;
			}

			$page_token = '';
			do {
				$url = add_query_arg(
					array_filter(
						array(
							'readMask'  => 'name,title,storeCode,websiteUri,phoneNumbers,metadata',
							'pageSize'  => 100,
							'pageToken' => $page_token,
						)
					),
					'https://mybusinessbusinessinformation.googleapis.com/v1/' . ltrim( $account['name'], '/' ) . '/locations'
				);
				$data = $this->get_json( $url );
				if ( is_wp_error( $data ) ) {
					return $data;
				}

				foreach ( $data['locations'] ?? array() as $location ) {
					if ( ! is_array( $location ) ) {
						continue;
					}
					$location['tarw_account_name'] = (string) $account['name'];
					$location['tarw_account_label'] = (string) ( $account['accountName'] ?? $account['name'] );
					$locations[] = $location;
				}

				$page_token = isset( $data['nextPageToken'] ) ? (string) $data['nextPageToken'] : '';
			} while ( '' !== $page_token );
		}

		return $locations;
	}

	/**
	 * Lists all reviews for one managed location.
	 *
	 * @param string $account_name Account resource name.
	 * @param string $location_name Location resource name.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function list_reviews( $account_name, $location_name ) {
		$account_id  = $this->resource_id( $account_name );
		$location_id = $this->resource_id( $location_name );

		if ( '' === $account_id || '' === $location_id ) {
			return new \WP_Error( 'tarw_invalid_location', __( 'The Business Profile account or location identifier is invalid.', 'tomawesome-review-widgets' ) );
		}

		$reviews      = array();
		$page_token   = '';
		$average      = null;
		$total_count  = null;
		$base_url     = 'https://mybusiness.googleapis.com/v4/accounts/' . rawurlencode( $account_id ) . '/locations/' . rawurlencode( $location_id ) . '/reviews';

		do {
			$url = add_query_arg(
				array_filter(
					array(
						'pageSize'  => 50,
						'pageToken' => $page_token,
						'orderBy'   => 'updateTime desc',
					)
				),
				$base_url
			);
			$data = $this->get_json( $url );
			if ( is_wp_error( $data ) ) {
				return $data;
			}

			$reviews     = array_merge( $reviews, is_array( $data['reviews'] ?? null ) ? $data['reviews'] : array() );
			$average     = isset( $data['averageRating'] ) ? (float) $data['averageRating'] : $average;
			$total_count = isset( $data['totalReviewCount'] ) ? absint( $data['totalReviewCount'] ) : $total_count;
			$page_token  = isset( $data['nextPageToken'] ) ? (string) $data['nextPageToken'] : '';
		} while ( '' !== $page_token );

		return array(
			'reviews'      => $reviews,
			'average'      => $average,
			'total_count'  => $total_count,
		);
	}

	/**
	 * Performs an authenticated JSON GET request.
	 *
	 * @param string $url Fixed Google API URL.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function get_json( $url ) {
		$token = $this->oauth->access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => 25,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Accept'        => 'application/json',
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
				: sprintf( __( 'Google Business Profile returned HTTP %d.', 'tomawesome-review-widgets' ), $status );
			return new \WP_Error( 'tarw_business_profile_api_error', $message, array( 'status' => $status ) );
		}

		return $data;
	}

	/**
	 * Extracts the final segment of a Google resource name.
	 *
	 * @param string $name Resource name.
	 * @return string
	 */
	private function resource_id( $name ) {
		$parts = array_values( array_filter( explode( '/', trim( (string) $name, '/' ) ) ) );
		$id    = end( $parts );
		return is_string( $id ) && preg_match( '/^[A-Za-z0-9_-]+$/', $id ) ? $id : '';
	}
}
