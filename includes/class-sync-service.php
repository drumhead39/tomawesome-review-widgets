<?php
/**
 * Review synchronization service.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Synchronizes configured sources without exposing credentials in the browser.
 */
final class Sync_Service {

	/**
	 * Review persistence service.
	 *
	 * @var Review_Repository
	 */
	private $repository;

	/**
	 * Google Business Profile API client.
	 *
	 * @var Business_Profile_Client
	 */
	private $business_profile;

	/**
	 * Google Places API client.
	 *
	 * @var Places_Client
	 */
	private $places;

	/**
	 * Constructor.
	 *
	 * @param Settings          $settings Settings service.
	 * @param Review_Repository $repository Repository service.
	 * @param OAuth             $oauth OAuth service.
	 */
	public function __construct( Settings $settings, Review_Repository $repository, OAuth $oauth ) {
		$this->repository       = $repository;
		$this->business_profile = new Business_Profile_Client( $oauth );
		$this->places           = new Places_Client( $settings );
	}

	/**
	 * Synchronizes every enabled source and removes expired API content.
	 *
	 * @return void
	 */
	public function sync_all() {
		$source_ids = get_posts(
			array(
				'post_type'      => 'tarw_source',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		foreach ( $source_ids as $source_id ) {
			if ( '0' === (string) get_post_meta( $source_id, '_tarw_enabled', true ) ) {
				continue;
			}
			$this->sync_source( $source_id );
		}

		$this->repository->purge_expired();
	}

	/**
	 * Synchronizes one source.
	 *
	 * @param int $source_id Source post ID.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function sync_source( $source_id ) {
		$source_id = absint( $source_id );
		if ( 'tarw_source' !== get_post_type( $source_id ) ) {
			return new \WP_Error( 'tarw_source_missing', __( 'The review source could not be found.', 'tomawesome-review-widgets' ) );
		}

		$type = get_post_meta( $source_id, '_tarw_source_type', true );
		$data = 'places' === $type ? $this->fetch_places( $source_id ) : $this->fetch_business_profile( $source_id );

		if ( is_wp_error( $data ) ) {
			update_post_meta( $source_id, '_tarw_last_error', sanitize_text_field( $data->get_error_message() ) );
			update_post_meta( $source_id, '_tarw_last_attempt', current_time( 'mysql', true ) );
			return $data;
		}

		$external_ids = array();
		foreach ( $data['reviews'] as $review ) {
			if ( empty( $review['external_id'] ) ) {
				continue;
			}
			$this->repository->upsert( $source_id, $review );
			$external_ids[] = $review['external_id'];
		}

		$this->repository->delete_not_in( $source_id, $external_ids );
		update_post_meta( $source_id, '_tarw_rating', null === $data['average'] ? '' : (float) $data['average'] );
		update_post_meta( $source_id, '_tarw_total_review_count', null === $data['total_count'] ? count( $external_ids ) : absint( $data['total_count'] ) );
		update_post_meta( $source_id, '_tarw_last_sync', current_time( 'mysql', true ) );
		delete_post_meta( $source_id, '_tarw_last_error' );

		return array(
			'imported' => count( $external_ids ),
			'average'  => $data['average'],
			'total'    => $data['total_count'],
		);
	}

	/**
	 * Gets locations available through the connected account.
	 *
	 * @return array<int,array<string,mixed>>|\WP_Error
	 */
	public function discover_locations() {
		return $this->business_profile->list_locations();
	}

	/**
	 * Fetches and normalizes a Business Profile source.
	 *
	 * @param int $source_id Source post ID.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function fetch_business_profile( $source_id ) {
		$account  = (string) get_post_meta( $source_id, '_tarw_account_name', true );
		$location = (string) get_post_meta( $source_id, '_tarw_location_name', true );
		$data     = $this->business_profile->list_reviews( $account, $location );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$normalized = array();
		foreach ( $data['reviews'] as $review ) {
			$reviewer     = is_array( $review['reviewer'] ?? null ) ? $review['reviewer'] : array();
			$normalized[] = array(
				'external_id'          => (string) ( $review['reviewId'] ?? '' ),
				'reviewer_name'        => (string) ( $reviewer['displayName'] ?? '' ),
				'reviewer_photo_url'   => (string) ( $reviewer['profilePhotoUrl'] ?? '' ),
				'reviewer_profile_url' => '',
				'rating'               => $this->star_rating( $review['starRating'] ?? 0 ),
				'review_text'          => (string) ( $review['comment'] ?? '' ),
				'review_url'           => (string) get_post_meta( $source_id, '_tarw_review_url', true ),
				'create_time'          => (string) ( $review['createTime'] ?? '' ),
				'update_time'          => (string) ( $review['updateTime'] ?? '' ),
			);
		}

		return array(
			'reviews'     => $normalized,
			'average'     => $data['average'],
			'total_count' => $data['total_count'],
		);
	}

	/**
	 * Fetches and normalizes a Places source.
	 *
	 * @param int $source_id Source post ID.
	 * @return array<string,mixed>|\WP_Error
	 */
	private function fetch_places( $source_id ) {
		$place_id = (string) get_post_meta( $source_id, '_tarw_place_id', true );
		$data     = $this->places->get_place( $place_id );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$maps_url   = (string) ( $data['googleMapsUri'] ?? '' );
		$normalized = array();
		foreach ( $data['reviews'] ?? array() as $review ) {
			$author       = is_array( $review['authorAttribution'] ?? null ) ? $review['authorAttribution'] : array();
			$text         = is_array( $review['text'] ?? null ) ? (string) ( $review['text']['text'] ?? '' ) : '';
			$normalized[] = array(
				'external_id'          => (string) ( $review['name'] ?? '' ),
				'reviewer_name'        => (string) ( $author['displayName'] ?? '' ),
				'reviewer_photo_url'   => (string) ( $author['photoUri'] ?? '' ),
				'reviewer_profile_url' => (string) ( $author['uri'] ?? '' ),
				'rating'               => min( 5, max( 0, absint( $review['rating'] ?? 0 ) ) ),
				'review_text'          => $text,
				'review_url'           => $maps_url,
				'create_time'          => (string) ( $review['publishTime'] ?? '' ),
				'update_time'          => (string) ( $review['publishTime'] ?? '' ),
			);
		}

		if ( '' !== $maps_url ) {
			update_post_meta( $source_id, '_tarw_review_url', esc_url_raw( $maps_url ) );
		}

		return array(
			'reviews'     => $normalized,
			'average'     => isset( $data['rating'] ) ? (float) $data['rating'] : null,
			'total_count' => isset( $data['userRatingCount'] ) ? absint( $data['userRatingCount'] ) : null,
		);
	}

	/**
	 * Converts Google's rating enum to an integer.
	 *
	 * @param mixed $rating Rating value.
	 * @return int
	 */
	private function star_rating( $rating ) {
		$map = array(
			'ONE'   => 1,
			'TWO'   => 2,
			'THREE' => 3,
			'FOUR'  => 4,
			'FIVE'  => 5,
		);
		return is_numeric( $rating ) ? min( 5, max( 0, absint( $rating ) ) ) : ( $map[ (string) $rating ] ?? 0 );
	}
}
