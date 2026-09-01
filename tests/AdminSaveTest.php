<?php
/**
 * Save-permission regressions for explicit nonce rejection.
 *
 * @package TomAwesomeReviewWidgets
 */

use PHPUnit\Framework\TestCase;
use TomAwesome_Review_Widgets\Admin;

final class AdminSaveTest extends TestCase {

	/** @dataProvider permission_provider */
	public function test_save_requires_a_nonempty_valid_nonce_and_an_administrator( $post, $administrator, $revision, $expected, $nonce_checks ): void {
		$previous_post = $_POST;
		$_POST = $post;
		$GLOBALS['tarw_test_is_admin'] = $administrator;
		$GLOBALS['tarw_test_is_revision'] = $revision;
		$GLOBALS['tarw_test_nonce_calls'] = array();
		try {
			$class = new ReflectionClass( Admin::class );
			$admin = $class->newInstanceWithoutConstructor();
			$method = $class->getMethod( 'can_save' );
			$method->setAccessible( true );
			$this->assertSame( $expected, $method->invoke( $admin, 123, 'tarw_widget_nonce', 'tarw_save_widget' ) );
			$this->assertCount( $nonce_checks, $GLOBALS['tarw_test_nonce_calls'] );
		} finally {
			$_POST = $previous_post;
			unset( $GLOBALS['tarw_test_is_admin'], $GLOBALS['tarw_test_is_revision'], $GLOBALS['tarw_test_nonce_calls'] );
		}
	}

	public static function permission_provider(): array {
		return array(
			'missing nonce' => array( array(), true, false, false, 0 ),
			'empty nonce' => array( array( 'tarw_widget_nonce' => '' ), true, false, false, 0 ),
			'invalid nonce' => array( array( 'tarw_widget_nonce' => 'invalid' ), true, false, false, 1 ),
			'current nonce' => array( array( 'tarw_widget_nonce' => 'valid-current' ), true, false, true, 1 ),
			'previous nonce' => array( array( 'tarw_widget_nonce' => 'valid-previous' ), true, false, true, 1 ),
			'not administrator' => array( array( 'tarw_widget_nonce' => 'valid-current' ), false, false, false, 0 ),
			'revision' => array( array( 'tarw_widget_nonce' => 'valid-current' ), true, true, false, 0 ),
		);
	}
}
