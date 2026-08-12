# Installation and Google connection guide

TomAwesome Review Widgets supports two Google connection methods. Use Google Business Profile for businesses you manage and Places API (New) for a public Place ID.

## 1. Install the WordPress plugin

1. In WordPress, open **Plugins > Add New > Upload Plugin**.
2. Select the `tomawesome-review-widgets.zip` file.
3. Choose **Install Now**, then **Activate Plugin**.
4. WordPress will open **Review Widgets > Getting Started**. Choose the connection method that fits your use case.

You can return to the onboarding checklist at any time from **Review Widgets > Getting Started**. The Plugins screen also includes **Getting Started** and **Google Connection** links beneath the plugin name.

The site must run WordPress 6.2 or newer, PHP 7.4 or newer, HTTPS, and PHP OpenSSL. HTTPS protects the OAuth callback and OpenSSL allows the plugin to encrypt credentials at rest.

## 2A. Complete-review setup for managed businesses

Google Business Profile is the recommended source when you own or are authorized to manage the listing. It can provide the complete, paginated review history instead of the small sample returned by Places.

There are **two separate Google approval concepts** in this setup:

1. **Business Profile API access** gives one Google Cloud project permission and quota to call the API. You request this through Google's Business Profile API access form.
2. **OAuth publishing or verification** governs who may authorize the application. You configure this under Google Auth Platform. Testing mode is enough for an initial connection, but it is not suitable for dependable long-term synchronization.

Finishing one does not automatically finish the other.

### 2A-1. Confirm that the Google account is eligible

Use a Google account listed as an **Owner** or **Manager** of the Business Profile:

1. Sign in to Google and search for the business name or `my business`.
2. Open the three-dot menu in the private Business Profile controls.
3. Choose **Business Profile settings > People and access**.
4. Confirm the email address shown as an Owner or Manager. Use that exact account for the access application, OAuth test user, and WordPress connection.

Google currently requires applicants to manage a verified, active Business Profile that has existed for at least 60 days and has a website representing the business. Review [Google's current prerequisites](https://developers.google.com/my-business/content/prereqs) before applying.

### 2A-2. Create a dedicated Google Cloud project

1. Open [Google Cloud Console](https://console.cloud.google.com/).
2. Use the project selector in the top bar and choose **New Project**.
3. Give the project a recognizable name such as `Example Business Review Widgets`.
4. Create the project and make sure it remains selected for every following step.
5. Open **Cloud Overview > Dashboard** and locate **Project info**.
6. Copy the numeric **Project number**. Do not substitute the project name or project ID; Google's access form asks for the project number.

Google grants Business Profile API access to this specific project. Creating OAuth credentials in a different project will not inherit the approval.

### 2A-3. Apply for Business Profile API access

1. With the correct project selected, open Google's [Business Profile API access form](https://support.google.com/business/contact/api_default).
2. Choose **Application for Basic API Access** from the request type.
3. Complete the form using the Google account that owns or manages the Business Profile.
4. Enter the project number copied in the previous section.
5. Submit the request and wait for Google's response before attempting to connect the plugin.

Approval is not immediate and the plugin developer cannot approve the project. Google says you can check the result in Google Cloud by viewing the Business Profile API quota:

- **0 QPM** means access has not been granted.
- **300 QPM** means the project has been approved.

Do not request a quota increase when the quota is zero; complete the basic-access application instead.

### 2A-4. Enable the Business Profile APIs

After Google approves the project:

1. Open **APIs & Services > Library** in Google Cloud.
2. Search for each API below, open it, and choose **Enable**:
   - Google My Business API
   - My Business Account Management API
   - My Business Lodging API
   - My Business Place Actions API
   - My Business Notifications API
   - My Business Verifications API
   - My Business Business Information API
   - My Business Q&A API
3. Confirm the APIs appear under **APIs & Services > Enabled APIs & services**.

Google currently lists all eight as part of the required Business Profile suite. See Google's [current Basic setup page](https://developers.google.com/my-business/content/basic-setup#enable-the-apis) if an API name or menu changes.

The **Google My Business API** may not appear in the API Library until Google approves the account and project.

### 2A-5. Configure Google Auth Platform

1. In the same Cloud project, open **Google Auth Platform > Overview**.
2. If Google displays **Get started**, complete the initial form:
   - Enter an accurate application name. This is what users will see on Google's authorization screen.
   - Select a monitored **User support email**.
   - Choose **External** as the audience unless every authorized user belongs to the same eligible Google Workspace organization.
   - Enter a monitored developer contact email.
3. Open **Branding** and add accurate application information:
   - Homepage URL
   - Privacy Policy URL
   - Terms of Service URL
   - Authorized domain for the WordPress site
4. Open **Audience**. For the first connection, leave the publishing status as **Testing**.
5. Under **Test users**, add the exact Google account identified in section 2A-1. A manager using a non-Gmail address may still have a Google Account; enter the actual address shown in Google.
6. Open **Data Access**, choose **Add or remove scopes**, and add:

   ```text
   https://www.googleapis.com/auth/business.manage
   ```

If Google displays “Access blocked” during the first connection, the most common cause is that the signed-in account was not added under **Audience > Test users**, or a different Cloud project was configured.

### 2A-6. Create the OAuth web client

1. Open **Google Auth Platform > Clients**.
2. Choose **Create client**.
3. For **Application type**, select **Web application**.
4. Enter a recognizable name such as `Review Widgets on example.com`.
5. In WordPress, open **Review Widgets > Google Connection**.
6. Copy the complete **Authorized redirect URI** displayed by the plugin.
7. Back in Google Cloud, paste it under **Authorized redirect URIs**. Do not place it under Authorized JavaScript origins.
8. Choose **Create**.
9. Copy the generated **Client ID** and **Client secret**. Do not publish either value or commit it to GitHub.

The redirect URI must be an exact match. The protocol (`https`), domain, path, and query string all matter. A mismatch produces Google's `redirect_uri_mismatch` error.

### 2A-7. Connect WordPress and import locations

1. In WordPress, open **Review Widgets > Google Connection**.
2. Paste the OAuth Client ID and Client secret and choose **Save connection settings**.
3. Choose **Connect Google Business Profile**.
4. At Google, select the same Owner or Manager account that you added as a test user.
5. Review the requested access and approve it.
6. After WordPress reports a successful connection, choose **Discover managed locations**.
7. Select **Add as review source** beside each location you want to use.
8. Open each imported source and verify its **Read all reviews** and **Leave a review** URLs.
9. Choose **Synchronize now**.

One OAuth connection can provide multiple managed locations. Each imported location becomes an independent Review Source that may be reused by any number of widgets.

### 2A-8. Move beyond Testing before relying on automatic sync

Testing mode is useful for confirming the setup, but Google generally expires refresh tokens after seven days when an External app requests scopes beyond basic identity information. When that token expires, daily synchronization stops until an administrator reconnects.

Before production use, open **Google Auth Platform > Audience** and review Google's current publishing and verification requirements. The appropriate route depends on whether the OAuth app is strictly for the site owner's limited personal use or will be offered to additional users. Public or broadly distributed OAuth applications may require Google verification.

The site owner—not the plugin author—owns and controls the Google Cloud project and is responsible for Google's current access, consent-screen, verification, and policy requirements.

This is self-hosted open-source software: each site operator independently owns and operates its API project. Do not share one approved project, credentials, or indirect API access across unrelated client sites. Agencies and platforms should review Google's restrictions for end-client and third-party use and contact Google before deploying this workflow for clients.

## 2B. Public Places setup

Places mode works without managing the business, but Google currently supplies at most five reviews selected by relevance.

Before publishing Places content, make sure the website provides publicly accessible Terms of Use and a Privacy Policy that incorporate [Google's Terms of Service](https://policies.google.com/terms) and [Google's Privacy Policy](https://policies.google.com/privacy). Do not hide or remove the widget's Google Maps attribution, returned provider attribution, author information, individual-review links, or review-selection/filter notice. Review [Google's current Places policies and attribution requirements](https://developers.google.com/maps/documentation/places/web-service/policies) and the packaged [`PLACES-COMPLIANCE.md`](PLACES-COMPLIANCE.md) guide.

1. In Google Cloud, create or choose a project with billing configured.
2. Enable **Places API (New)**.
3. Create an API key.
4. Restrict the key to **Places API (New)**. Because requests are server-side, HTTP-referrer restrictions do not apply. If the server has a stable outbound IP address, add an IP restriction too.
5. Save the key under **Review Widgets > Google Connection**.
6. Find the business's Google Place ID using Google's official Place ID tools.
7. Open **Review Widgets > Review Sources > Add New**.
8. Enter an internal source title, choose **Google Places**, paste the Place ID, publish, and choose **Synchronize now**.

After installing version 0.2.4 or any future update that changes Places attribution handling, synchronize every Places source once. Until that succeeds, the plugin hides its Places widget from visitors and shows administrators a synchronization reminder. This prevents older cached records from being displayed without newly required fields.

Never put the Places API key in a page, shortcode, theme file, or client-side JavaScript. This plugin sends it only in server-side requests.

### Why Places widgets display extra public information

Google's Places policies require a clear review-selection/filter notice in addition to Google Maps attribution, available author attribution, returned data-provider credit, and direct access to each individual source review. Version 0.2.5 places the concise notice beside the Google Maps attribution in the widget footer. Its wording updates automatically when the widget's review count, minimum rating, written-text requirement, or ordering changes.

These are Places-only elements. After Business Profile API access is approved:

1. Connect Google Business Profile under **Review Widgets > Google Connection**.
2. Discover, import, and synchronize the managed location as a new source. Do not convert the existing Places source.
3. Edit the widget and select the new managed Business Profile source.
4. Update the widget.

On the next page load, the plugin automatically removes the Places selection notice, Google Maps Places attribution, returned provider credit, and individual Places-review links. The managed source can retrieve the complete review list, makes reviewer photos optional, and allows Healthcare Privacy Mode.

## 3. Create a widget

1. Open **Review Widgets > Add New**.
2. Enter an internal title such as “Homepage three-column reviews.”
3. Choose the source, layout, review count, rating threshold, order, responsive columns, and display options.
4. Publish the widget.
5. Copy its shortcode, such as `[tomawesome_reviews id="123"]`.
6. Paste the shortcode into a WordPress Shortcode block or a page builder's shortcode element.

Create additional widgets for other pages, layouts, or businesses. They may reuse the same source without making additional front-end API calls; widgets render from the local, time-limited cache.

## 4. Healthcare Privacy Mode

Healthcare Privacy Mode cannot be used with a Google Places source because it suppresses author identity while Google Maps requires Places reviews to retain author attribution and individual source links. Use a managed Google Business Profile source for this workflow.

1. Synchronize the source.
2. Open **Review Widgets > Review Library**.
3. Read the original review and create privacy-reviewed display copy that removes identifying details.
4. Complete your organization's authorization and legal review.
5. Check the approval box and save.
6. Enable **Healthcare Privacy Mode (HIPAA-conscious)** in a widget using the managed Business Profile source.

The widget will now show only approved privacy copy and will suppress reviewer identity, photos, profile links, exact dates, and owner responses. Read `HEALTHCARE-PRIVACY-MODE.md` before using the feature.

## 5. Synchronization and troubleshooting

- WordPress schedules synchronization once daily and offers a manual button on each source.
- WordPress Cron runs when the site receives traffic. A low-traffic site can configure a real server cron to request `wp-cron.php`.
- Imported API content expires after 30 days. An expired review will not display until a successful sync renews it.
- An OAuth `redirect_uri_mismatch` error means the URI in Google Cloud does not exactly match the URI shown by the plugin.
- If **Discover managed locations** reports unavailable or exhausted quota, open the linked Account Management API quota page and check **Requests per minute**. A limit of `0` usually means Google has not granted Basic API Access; submit the linked access application and wait for approval. If the limit is above `0`, wait at least one minute and retry. Confirm that the approved project is the same project that owns the OAuth client.
- A Business Profile `403` without a quota message normally means the needed API is disabled, the signed-in user lacks listing access, or Business Profile is disabled by a Workspace administrator.
- If WordPress salts change, save the API credentials and reconnect Google because existing encrypted secrets can no longer be decrypted.

Google documentation changes over time. Recheck the [Business Profile setup](https://developers.google.com/my-business/content/basic-setup), [Business Profile policies](https://developers.google.com/my-business/content/policies), and [Places policies](https://developers.google.com/maps/documentation/places/web-service/policies) before production use.
