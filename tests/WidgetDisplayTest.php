<?php
/**
 * Public widget-heading tests.
 *
 * @package TomAwesomeReviewWidgets
 */

use PHPUnit\Framework\TestCase;
use TomAwesome_Review_Widgets\Widget_Display;

/**
 * Tests visitor-facing business-name selection.
 */
final class WidgetDisplayTest extends TestCase {

	/**
	 * A widget-level heading overrides the synchronized Google name.
	 *
	 * @return void
	 */
	public function test_custom_heading_has_priority() {
		$this->assertSame(
			'What Our Patients Say',
			Widget_Display::business_name( 'What Our Patients Say', 'Davis Family Chiropractic' )
		);
	}

	/**
	 * Google's business name is the default public heading.
	 *
	 * @return void
	 */
	public function test_google_business_name_is_the_default() {
		$this->assertSame(
			'Davis Family Chiropractic',
			Widget_Display::business_name( '', 'Davis Family Chiropractic' )
		);
	}

	/**
	 * Missing public names receive a generic label, never an internal source title.
	 *
	 * @return void
	 */
	public function test_generic_fallback_does_not_expose_internal_source_title() {
		$this->assertSame( 'Google Reviews', Widget_Display::business_name( '', '' ) );
		$this->assertStringNotContainsString( 'Places - DFC', Widget_Display::business_name( '', '' ) );
	}

	/**
	 * Heading values are normalized to safe single-line text.
	 *
	 * @return void
	 */
	public function test_heading_is_sanitized() {
		$this->assertSame(
			'Davis Family Chiropractic',
			Widget_Display::business_name( "<strong>Davis Family</strong>\nChiropractic", '' )
		);
	}

	/**
	 * Visual controls produce scoped CSS custom properties.
	 *
	 * @return void
	 */
	public function test_visual_controls_build_inline_style() {
		$this->assertSame(
			'--tarw-widget-background:#f8f8f8;--tarw-widget-text:#222;--tarw-accent:#880000;--tarw-card-background:#fff;--tarw-widget-margin:24px;--tarw-widget-padding:20px;--tarw-card-border-radius:12px;',
			Widget_Display::inline_style(
				array(
					'widget_background'   => '#F8F8F8',
					'widget_text'         => '#222',
					'accent'              => '#880000',
					'card_background'     => '#fff',
					'widget_margin'        => '24',
					'widget_padding'       => '20',
					'card_border_radius'   => '12',
					'widget_border_radius' => '',
				)
			)
		);
	}

	/**
	 * Invalid CSS values are omitted and large numeric values are clamped.
	 *
	 * @return void
	 */
	public function test_visual_controls_reject_unsafe_values_and_clamp_pixels() {
		$style = Widget_Display::inline_style(
			array(
				'widget_background'  => 'red;position:fixed',
				'widget_text'        => '#12345g',
				'widget_padding'     => '-10',
				'widget_margin'      => '9999',
				'card_padding'       => '20px;display:none',
				'widget_border_width' => '50',
			)
		);

		$this->assertSame( '--tarw-widget-margin:300px;--tarw-widget-border-width:20px;', $style );
		$this->assertStringNotContainsString( 'position', $style );
		$this->assertStringNotContainsString( 'display', $style );
	}

	/**
	 * Empty visual controls leave the default widget design unchanged.
	 *
	 * @return void
	 */
	public function test_empty_visual_controls_add_no_inline_style() {
		$this->assertSame( '', Widget_Display::inline_style( Widget_Display::style_defaults() ) );
	}

	/**
	 * Places sources cannot use the author-suppressing privacy presentation.
	 *
	 * @return void
	 */
	public function test_places_sources_disable_privacy_mode() {
		$this->assertFalse( Widget_Display::privacy_mode( true, true ) );
		$this->assertTrue( Widget_Display::privacy_mode( true, false ) );
		$this->assertFalse( Widget_Display::privacy_mode( false, false ) );
	}

	/**
	 * Available Places author photos override the optional widget setting.
	 *
	 * @return void
	 */
	public function test_places_author_photo_is_required_when_available() {
		$this->assertTrue( Widget_Display::show_reviewer_photo( true, false, false, 'https://example.com/avatar.jpg' ) );
		$this->assertFalse( Widget_Display::show_reviewer_photo( false, false, false, 'https://example.com/avatar.jpg' ) );
		$this->assertFalse( Widget_Display::show_reviewer_photo( true, true, true, 'https://example.com/avatar.jpg' ) );
		$this->assertFalse( Widget_Display::show_reviewer_photo( true, false, true, '' ) );
	}

	/**
	 * The public notice describes Google's sample and every active filter.
	 *
	 * @return void
	 */
	public function test_places_filter_notice_describes_selection_and_filters() {
		$notice = Widget_Display::places_filter_notice(
			array(
				'limit'      => 12,
				'min_rating' => 4,
				'sort'       => 'highest',
				'text_only'  => 1,
			)
		);

		$this->assertStringContainsString( 'selected by relevance', $notice );
		$this->assertStringContainsString( 'up to 5 reviews', $notice );
		$this->assertStringContainsString( '4 stars or higher', $notice );
		$this->assertStringContainsString( 'highest rated first', $notice );
		$this->assertStringContainsString( 'Written text is required', $notice );
	}
}
