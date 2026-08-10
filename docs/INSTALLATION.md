# Installation and Google connection guide

TomAwesome Review Widgets supports two Google connection methods. Use Google Business Profile for businesses you manage and Places API (New) for a public Place ID.

## 1. Install the WordPress plugin

1. In WordPress, open **Plugins > Add New > Upload Plugin**.
2. Select the `tomawesome-review-widgets.zip` file.
3. Choose **Install Now**, then **Activate Plugin**.
4. Open **Review Widgets > Google Connection**.

The site must run WordPress 6.2 or newer, PHP 7.4 or newer, HTTPS, and PHP OpenSSL. HTTPS protects the OAuth callback and OpenSSL allows the plugin to encrypt credentials at rest.

## 2A. Complete-review setup for managed businesses

Google Business Profile is the recommended source when you own or are authorized to manage the listing. Google requires application approval before this API becomes available.

### Prepare Google Cloud

1. Sign in with a Google account that manages a verified, active Business Profile.
2. Create or choose a Google Cloud project owned by the appropriate organization.
3. Complete Google's [Business Profile API prerequisites](https://developers.google.com/my-business/content/prereqs), including its access application. Do not continue until Google approves the project.
4. Enable at least these APIs in the approved project:
   - Google My Business API
   - My Business Account Management API
   - My Business Business Information API
5. Configure the OAuth consent screen with accurate application, support, homepage, privacy-policy, and terms links.
6. Add the scope `https://www.googleapis.com/auth/business.manage`.
7. Create an OAuth client with application type **Web application**.
8. In WordPress, copy the exact **Authorized redirect URI** shown under **Review Widgets > Google Connection**.
9. Add that URI to the OAuth client's **Authorized redirect URIs** in Google Cloud. The scheme, domain, path, and query string must match exactly.

Public OAuth applications may require Google verification. The site owner—not the plugin author—owns and controls this Google Cloud project and is responsible for Google's current access, consent-screen, verification, and policy requirements.

This is self-hosted open-source software: each site operator independently owns and operates its API project. Do not share one approved project, credentials, or indirect API access across unrelated client sites. Agencies and platforms should review Google's restrictions for end-client and third-party use and contact Google before deploying this workflow for clients.

### Connect WordPress

1. Copy the OAuth client ID and client secret from Google Cloud.
2. Paste both under **Review Widgets > Google Connection** and save.
3. Choose **Connect Google Business Profile**.
4. Sign in manually, review Google's consent screen, and approve access.
5. Back in WordPress, choose **Discover managed locations**.
6. Select **Add as review source** beside each needed location.
7. Open each imported source, verify its “Read all reviews” and “Leave a review” URLs, then choose **Synchronize now**.

One OAuth connection can provide multiple managed locations. Each imported location is an independent Review Source.

## 2B. Public Places setup

Places mode works without managing the business, but Google currently supplies at most five reviews selected by relevance.

1. In Google Cloud, create or choose a project with billing configured.
2. Enable **Places API (New)**.
3. Create an API key.
4. Restrict the key to **Places API (New)**. Because requests are server-side, HTTP-referrer restrictions do not apply. If the server has a stable outbound IP address, add an IP restriction too.
5. Save the key under **Review Widgets > Google Connection**.
6. Find the business's Google Place ID using Google's official Place ID tools.
7. Open **Review Widgets > Review Sources > Add New**.
8. Enter an internal source title, choose **Google Places**, paste the Place ID, publish, and choose **Synchronize now**.

Never put the Places API key in a page, shortcode, theme file, or client-side JavaScript. This plugin sends it only in server-side requests.

## 3. Create a widget

1. Open **Review Widgets > Add New**.
2. Enter an internal title such as “Homepage three-column reviews.”
3. Choose the source, layout, review count, rating threshold, order, responsive columns, and display options.
4. Publish the widget.
5. Copy its shortcode, such as `[tomawesome_reviews id="123"]`.
6. Paste the shortcode into a WordPress Shortcode block or a page builder's shortcode element.

Create additional widgets for other pages, layouts, or businesses. They may reuse the same source without making additional front-end API calls; widgets render from the local, time-limited cache.

## 4. Healthcare Privacy Mode

1. Synchronize the source.
2. Open **Review Widgets > Review Library**.
3. Read the original review and create privacy-reviewed display copy that removes identifying details.
4. Complete your organization's authorization and legal review.
5. Check the approval box and save.
6. Enable **Healthcare Privacy Mode (HIPAA-conscious)** in the widget.

The widget will now show only approved privacy copy and will suppress reviewer identity, photos, profile links, exact dates, and owner responses. Read `HEALTHCARE-PRIVACY-MODE.md` before using the feature.

## 5. Synchronization and troubleshooting

- WordPress schedules synchronization once daily and offers a manual button on each source.
- WordPress Cron runs when the site receives traffic. A low-traffic site can configure a real server cron to request `wp-cron.php`.
- Imported API content expires after 30 days. An expired review will not display until a successful sync renews it.
- An OAuth `redirect_uri_mismatch` error means the URI in Google Cloud does not exactly match the URI shown by the plugin.
- A Business Profile `403` or zero quota normally means the Google Cloud project is not approved, the needed API is disabled, the signed-in user lacks listing access, or Business Profile is disabled by a Workspace administrator.
- If WordPress salts change, save the API credentials and reconnect Google because existing encrypted secrets can no longer be decrypted.

Google documentation changes over time. Recheck the [Business Profile setup](https://developers.google.com/my-business/content/basic-setup), [Business Profile policies](https://developers.google.com/my-business/content/policies), and [Places policies](https://developers.google.com/maps/documentation/places/web-service/policies) before production use.
