<?php
/**
 * Isolated uninstall tests; no WordPress data is removed.
 *
 * @package TomAwesomeReviewWidgets
 */

use PHPUnit\Framework\TestCase;

final class UninstallTest extends TestCase {

	private $previous_database;

	protected function setUp(): void {
		$this->previous_database = $GLOBALS['wpdb'] ?? null;
		$GLOBALS['wpdb'] = new TARW_Test_Database();
		$GLOBALS['tarw_test_options'] = array();
		$GLOBALS['tarw_test_post_ids'] = array( 10, 20 );
		$GLOBALS['tarw_test_deleted_posts'] = array();
		$GLOBALS['tarw_test_deleted_options'] = array();
		$GLOBALS['tarw_test_cleared_hooks'] = array();
	}

	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->previous_database;
		unset(
			$GLOBALS['tarw_test_options'],
			$GLOBALS['tarw_test_post_ids'],
			$GLOBALS['tarw_test_deleted_posts'],
			$GLOBALS['tarw_test_deleted_options'],
			$GLOBALS['tarw_test_cleared_hooks']
		);
	}

	public function test_uninstall_preserves_data_without_explicit_opt_in(): void {
		include dirname( __DIR__ ) . '/uninstall.php';
		$this->assertSame( array(), $GLOBALS['wpdb']->queries );
		$this->assertSame( array(), $GLOBALS['tarw_test_deleted_posts'] );
		$this->assertSame( array(), $GLOBALS['tarw_test_deleted_options'] );
		$this->assertSame( array(), $GLOBALS['tarw_test_cleared_hooks'] );
	}

	public function test_opted_in_uninstall_prepares_only_the_plugin_table(): void {
		$GLOBALS['tarw_test_options']['tarw_settings'] = array( 'delete_on_uninstall' => 1 );
		$GLOBALS['wpdb']->prefix = 'clinic_';
		$GLOBALS['wpdb']->column = array( '_transient_tarw_oauth_state_example' );
		include dirname( __DIR__ ) . '/uninstall.php';
		$this->assertSame( 'DROP TABLE IF EXISTS %i', $GLOBALS['wpdb']->prepared[0]['template'] );
		$this->assertSame( array( 'clinic_tarw_reviews' ), $GLOBALS['wpdb']->prepared[0]['arguments'] );
		$this->assertSame( 'DROP TABLE IF EXISTS `clinic_tarw_reviews`', $GLOBALS['wpdb']->queries[0] );
		$this->assertSame( array( array( 10, true ), array( 20, true ) ), $GLOBALS['tarw_test_deleted_posts'] );
		$this->assertSame( array( 'tarw_daily_sync' ), $GLOBALS['tarw_test_cleared_hooks'] );
		$this->assertContains( 'tarw_settings', $GLOBALS['tarw_test_deleted_options'] );
		$this->assertContains( '_transient_tarw_oauth_state_example', $GLOBALS['tarw_test_deleted_options'] );
		$this->assertContains( '_transient_timeout_tarw_oauth_state_example', $GLOBALS['tarw_test_deleted_options'] );
	}
}
