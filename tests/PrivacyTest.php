<?php
/**
 * Healthcare Privacy Mode tests.
 *
 * @package TomAwesomeReviewWidgets
 */

use PHPUnit\Framework\TestCase;
use TomAwesome_Review_Widgets\Privacy;

/**
 * Tests privacy display requirements independently of WordPress.
 */
final class PrivacyTest extends TestCase {

	/**
	 * Identity is always generic.
	 *
	 * @return void
	 */
	public function test_anonymous_label_does_not_include_imported_identity() {
		$this->assertSame( 'Google Reviewer', Privacy::anonymous_label() );
	}

	/**
	 * Tags are removed from administrator copy.
	 *
	 * @return void
	 */
	public function test_excerpt_is_plain_text() {
		$this->assertSame( 'Helpful care', Privacy::sanitize_excerpt( '<strong>Helpful</strong> care' ) );
	}

	/**
	 * Display requires approval and nonempty privacy copy.
	 *
	 * @return void
	 */
	public function test_review_must_be_approved_and_have_copy() {
		$this->assertFalse( Privacy::is_ready( array( 'privacy_approved' => 0, 'privacy_excerpt' => 'Safe text' ) ) );
		$this->assertFalse( Privacy::is_ready( array( 'privacy_approved' => 1, 'privacy_excerpt' => '  ' ) ) );
		$this->assertTrue( Privacy::is_ready( array( 'privacy_approved' => 1, 'privacy_excerpt' => 'Safe text' ) ) );
	}

	/**
	 * Copy is bounded to prevent unexpectedly large database and output values.
	 *
	 * @return void
	 */
	public function test_excerpt_has_a_two_thousand_character_limit() {
		$this->assertSame( 2000, strlen( Privacy::sanitize_excerpt( str_repeat( 'x', 2500 ) ) ) );
	}
}
