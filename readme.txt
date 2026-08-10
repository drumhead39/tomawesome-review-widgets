=== TomAwesome Review Widgets ===
Contributors: tomawesome
Tags: reviews, google reviews, testimonials, business profile, healthcare
Requires at least: 6.2
Tested up to: 7.0
Stable tag: 0.1.0
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
* Reviewer photo and date controls
* Business rating summary
* Read-all and leave-a-review links
* Responsive desktop, tablet, and mobile columns
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

This feature is a technical safeguard. It is not legal advice, a certification, or a guarantee that a site or organization complies with HIPAA or any other law. Site owners remain responsible for authorizations, policies, risk analysis, workforce training, Google API terms, and legal review.

= Data handling =

API credentials and OAuth tokens are encrypted locally using a key derived from the site's WordPress salts. Imported review content is stored in the site's WordPress database for no more than 30 days and is refreshed during synchronization. Owner responses are not stored.

The plugin adds suggested disclosure text to Tools > Privacy Policy Guide.

= External services =

This plugin connects to Google services only after an administrator configures a Google connection or API key.

For Google Business Profile, the plugin sends the administrator through Google's OAuth consent flow, sends the OAuth client credentials and authorization code to Google's token endpoint, and sends authorized account/location identifiers to Google Business Profile API endpoints. Google returns account, location, aggregate rating, reviewer attribution, rating, review text, and review timestamps. Requests occur when an administrator connects or discovers locations, during a manual synchronization, and during the daily scheduled synchronization.

For Places API (New), the plugin sends the configured Place ID and API key to Google. Google returns Place details, aggregate rating data, reviewer attribution, review text, and timestamps. Requests occur during manual and daily synchronization.

When a standard widget is configured to show reviewer photos, a visitor's browser requests those images from the Google-provided URL. Healthcare Privacy Mode always prevents reviewer photos from loading.

Google terms and policies: https://policies.google.com/terms

Google privacy policy: https://policies.google.com/privacy

Business Profile API policies: https://developers.google.com/my-business/content/policies

Places API policies and attribution: https://developers.google.com/maps/documentation/places/web-service/policies

TomAwesome Review Widgets is not affiliated with, sponsored by, or endorsed by Google LLC. Google and Google Business Profile are trademarks of Google LLC.

== Installation ==

1. Upload the plugin ZIP through Plugins > Add New > Upload Plugin, or copy the `tomawesome-review-widgets` directory into `/wp-content/plugins/`.
2. Activate TomAwesome Review Widgets.
3. Go to Review Widgets > Google Connection.
4. Choose a connection method:
   * For complete reviews from managed businesses, configure an approved Google Business Profile API project and OAuth Web application.
   * For a public Place, configure a Places API (New) key. Google returns up to five selected reviews.
5. Add or import at least one Review Source and use Synchronize now.
6. Go to Review Widgets > Add New, name and configure the widget, then publish it.
7. Copy the generated shortcode into a page, post, or page-builder shortcode element.

The detailed setup guide is included at `docs/INSTALLATION.md` in the plugin package.

== Frequently Asked Questions ==

= Can I create more than one widget? =

Yes. Widgets are unlimited and each has its own source, layout, filters, responsive columns, and display controls.

= Can one site display reviews for multiple businesses? =

Yes. One connected Google account can import multiple managed Business Profile locations. You may also configure multiple public Places sources.

= Why does Places mode show only a few reviews? =

The Places API currently returns at most five reviews selected by Google. Use the Business Profile connection for a complete review list from a business you manage.

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

= 0.1.0 =

* Initial developer preview.
* Added multiple Business Profile and Places sources.
* Added unlimited grid, list, carousel, and featured-review shortcode widgets.
* Added encrypted local credential storage and daily/manual synchronization.
* Added 30-day API-content retention enforcement.
* Added Healthcare Privacy Mode with manual privacy-copy approval.
* Added responsive, accessible front-end rendering and reduced-motion support.

== Upgrade Notice ==

= 0.1.0 =

Developer preview. Test Google connectivity and widget output on a staging site before production use.
