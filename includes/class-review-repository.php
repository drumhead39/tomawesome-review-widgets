<?php
/**
 * Review persistence.
 *
 * @package TomAwesomeReviewWidgets
 */

namespace TomAwesome_Review_Widgets;

defined( 'ABSPATH' ) || exit;

// This repository intentionally uses a plugin-owned custom table. Table names cannot be query placeholders.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Stores only the API fields needed to render widgets.
 */
final class Review_Repository {

	/**
	 * Upserts an imported review while preserving administrator privacy approval.
	 *
	 * @param int                 $source_id Source post ID.
	 * @param array<string,mixed> $review Normalized review.
	 * @return int|false
	 */
	public function upsert( $source_id, array $review ) {
		global $wpdb;

		$table      = $this->table();
		$now        = current_time( 'mysql', true );
		$expires_at = gmdate( 'Y-m-d H:i:s', time() + ( 30 * DAY_IN_SECONDS ) );
		$create     = $this->mysql_time( $review['create_time'] ?? '' );
		$update     = $this->mysql_time( $review['update_time'] ?? '' );

		$sql = "INSERT INTO {$table}
			(source_id, external_id, reviewer_name, reviewer_photo_url, reviewer_profile_url, rating, review_text, review_url, create_time, update_time, privacy_approved, privacy_excerpt, synced_at, expires_at)
			VALUES (%d, %s, %s, %s, %s, %d, %s, %s, NULLIF(%s, ''), NULLIF(%s, ''), 0, '', %s, %s)
			ON DUPLICATE KEY UPDATE
				reviewer_name = VALUES(reviewer_name),
				reviewer_photo_url = VALUES(reviewer_photo_url),
				reviewer_profile_url = VALUES(reviewer_profile_url),
				rating = VALUES(rating),
				review_text = VALUES(review_text),
				review_url = VALUES(review_url),
				create_time = VALUES(create_time),
				update_time = VALUES(update_time),
				synced_at = VALUES(synced_at),
				expires_at = VALUES(expires_at)";

		return $wpdb->query(
			$wpdb->prepare(
				$sql,
				absint( $source_id ),
				sanitize_text_field( $review['external_id'] ?? '' ),
				sanitize_text_field( $review['reviewer_name'] ?? '' ),
				esc_url_raw( $review['reviewer_photo_url'] ?? '' ),
				esc_url_raw( $review['reviewer_profile_url'] ?? '' ),
				min( 5, max( 0, absint( $review['rating'] ?? 0 ) ) ),
				sanitize_textarea_field( $review['review_text'] ?? '' ),
				esc_url_raw( $review['review_url'] ?? '' ),
				$create,
				$update,
				$now,
				$expires_at
			)
		);
	}

	/**
	 * Gets eligible reviews for a widget.
	 *
	 * @param int                 $source_id Source post ID.
	 * @param array<string,mixed> $args Query arguments.
	 * @return array<int,object>
	 */
	public function get_for_widget( $source_id, array $args ) {
		global $wpdb;

		$source_id    = absint( $source_id );
		$minimum      = min( 5, max( 1, absint( $args['min_rating'] ?? 1 ) ) );
		$limit        = min( 50, max( 1, absint( $args['limit'] ?? 6 ) ) );
		$privacy_mode = ! empty( $args['privacy_mode'] );
		$text_only    = ! empty( $args['text_only'] );
		$order        = $this->order_clause( (string) ( $args['sort'] ?? 'newest' ) );
		$where        = 'source_id = %d AND rating >= %d AND expires_at > %s';
		$values       = array( $source_id, $minimum, current_time( 'mysql', true ) );

		if ( $privacy_mode ) {
			$where .= " AND privacy_approved = 1 AND privacy_excerpt <> ''";
		} elseif ( $text_only ) {
			$where .= " AND review_text <> ''";
		}

		$sql      = "SELECT * FROM {$this->table()} WHERE {$where} ORDER BY {$order} LIMIT %d";
		$values[] = $limit;

		return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
	}

	/**
	 * Gets reviews for the administrator library.
	 *
	 * @param int $source_id Optional source filter.
	 * @param int $page Page number.
	 * @param int $per_page Rows per page.
	 * @return array<int,object>
	 */
	public function get_admin_page( $source_id, $page = 1, $per_page = 50 ) {
		global $wpdb;

		$page     = max( 1, absint( $page ) );
		$per_page = min( 100, max( 1, absint( $per_page ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		if ( $source_id ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->table()} WHERE source_id = %d ORDER BY update_time DESC, id DESC LIMIT %d OFFSET %d",
					absint( $source_id ),
					$per_page,
					$offset
				)
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table()} ORDER BY update_time DESC, id DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);
	}

	/**
	 * Counts reviews, optionally for one source.
	 *
	 * @param int $source_id Optional source ID.
	 * @return int
	 */
	public function count( $source_id = 0 ) {
		global $wpdb;

		if ( $source_id ) {
			return absint(
				$wpdb->get_var(
					$wpdb->prepare( "SELECT COUNT(*) FROM {$this->table()} WHERE source_id = %d", absint( $source_id ) )
				)
			);
		}

		return absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table()}" ) );
	}

	/**
	 * Saves the administrator-reviewed privacy copy and approval state.
	 *
	 * @param int    $review_id Review table ID.
	 * @param bool   $approved Whether approved.
	 * @param string $excerpt Privacy-safe display copy.
	 * @return int|false
	 */
	public function set_privacy_review( $review_id, $approved, $excerpt ) {
		global $wpdb;

		return $wpdb->update(
			$this->table(),
			array(
				'privacy_approved'    => $approved ? 1 : 0,
				'privacy_excerpt'     => Privacy::sanitize_excerpt( $excerpt ),
				'privacy_approved_at' => $approved ? current_time( 'mysql', true ) : null,
			),
			array( 'id' => absint( $review_id ) ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Removes rows that are no longer returned by a successful source sync.
	 *
	 * @param int      $source_id Source post ID.
	 * @param string[] $external_ids Current review IDs.
	 * @return int|false
	 */
	public function delete_not_in( $source_id, array $external_ids ) {
		global $wpdb;

		$source_id    = absint( $source_id );
		$external_ids = array_values( array_filter( array_map( 'sanitize_text_field', $external_ids ) ) );

		if ( empty( $external_ids ) ) {
			return $wpdb->delete( $this->table(), array( 'source_id' => $source_id ), array( '%d' ) );
		}

		$placeholders = implode( ', ', array_fill( 0, count( $external_ids ), '%s' ) );
		$sql          = "DELETE FROM {$this->table()} WHERE source_id = %d AND external_id NOT IN ({$placeholders})";
		$values       = array_merge( array( $source_id ), $external_ids );
		return $wpdb->query( $wpdb->prepare( $sql, $values ) );
	}

	/**
	 * Deletes all reviews for a removed source.
	 *
	 * @param int $source_id Source post ID.
	 * @return int|false
	 */
	public function delete_source( $source_id ) {
		global $wpdb;
		return $wpdb->delete( $this->table(), array( 'source_id' => absint( $source_id ) ), array( '%d' ) );
	}

	/**
	 * Enforces Google's maximum 30-day API content storage period.
	 *
	 * @return int|false
	 */
	public function purge_expired() {
		global $wpdb;
		return $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$this->table()} WHERE expires_at <= %s", current_time( 'mysql', true ) )
		);
	}

	/**
	 * Gets the prefixed table name.
	 *
	 * @return string
	 */
	public function table() {
		global $wpdb;
		return $wpdb->prefix . 'tarw_reviews';
	}

	/**
	 * Converts an API timestamp to UTC MySQL format.
	 *
	 * @param string $value Timestamp.
	 * @return string
	 */
	private function mysql_time( $value ) {
		$timestamp = strtotime( (string) $value );
		return false === $timestamp ? '' : gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Returns a fixed safe ORDER BY clause.
	 *
	 * @param string $sort Sort option.
	 * @return string
	 */
	private function order_clause( $sort ) {
		$clauses = array(
			'newest' => 'COALESCE(update_time, create_time) DESC, id DESC',
			'oldest' => 'COALESCE(create_time, update_time) ASC, id ASC',
			'highest'=> 'rating DESC, COALESCE(update_time, create_time) DESC',
			'random' => 'RAND()',
		);
		return $clauses[ $sort ] ?? $clauses['newest'];
	}
}
