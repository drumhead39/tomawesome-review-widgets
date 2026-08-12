=== TomAwesome Review Widgets ===
Contributors: tomawesome
Tags: reviews, google reviews, testimonials, business profile, healthcare
Requires at least: 6.2
Tested up to: 7.0
Stable tag: 0.2.5
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create unlimited, independently configured review widgets for managed Google Business Profile locations and public Places sources.

== Description ==

TomAwesome Review Widgets lets you connect multiple businesses, synchronize reviews, and create unlimited shortcode widgets with independent layouts and filters.

The plugin connects directly from your WordPress server to Google. It does not use a TomAwesome cloud service, does not send review data to the plugin author, and does not include advertising or paid feature gates.

= Review sources =

* Google Business Profile: for verified locations you own or are authorized to manage. With approved Google API access and OAuth, the plugin can retrieve the complete paginated review list.
* Google Places API (New): for a public Place ID. Google currently returns at most five reviews selected by Google.

= Unlimited widget configurations =

* Grid, list, carousel, or single featured review
* Independent business/source selection
* Review count and minimum rating
* Newest, oldest, highest-rated, or randomized order
* Written reviews only
* Expandable long review text
* Reviewer photo and date controls for managed Business Profile sources; Places sources always show available author attribution
* Business rating summary
* Google business-name heading with an optional custom public heading per widget
* Read-all and leave-a-review links
* Responsive desktop, tablet, and mobile columns
* Built-in color, margin, padding, border, and corner-radius controls
* Optional custom CSS classes
* Shortcodes such as `[tomawesome_reviews id="123"]`

= Healthcare Privacy Mode =

Healthcare Privacy Mode is an optional, conservative workflow intended for healthcare and other privacy-sensitive organizations. When enabled for a widget, the plugin:

* Displays only reviews separately approved by an administrator in the Review Library.
* Requires privacy-reviewed display copy.
* Replaces the imported reviewer name with “Google Reviewer.”
* Hides reviewer photos, profile links, exact review dates, and owner responses.
* Suppresses links that could lead visitors directly back to the unsanitized review listing.
* Keeps privacy-reviewed copy separate from the untouched synchronized review.

Healthcare Privacy Mode is not available for Google Places sources because current Google Maps policies require Places reviews to retain author attribution and individual source links.

This feature is a technical safeguard. It is not legal advice, a certification, or a guarantee that a site or organization complies with HIPAA or any other law. Site owners remain responsible for authorizations, policies, risk analysis, workforce training, Google API terms, and legal review.

= Data handling =

API credentials and OAuth tokens are encrypted locally using a key derived from the site's WordPress salts. Imported review content is stored in the site's WordPress database for no more than 30 days and is refreshed during synchronization. Owner responses are not stored.

The plugin adds suggested disclosure text to Tools > Privacy Policy Guide.

= External services =

This plugin connects to Google services only after an administrator configures a Google connection or API key.

For Google Business Profile, the plugin sends the administrator through Google's OAuth consent flow, sends the OAuth client credentials and authorization code to Google's token endpoint, and sends authorized account/location identifiers to Google Business Profile API endpoints. Google returns account, location, aggregate rating, reviewer attribution, rating, review text, and review timestamps. Requests occur when an administrator connects or discovers locations, during a manual synchronization, and during the daily scheduled synchronization.

For Places API (New), the plugin sends the configured Place ID and API key to Google. Google returns Place details, aggregate rating data, reviewer attribution, review text, timestamps, individual review links, and any required third-party data-provider attributions. Requests occur during manual and daily synchronization.

For Places sources, available reviewer photos and profile links are required attribution and are always displayed; a visitor's browser requests those images from Google's URL. For managed Business Profile sources, reviewer photos remain optional and Healthcare Privacy Mode prevents them from loading.

Sites that publish Places API content must maintain publicly accessible Terms of Use and a Privacy Policy that incorporate Google's Terms of Service and Privacy Policy. Places widgets display Google Maps attribution, all available author attribution, returned data-provider attribution, individual review links, and a description of review selection and filtering.

= Why Places widgets contain extra public information =

Google's current Places policies require sites displaying Places reviews to identify Google Maps as the content source, credit authors and returned data providers, give visitors direct access to each source review, and clearly explain how the reviews are selected, filtered, and ordered. The plugin enforces these elements only when a widget uses a Google Places source. They are not advertising and should not be hidden with CSS or removed by a theme override.

The selection/filter notice is placed beside the Google Maps attribution in the widget footer and updates automatically with the widget settings. For example: “Google selects up to five reviews by relevance; this widget shows written 5-star reviews, newest first.”

After Business Profile API access is approved, import and synchronize the managed location as a new Review Source, then edit the widget and select that managed source. The Places selection notice, Google Maps Places attribution, returned provider credit, and individual Places-review links disappear automatically. Managed sources can retrieve the complete review list, make reviewer photos optional, and allow Healthcare Privacy Mode.

Google terms and policies: https://policies.google.com/terms

Google privacy policy: https://policies.google.com/privacy

Business Profile API policies: https://developers.google.com/my-business/content/policies

Places API policies and attribution: https://developers.google.com/maps/documentation/places/web-service/policies

TomAwesome Review Widgets is not affiliated with, sponsored by, or endorsed by Google LLC. Google and Google Business Profile are trademarks of Google LLC.

== Installation ==

1. Upload the plugin ZIP through Plugins > Add New > Upload Plugin, or copy the `tomawesome-review-widgets` directory into `/wp-content/plugins/`.
2. Activate TomAwesome Review Widgets.
3. WordPress opens Review Widgets > Getting Started with a setup checklist and two connection paths.
4. Choose a connection method:
   * For complete reviews from managed businesses, configure an approved Google Business Profile API project and OAuth Web application.
   * For a public Place, configure a Places API (New) key. Google returns up to five selected reviews.
5. Add or import at least one Review Source and use Synchronize now. After updating from a version earlier than 0.2.4, synchronize each Places source once to retrieve the current attribution fields and individual review links.
6. Go to Review Widgets > Add New, name and configure the widget, then publish it.
7. Copy the generated shortcode into a page, post, or page-builder shortcode element.

The Getting Started page remains available in the Review Widgets menu. The detailed Google Cloud and OAuth setup guide is also included at `docs/INSTALLATION.md` in the plugin package.

== Frequently Asked Questions ==

= Can I create more than one widget? =

Yes. Widgets are unlimited and each has its own source, layout, filters, responsive columns, and display controls.

= Can one site display reviews for multiple businesses? =

Yes. One connected Google account can import multiple managed Business Profile locations. You may also configure multiple public Places sources.

= Why does Places mode show only a few reviews? =

The Places API currently returns at most five reviews selected by Google. Use the Business Profile connection for a complete review list from a business you manage.

= Why does my Places widget show a review-selection notice and extra Google Maps links? =

Google's Places policies require a clear description of review selection, filtering, and ordering, along with Google Maps attribution, available author attribution, returned provider credit, and direct access to each individual source review. The plugin places the concise notice beside the Google Maps attribution in the footer and updates it when the widget's count, minimum rating, written-text requirement, or order changes.

These elements are tied to the selected source type and are not optional styling controls. Do not hide them with Custom CSS. Open Review Widgets > Getting Started > Public Places for the full explanation and current policy link.

= What happens when I switch a widget to a managed Business Profile source? =

First import and synchronize the managed location as a new source; do not convert the existing Places source. Edit the widget, select the new managed Business Profile source, and update it. The Places selection notice, Places-specific Google Maps attribution, returned provider credit, and individual Places-review links disappear automatically on the next page load. Reviewer photos become optional, Healthcare Privacy Mode becomes available, and the managed source can retrieve the complete paginated review list.

= Can Healthcare Privacy Mode be used with a Places source? =

No. Current Google Maps policies require Places reviews to retain available author attribution and an individual source link. Select a managed Business Profile source before enabling the privacy workflow.

= Is Healthcare Privacy Mode HIPAA compliant? =

No plugin can certify an organization's HIPAA compliance. The mode enforces privacy-oriented display restrictions and a manual approval workflow, but the site owner must determine whether each use is lawful and consistent with Google terms.

= Does the plugin send data to TomAwesome? =

No. There is no TomAwesome proxy, analytics service, telemetry, advertising service, or paid account. Configured API requests go directly between the WordPress site and Google.

= How often are reviews refreshed? =

WordPress schedules a daily synchronization. Administrators can also synchronize a source manually. WordPress Cron depends on site traffic; sites with low traffic may use a real server cron to call `wp-cron.php`.

= How long are Google reviews stored? =

No more than 30 days. Successful synchronizations renew the local performance cache. Expired content is removed even after a failed synchronization.

= What happens when I uninstall the plugin? =

By default, saved configuration and content remain to prevent accidental data loss. Enable “Delete plugin settings...” under Review Widgets > Google Connection before uninstalling if you want all plugin data removed.

== Changelog ==

= 0.2.5 =

* Shortened the Places review-selection notice and moved it beside the Google Maps attribution in the widget footer.
* Kept the notice synchronized with the active count, rating, written-text, and order filters.
* Added built-in and packaged documentation explaining every Places-only compliance element.
* Documented how Places-specific elements disappear automatically when a widget switches to a managed Business Profile source.

= 0.2.4 =

* Updated Places output for Google's August 2026 attribution requirements.
* Added Google Maps and returned data-provider attribution.
* Added a direct Google Maps link to every Places review.
* Made available Places author attribution mandatory and added a selection/filter notice.
* Disabled Healthcare Privacy Mode for Places sources.
* Requires one Places source synchronization after upgrade before cached Places content renders.

= 0.2.3 =

* Added per-widget color pickers for the widget, review cards, text, accents, stars, and borders.
* Added visual margin, padding, border-width, and corner-radius controls.
* Existing widgets keep their current styling until custom values are entered.
* Clarified the advanced custom CSS class field.

= 0.2.2 =

* Replaced visitor-facing internal source titles with the synchronized Google business name.
* Added an optional per-widget Public heading override.
* Added a generic Google Reviews fallback so internal source labels are never exposed publicly.

= 0.2.1 =

* Replaced raw Business Profile quota errors with actionable administrator guidance.
* Added direct links to check Google Cloud quota and apply for Basic API Access.
* Distinguished zero quota pending API approval from a temporary rate limit.
* Preserved Google's technical response for troubleshooting.
* Added quota-error handling tests.

= 0.2.0 =

* Added a guided Getting Started screen and live setup progress.
* Added an automatic onboarding redirect after activation.
* Added direct Getting Started and Google Connection links on the Plugins screen.
* Expanded the managed Business Profile setup instructions for first-time Google Cloud users.
* Clarified API-project approval, OAuth test users, redirect URIs, and production readiness.
* Corrected administrator coding-standard issues and release packaging exclusions.

= 0.1.0 =

* Initial developer preview.
* Added multiple Business Profile and Places sources.
* Added unlimited grid, list, carousel, and featured-review shortcode widgets.
* Added encrypted local credential storage and daily/manual synchronization.
* Added 30-day API-content retention enforcement.
* Added Healthcare Privacy Mode with manual privacy-copy approval.
* Added responsive, accessible front-end rendering and reduced-motion support.

== Upgrade Notice ==

= 0.2.5 =

Places widgets now use a shorter footer notice. No source synchronization or widget reconfiguration is required.

= 0.2.4 =

Synchronize each Places source once after updating. Until then, its widget is hidden from visitors so older cached content cannot omit Google's current required attribution and individual review links.

= 0.2.3 =

Adds optional visual styling controls directly to the widget editor without changing existing widget designs.

= 0.2.2 =

Internal review-source titles are no longer exposed in public widget summaries. Synchronize each source once to store Google's business name, or set a custom Public heading in the widget.

= 0.2.1 =

Adds clear next steps when Google blocks managed-location discovery because API quota is zero or temporarily exhausted.

= 0.2.0 =

Adds first-run onboarding and a substantially clearer Google Business Profile setup guide. Continue testing on a staging site.

= 0.1.0 =

Developer preview. Test Google connectivity and widget output on a staging site before production use.
