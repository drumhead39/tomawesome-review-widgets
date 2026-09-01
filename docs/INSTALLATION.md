# TomAwesome Review Widgets: Beginner Installation and Google Setup Guide

This guide is intentionally detailed. It is written for a WordPress administrator who has never used Google Cloud Console before.

## Read this before installing

TomAwesome Review Widgets does **not** display Google reviews immediately after activation.

The plugin is self-hosted and does not operate a TomAwesome cloud service. It does not include a shared Google API key, a preapproved Google Cloud project, or a universal Google login. Before the plugin can retrieve reviews, the site owner must create and configure their own Google connection.

There are two different setup paths:

| Setup path | Difficulty | Google approval required? | Reviews available | Best for |
| --- | --- | --- | --- | --- |
| **Google Places API** | Easier | No Business Profile approval or OAuth | At most five reviews selected by Google | A faster setup, public businesses, or sites that only need a small review sample |
| **Managed Business Profile** | Advanced | Yes | Complete paginated review list for locations you own or manage | Full review access, multiple managed locations, and Healthcare Privacy Mode |

### The honest time expectation

- **Places setup** can often be completed in one working session, but it still requires a Google Cloud project, billing, Places API (New), an API key, and a Place ID.
- **Managed Business Profile setup is substantially harder.** It requires an eligible verified Business Profile, a dedicated Cloud project, a Google Basic API Access application, an outside approval wait, seven enabled APIs, Google Auth Platform configuration, an OAuth web client, and a successful WordPress connection.
- Google controls managed-project approval. It may take days or longer, and Google does not guarantee a completion date. Installing the plugin does not begin or accelerate that review.
- Google Cloud menu names and policies can change. The linked Google documentation is authoritative when a screen differs from this guide.

If five Google-selected reviews are enough, begin with **Path A: Google Places API**. You can switch a widget to a managed source later without rebuilding the widget.

If you need the complete review list, read **Path B: Managed Business Profile** all the way through before starting.

## What the plugin can and cannot do

### The plugin can

- Store Google credentials on your own WordPress site.
- Connect directly from your WordPress server to Google.
- Create multiple review sources and unlimited shortcode widgets.
- Synchronize reviews manually and once daily through WordPress Cron.
- Retrieve the complete review list from a properly approved and connected managed Business Profile.
- Retrieve the limited review sample returned by Places API (New).

### The plugin cannot

- Supply Google API credentials for you.
- Approve a Google Cloud project or speed up Google's review.
- Bypass Google billing, quotas, OAuth, attribution, or API policies.
- Retrieve managed reviews before Google approves and correctly configures the same project used by the OAuth client.
- Force Places API to return more than the reviews Google selects.
- Guarantee legal, privacy, HIPAA, or Google-policy compliance.

## Part 1: Install the WordPress plugin

1. Sign in to WordPress as an Administrator.
2. Open **Plugins > Add New Plugin**.
3. Select **Upload Plugin**.
4. Choose the TomAwesome Review Widgets ZIP file.
5. Select **Install Now**.
6. Select **Activate Plugin**.
7. WordPress opens **Review Widgets > Getting Started**.

Activation creates the plugin's local storage and schedules daily synchronization. It does **not** contact Google or download reviews.

Choose one Google setup path below.

---

# Path A: Google Places API — easier, but limited

Choose Places when five Google-selected reviews are enough, when you do not manage the Business Profile, or when you want to get started without Business Profile API approval and OAuth.

## Places limitations you must understand

- Google currently returns at most five reviews selected by relevance.
- The plugin cannot ask Google for a different or complete set.
- Places widgets must display required Google Maps, reviewer, provider, individual-review, and selection/filter attribution.
- Healthcare Privacy Mode is unavailable because it would hide attribution Google requires.
- Your website must maintain public Terms of Use and a Privacy Policy that incorporate Google's Terms of Service and Privacy Policy.
- Google Cloud billing is required, and Google may charge for API usage under its current pricing.

## A1. Create a Google Cloud project

1. Sign in to the Google account that will own the project.
2. Open [Google Cloud Console](https://console.cloud.google.com/).
3. At the top of the page, select the current project name. If no project is selected, select **Select a project**.
4. Select **New Project**.
5. Enter a recognizable name, such as **Reviews for example.com**.
6. Select **Create**.
7. Wait for Google to finish creating it.
8. Open the project selector again and select the new project.

Always confirm the correct project name appears in the top bar before changing an API, key, billing setting, or OAuth setting.

## A2. Attach a billing account

1. With the new project selected, open the navigation menu.
2. Open **Billing**.
3. Link an existing billing account or follow Google's steps to create one.
4. Return to the project after billing is linked.

TomAwesome does not receive billing information or Google API payments. Review [Google Maps Platform pricing](https://mapsplatform.google.com/pricing/) before production use.

## A3. Enable Places API (New)

1. Open **APIs & Services > Library**.
2. Search for **Places API (New)**.
3. Open the result whose exact name is **Places API (New)**.
4. Select **Enable**.

If the button says **Manage**, the API is already enabled.

## A4. Create an API key

1. Open **APIs & Services > Credentials**.
2. Select **Create Credentials**.
3. Select **API key**.
4. Copy the key and keep it private.
5. Select **Edit API key**.
6. Give it a recognizable name, such as **WordPress Review Widgets**.
7. Under **API restrictions**, choose **Restrict key**.
8. Select only **Places API (New)**.
9. Save the key.

### Important restriction warning

The plugin sends Places requests from the WordPress server, not from the visitor's browser. A **Websites/HTTP referrer** application restriction normally blocks these server-side requests.

For the first test, restrict the key to Places API (New) and leave the application restriction unset. After synchronization works, ask the web host whether the site has a fixed outbound server IP. If it does, an IP-address restriction may be appropriate.

## A5. Find the business's Place ID

1. Open Google's [Place ID documentation and finder](https://developers.google.com/maps/documentation/places/web-service/place-id).
2. Search for the exact business.
3. Confirm the business name and address.
4. Copy its Place ID.

A Place ID is not the business name, website URL, Google Maps URL, or phone number.

## A6. Save the Places key in WordPress

1. Return to WordPress.
2. Open **Review Widgets > Google Connection**.
3. Paste the API key into **Places API key**.
4. Select **Save connection settings**.

## A7. Add and synchronize the Places source

1. Open **Review Widgets > Review Sources > Add New**.
2. Enter an internal title, such as **Places - Main Office**.
3. Choose **Google Places** as the source type.
4. Paste the Place ID.
5. Leave the source enabled.
6. Select **Publish**.
7. Select **Synchronize now**.
8. Confirm that WordPress reports **Reviews synchronized successfully**.

If synchronization fails, read the exact **Last error** shown on the source screen. It usually identifies a disabled API, invalid key, billing problem, key restriction, or Place ID problem.

## A8. Create the widget

Continue to [Part 2: Create and place a widget](#part-2-create-and-place-a-widget).

---

# Path B: Managed Business Profile — advanced, complete access

Choose this path only when:

- Your Google account is an Owner or Manager of the Business Profile.
- You need the complete review list, multiple managed locations, or Healthcare Privacy Mode.
- You are prepared to configure Google Cloud and wait for Google to approve the project.

## Managed setup at a glance

1. Confirm the Business Profile is eligible and identify the correct owner/manager account.
2. Create a dedicated Google Cloud project.
3. Record both its Project ID and Project number.
4. Apply for Google Business Profile Basic API Access.
5. Wait for approval.
6. Enable all seven Business Profile APIs in the approved project.
7. Configure Google Auth Platform.
8. Create a Web application OAuth client.
9. Save the client credentials in WordPress and connect Google.
10. Discover, import, and synchronize the location.
11. Move beyond OAuth Testing before relying on daily synchronization.

Google approval and OAuth configuration are separate. Completing OAuth does not approve the API project, and enabling APIs does not approve it either.

## B1. Confirm eligibility and the correct Google account

Before creating a project:

1. Sign in to the Google account used to manage the business.
2. Open the Business Profile.
3. Open **Business Profile settings > People and access**.
4. Confirm the account is shown as an **Owner** or **Manager**.
5. Confirm the Business Profile is verified and active.
6. Confirm the business website represents the same business.

Google currently requires applicants to manage a verified, active Business Profile that has existed for at least 60 days and has a matching website. Review [Google's current prerequisites](https://developers.google.com/my-business/content/prereqs) before applying.

## B2. Create a dedicated Google Cloud project

1. Open [Google Cloud Console](https://console.cloud.google.com/).
2. Select the project name in the top bar.
3. Select **New Project**.
4. Enter a recognizable name, such as **Business Profile Reviews for example.com**.
5. Select **Create**.
6. Select the new project from the project selector.
7. Open the project **Dashboard**.
8. Record both:
   - **Project ID** — a text value such as example-review-widgets
   - **Project number** — a numeric value such as 123456789012

These are different values. Google's access and support forms may ask for both.

Use this same project for the access request, all seven APIs, Google Auth Platform, and the OAuth client. Approval belongs to the project number, not merely to the Google account.

## B3. Apply for Basic API Access

1. Confirm the dedicated project is selected.
2. Open Google's [Business Profile API access form](https://support.google.com/business/contact/api_default).
3. Choose **Application for Basic API Access**.
4. Complete the form using the Google account that owns or manages the Business Profile.
5. Enter the exact Project ID and Project number recorded above.
6. Explain the legitimate use clearly: the WordPress site will use OAuth to retrieve reviews for a Business Profile the authorized account owns or manages.
7. Submit the application.
8. Save the case ID or confirmation email.

Do not submit duplicate applications. Google controls the queue and may provide no exact completion estimate.

## B4. Wait for Google approval

Do not expect managed discovery to work while the project quota is zero.

To check the project:

1. Open **APIs & Services** in the same Cloud project.
2. Open **My Business Account Management API**.
3. Open **Quotas & System Limits**.
4. Find **Requests per minute**.

Typical result:

- **0 QPM** — Basic API Access has not been granted.
- **300 QPM** — the normal Basic API Access approval has been granted.

If Google says the request is queued, wait for the final approval email. The plugin author cannot check, approve, or accelerate Google's queue.

## B5. Enable all seven Business Profile APIs

After approval:

1. Open **APIs & Services > Library**.
2. Search for each exact API name below.
3. Open each result and select **Enable**.
4. If an API shows **Manage**, it is already enabled.

Enable:

1. **Google My Business API**
2. **My Business Account Management API**
3. **My Business Lodging API**
4. **My Business Place Actions API**
5. **My Business Notifications API**
6. **My Business Verifications API**
7. **My Business Business Information API**

The **Google My Business API** may be invisible until Google approves the account and project.

Why all three core APIs matter:

- Account Management finds the accounts the Google user manages.
- Business Information finds the locations within those accounts.
- Google My Business API retrieves the reviews.

It is therefore possible to discover a location successfully but receive no reviews if **Google My Business API** was not enabled. If Google reports that mybusiness.googleapis.com is disabled, open that exact API and enable it.

Google's current authoritative list is on its [Business Profile basic setup page](https://developers.google.com/my-business/content/basic-setup#enable-the-apis).

## B6. Configure Google Auth Platform

Keep the same approved project selected.

1. Open **Google Auth Platform > Overview**.
2. If Google shows **Get started**, select it.
3. Enter an application name users will recognize, such as **Example.com Review Widgets**.
4. Select a monitored **User support email**.
5. Choose **External** unless every authorized user belongs to the same eligible Google Workspace organization.
6. Enter a monitored developer contact email.
7. Finish the initial form.

Then configure these sections:

### Branding

1. Open **Branding**.
2. Add the application's name and support email.
3. Add the website homepage.
4. Add the website Privacy Policy URL.
5. Add the website Terms of Service URL.
6. Add the website's domain as an authorized domain when Google requests it.

### Audience

1. Open **Audience**.
2. For the first connection, leave the app in **Testing**.
3. Under **Test users**, add the exact Google account that owns or manages the Business Profile.

If a different Google account signs in, Google may block access even if that account can view the business elsewhere.

### Data Access

1. Open **Data Access**.
2. Select **Add or remove scopes**.
3. Add this exact scope:

    https://www.googleapis.com/auth/business.manage

4. Save the scope.

## B7. Create the OAuth web client

1. In WordPress, open **Review Widgets > Google Connection**.
2. Find the **Authorized redirect URI** displayed by the plugin.
3. Copy the complete URI.
4. Return to Google Cloud.
5. Open **Google Auth Platform > Clients**.
6. Select **Create client**.
7. For **Application type**, choose **Web application**.
8. Enter a recognizable name, such as **Review Widgets on example.com**.
9. Under **Authorized redirect URIs**, paste the exact URI copied from WordPress.
10. Do not put it under Authorized JavaScript origins.
11. Select **Create**.
12. Copy the generated **Client ID** and **Client secret**.

The redirect URI must match exactly. HTTPS, domain, path, query string, and trailing characters all matter.

Never publish the client secret, paste it into a public support post, or commit it to GitHub.

## B8. Save the OAuth credentials and connect Google

1. Return to **Review Widgets > Google Connection**.
2. Paste the OAuth Client ID.
3. Paste the OAuth Client secret.
4. Select **Save connection settings**.
5. Select **Connect Google Business Profile**.
6. Sign in with the same Owner or Manager account added as a test user.
7. Review Google's permission request.
8. Approve access.
9. Confirm WordPress reports that Google is connected.

If Google reports **redirect_uri_mismatch**, compare the URI in WordPress with the one stored under the OAuth client. Correct the Google client and reconnect.

## B9. Discover and import the managed location

1. In **Review Widgets > Google Connection**, select **Discover managed locations**.
2. Find the correct business and address.
3. Select **Add as review source**.
4. WordPress opens the new Review Source.

Importing a location creates an empty source. It does **not** download reviews automatically.

## B10. Synchronize the managed reviews

1. On the imported source, verify the business name and Google links.
2. Confirm the source is enabled.
3. Select **Synchronize now**.
4. Confirm WordPress reports **Reviews synchronized successfully**.
5. Open **Review Widgets > Review Library** and filter by the new source.
6. Confirm reviews are present before assigning the source to a widget.

If synchronization reports that **Google My Business API has not been used or is disabled**, enable Google My Business API in the approved project, wait several minutes for propagation, and retry.

## B11. Move beyond OAuth Testing

Testing mode is appropriate while configuring the first connection. However, Google generally expires refresh tokens after seven days when an External app in Testing requests a scope beyond basic identity information.

Before relying on daily synchronization:

1. Open **Google Auth Platform > Audience**.
2. Review Google's current publishing and verification requirements.
3. Choose the appropriate production route for the site owner's use case.
4. Reconnect WordPress if Google requires a new authorization.

Each site operator owns and controls their Google Cloud project. Do not share one approved project, client credentials, API key, or indirect API access across unrelated businesses or customer sites without confirming Google's current requirements.

## B12. Create the widget

Continue to Part 2 below.

---

# Part 2: Create and place a widget

Complete these steps only after the selected source reports a successful synchronization.

1. Open **Review Widgets > Add New**.
2. Enter an internal widget title.
3. Choose the synchronized review source.
4. Select a layout:
   - Grid
   - List
   - Carousel
   - Single featured review
5. Configure review count, minimum rating, written-text requirement, ordering, responsive columns, and display options.
6. Select **Publish**.
7. Copy the generated shortcode, such as:

    [tomawesome_reviews id="123"]

8. Edit the page where the reviews should appear.
9. Add a WordPress **Shortcode** block or the page builder's shortcode element.
10. Paste the shortcode.
11. Update the page and check it while logged out.

If a widget is changed from a Places source to a managed source, first synchronize the managed source. The Places-specific selection notice, Google Maps Places attribution, returned provider credit, and individual Places-review links then disappear automatically.

# Troubleshooting

## The widget shows no reviews

1. Open the selected Review Source.
2. Check **Last successful sync** or **Last error**.
3. Select **Synchronize now**.
4. Confirm the Review Library contains reviews for that source.
5. Check whether the widget's minimum rating or written-text filter excludes the available reviews.
6. Clear the page cache after changing the widget.

Selecting a newly imported managed source before its first successful synchronization produces an empty widget.

## Project quota is 0 QPM

Google has not granted Basic API Access to that project. Enabling APIs and configuring OAuth do not grant Basic API Access. Wait for approval or follow up on the existing case without filing duplicates.

## Token has expired or been revoked

Disconnect and reconnect Google. If the OAuth app is External and in Testing, review the seven-day refresh-token limitation and Google's production requirements.

## Google My Business API is disabled

Open Google My Business API in the same approved project, select **Enable**, wait several minutes, and synchronize again.

## Location discovery works but review sync fails

Discovery and review retrieval use different APIs. Confirm that Google My Business API is enabled in addition to Account Management and Business Information.

## Redirect URI mismatch

Copy the exact Authorized redirect URI from WordPress and paste it under the OAuth Web application's **Authorized redirect URIs**. Do not use JavaScript origins.

## Places requests are denied

Check:

- Billing is active.
- Places API (New) is enabled.
- The API key is restricted to Places API (New).
- A browser HTTP-referrer restriction is not blocking the server-side request.
- The Place ID is correct.

## Daily synchronization is late

WordPress Cron runs when the website receives traffic. Low-traffic sites may configure a real server cron to request wp-cron.php.

# Data, privacy, and policy responsibilities

- API credentials and OAuth tokens are encrypted locally using a key derived from the site's WordPress salts.
- Imported Google API content is stored in the WordPress database for no more than 30 days and refreshed during synchronization.
- Owner responses are not stored.
- The plugin contacts Google only after an administrator configures credentials and initiates or schedules synchronization.
- For managed sources, reviewer photos are optional.
- For Places sources, available reviewer attribution and required Google/provider attribution remain visible.
- Healthcare Privacy Mode is a technical safeguard, not legal advice or a compliance certification.

Review before production use:

- [Google Business Profile API prerequisites](https://developers.google.com/my-business/content/prereqs)
- [Google Business Profile basic setup](https://developers.google.com/my-business/content/basic-setup)
- [Google Business Profile API policies](https://developers.google.com/my-business/content/policies)
- [Google Places API policies and attribution](https://developers.google.com/maps/documentation/places/web-service/policies)
- [Google Terms of Service](https://policies.google.com/terms)
- [Google Privacy Policy](https://policies.google.com/privacy)
- [TomAwesome Places compliance guide](PLACES-COMPLIANCE.md)
- [TomAwesome Healthcare Privacy Mode guide](HEALTHCARE-PRIVACY-MODE.md)

# Updating or removing the plugin

- Updating the plugin does not normally remove credentials, sources, synchronized reviews, widgets, or styling.
- Deactivation stops the scheduled event but preserves settings and content.
- Reactivation restores the daily event.
- By default, uninstalling preserves data to prevent accidental loss.
- To remove all plugin data during uninstall, first enable **Delete plugin settings, widgets, sources, and synchronized reviews when the plugin is uninstalled** under **Review Widgets > Google Connection**.

TomAwesome Review Widgets is not affiliated with, sponsored by, or endorsed by Google LLC. Google and Google Business Profile are trademarks of Google LLC.
