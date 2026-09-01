<?php
/**
 * Query-construction regressions for the Plugin Check corrections.
 *
 * @package TomAwesomeReviewWidgets
 */

use PHPUnit\Framework\TestCase;
use TomAwesome_Review_Widgets\Review_Repository;

final class ReviewRepositoryTest extends TestCase {

	private $database;
	private $previous_database;
	private $repository;

	protected function setUp(): void {
		$this->previous_database = $GLOBALS['wpdb'] ?? null;
		$this->database = new TARW_Test_Database();
		$GLOBALS['wpdb'] = $this->database;
		$this->repository = new Review_Repository();
	}

	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->previous_database;
	}

	public function test_upsert_binds_the_identifier_and_review_values(): void {
		$this->database->prefix = 'custom_';
		$result = $this->repository->upsert(
			-42,
			array(
				'external_id' => "review'1",
				'reviewer_name' => "O'Brien",
				'reviewer_photo_url' => 'https://example.com/photo.jpg',
				'reviewer_profile_url' => 'https://example.com/profile',
				'rating' => 9,
				'review_text' => "It's 100% helpful.",
				'review_url' => 'https://example.com/review',
				'create_time' => '2026-08-01T10:00:00Z',
				'update_time' => '2026-08-02T11:00:00Z',
			)
		);
		$prepared = $this->database->prepared[0];
		$this->assertSame( 1, $result );
		$this->assertStringStartsWith( 'INSERT INTO %i', $prepared['template'] );
		$this->assertStringNotContainsString( "O'Brien", $prepared['template'] );
		$this->assertSame( 'custom_tarw_reviews', $prepared['arguments'][0] );
		$this->assertSame( 42, $prepared['arguments'][1] );
		$this->assertSame( "review'1", $prepared['arguments'][2] );
		$this->assertSame( 5, $prepared['arguments'][6] );
		$this->assertSame( '2026-08-01 10:00:00', $prepared['arguments'][9] );
		$this->assertSame( '2026-08-02 11:00:00', $prepared['arguments'][10] );
		$this->assertSame( '2026-09-01 12:00:00', $prepared['arguments'][11] );
		$this->assertEqualsWithDelta( time() + 30 * DAY_IN_SECONDS, strtotime( $prepared['arguments'][12] . ' UTC' ), 2 );
		$this->assertStringContainsString( "O''Brien", $this->database->queries[0] );
		$this->assertStringContainsString( "It''s 100% helpful.", $this->database->queries[0] );
	}

	public function test_resynchronization_does_not_overwrite_privacy_approval(): void {
		$this->repository->upsert( 7, array( 'external_id' => 'one' ) );
		$parts = explode( 'ON DUPLICATE KEY UPDATE', $this->database->queries[0] );
		$this->assertCount( 2, $parts );
		$this->assertStringContainsString( 'privacy_approved, privacy_excerpt', $parts[0] );
		$this->assertStringNotContainsString( 'privacy_', $parts[1] );
		$this->assertStringContainsString( 'expires_at = VALUES(expires_at)', $parts[1] );
		$this->assertSame( '', $this->database->prepared[0]['arguments'][9] );
	}

	/** @dataProvider sort_provider */
	public function test_sort_choices_preserve_existing_order( $sort, $order ): void {
		$this->database->rows = array( (object) array( 'id' => 123 ) );
		$rows = $this->repository->get_for_widget( 42, array( 'sort' => $sort ) );
		$this->assertSame( $this->database->rows, $rows );
		$this->assertSame(
			"SELECT * FROM `wp_tarw_reviews` WHERE source_id = 42 AND rating >= 1 AND expires_at > '2026-09-01 12:00:00' ORDER BY " . $order . ' LIMIT 6',
			$this->database->queries[0]
		);
		$this->assertSame( 'wp_tarw_reviews', $this->database->prepared[0]['arguments'][0] );
	}

	public static function sort_provider(): array {
		return array(
			'newest' => array( 'newest', 'COALESCE(update_time, create_time) DESC, id DESC' ),
			'oldest' => array( 'oldest', 'COALESCE(create_time, update_time) ASC, id ASC' ),
			'highest' => array( 'highest', 'rating DESC, COALESCE(update_time, create_time) DESC' ),
			'random' => array( 'random', 'RAND()' ),
			'unknown is newest' => array( 'DESC; DROP TABLE wp_options', 'COALESCE(update_time, create_time) DESC, id DESC' ),
		);
	}

	public function test_privacy_filter_takes_precedence_over_original_text_filter(): void {
		$this->repository->get_for_widget( 5, array( 'privacy_mode' => 1, 'text_only' => 1 ) );
		$this->assertStringContainsString( " AND privacy_approved = 1 AND privacy_excerpt <> ''", $this->database->queries[0] );
		$this->assertStringNotContainsString( "review_text <> ''", $this->database->queries[0] );
	}

	public function test_written_review_filter_remains_optional(): void {
		$this->repository->get_for_widget( 5, array( 'text_only' => 1 ) );
		$this->repository->get_for_widget( 5, array() );
		$this->assertStringContainsString( " AND review_text <> ''", $this->database->queries[0] );
		$this->assertStringNotContainsString( 'privacy_approved', $this->database->queries[0] );
		$this->assertStringNotContainsString( "review_text <> ''", $this->database->queries[1] );
	}

	public function test_rating_and_result_limits_remain_bounded(): void {
		$this->repository->get_for_widget( -12, array( 'min_rating' => 100, 'limit' => 1000 ) );
		$this->repository->get_for_widget( 12, array( 'min_rating' => 0, 'limit' => 0 ) );
		$this->assertStringContainsString( 'source_id = 12 AND rating >= 5', $this->database->queries[0] );
		$this->assertStringEndsWith( ' LIMIT 50', $this->database->queries[0] );
		$this->assertStringContainsString( 'rating >= 1', $this->database->queries[1] );
		$this->assertStringEndsWith( ' LIMIT 1', $this->database->queries[1] );
	}
}
