<?php
/**
 * Google Business Profile API error tests.
 *
 * @package TomAwesomeReviewWidgets
 */

use PHPUnit\Framework\TestCase;
use TomAwesome_Review_Widgets\Business_Profile_Client;

/**
 * Tests actionable handling of Google Business Profile failures.
 */
final class BusinessProfileClientTest extends TestCase {

	/**
	 * A quota response receives the dedicated actionable error code.
	 *
	 * @return void
	 */
	public function test_quota_error_is_recognized_and_preserves_safe_details() {
		$error = Business_Profile_Client::error_from_response(
			429,
			array(
				'error' => array(
					'status'  => 'RESOURCE_EXHAUSTED',
					'message' => "Quota exceeded for quota metric 'Requests' and limit 'Requests per minute'.",
					'details' => array(
						array(
							'metadata' => array(
								'consumer'          => 'projects/246395681948',
								'quota_limit_value' => '0',
								'quota_metric'      => 'mybusinessaccountmanagement.googleapis.com/requests',
								'service'           => 'mybusinessaccountmanagement.googleapis.com',
							),
						),
					),
				),
			)
		);

		$this->assertSame( 'tarw_business_profile_quota_exceeded', $error->get_error_code() );
		$this->assertStringContainsString( 'Basic API Access', $error->get_error_message() );
		$this->assertSame( '0', $error->get_error_data()['quota_limit_value'] );
		$this->assertSame( 'mybusinessaccountmanagement.googleapis.com', $error->get_error_data()['service'] );
	}

	/**
	 * Google's quota wording is recognized even when the HTTP status is 403.
	 *
	 * @return void
	 */
	public function test_quota_message_is_recognized_independently_of_http_status() {
		$error = Business_Profile_Client::error_from_response(
			403,
			array(
				'error' => array(
					'status'  => 'PERMISSION_DENIED',
					'message' => "Quota exceeded for quota metric 'Requests' and limit 'Requests per minute'.",
				),
			)
		);

		$this->assertSame( 'tarw_business_profile_quota_exceeded', $error->get_error_code() );
		$this->assertStringContainsString( 'zero Requests per minute quota', $error->get_error_message() );
	}

	/**
	 * Non-quota responses keep Google's useful message.
	 *
	 * @return void
	 */
	public function test_non_quota_error_remains_a_general_api_error() {
		$error = Business_Profile_Client::error_from_response(
			403,
			array(
				'error' => array(
					'status'  => 'PERMISSION_DENIED',
					'message' => 'The authenticated user does not have access to this location.',
				),
			)
		);

		$this->assertSame( 'tarw_business_profile_api_error', $error->get_error_code() );
		$this->assertSame( 'The authenticated user does not have access to this location.', $error->get_error_message() );
		$this->assertSame( 403, $error->get_error_data()['status'] );
	}
}
