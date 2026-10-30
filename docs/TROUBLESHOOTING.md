# TomAwesome Review Widgets Troubleshooting Guide

Use this guide when a shortcode is present but no reviews appear, a source will not synchronize, or reviews remain missing after Google reports success.

## Fast diagnosis

| Symptom or error | Most likely cause | First action |
| --- | --- | --- |
| A previously working widget is completely blank | Synchronization failed long enough for the 30-day review cache to expire | Open the selected Review Source and inspect **Last successful sync** and **Last error** |
| **Last error: Bad Request** | Google rejected the saved OAuth authorization, commonly an External app left in Testing beyond seven days | Complete Branding, publish the OAuth app, reconnect Google, and synchronize again |
| **Token has expired or been revoked** | The refresh token is invalid | Publish if still in Testing, then disconnect and reconnect |
| **Google My Business API has not been used or is disabled** | Review retrieval uses an API that was not enabled | Enable Google My Business API in the same project and retry after propagation |
| **Requests per minute is 0** | Google has not granted Business Profile Basic API Access to the project | Wait for approval or follow up on the existing application |
| Location discovery works but review synchronization fails | Discovery and review retrieval use different APIs | Confirm Google My Business API is enabled |
| Synchronization succeeds but normal pages remain blank | A page cache still contains the earlier empty server-rendered HTML | Purge WordPress, host, and CDN caches |
| Places synchronization is denied | Billing, Places API (New), API-key restrictions, or Place ID is incorrect | Check the Places configuration |

## Why a shortcode can become completely blank

The plugin stores Google review content locally for no more than 30 days. Each successful source synchronization renews that expiration. If synchronization repeatedly fails, the reviews eventually become ineligible and are removed. The public shortcode then returns no review markup rather than serving expired Google content.

This behavior means:

- The page and shortcode may still be correctly configured.
- Frontend TomAwesome CSS and JavaScript will not load when there are no eligible reviews.
- Rebuilding the page or creating a replacement widget does not repair the source.
- The source error must be corrected and synchronization must succeed.

Start here:

1. Open **Review Widgets > Review Sources**.
2. Edit the source selected by the blank widget.
3. Record **Last successful sync** and **Last error**.
4. Confirm **Include this source in daily synchronization** is enabled.
5. Select **Synchronize now**.
6. Continue with the matching error section below.

## Managed source: Last error is Bad Request

When a managed Business Profile source previously worked, **Bad Request** commonly means Google rejected the saved OAuth refresh token. The token endpoint can return this generic wording without explaining the underlying authorization failure.

This pattern is especially likely when:

- The first connection worked.
- The Google Auth Platform app is **External**.
- The app was left in **Testing**.
- More than seven days passed.
- The widget disappeared only after the locally synchronized reviews aged out.

### Repair the authorization

Perform these steps in order:

1. Open the same Google Cloud project used by the saved OAuth Client ID.
2. Open **Google Auth Platform > Branding**.
3. Complete the required app name, user-support email, website homepage, Privacy Policy URL, authorized domain, and developer contact information.
4. Save Branding.
5. Open **Google Auth Platform > Audience**.
6. Review Google's current publishing and verification requirements.
7. Select **Publish app** or **Push to production** when ready.
8. Return to **Review Widgets > Google Connection** in WordPress.
9. Disconnect Google.
10. Reconnect with the Google account that owns or manages the Business Profile.
11. Open every managed Review Source and select **Synchronize now**.
12. Confirm a new **Last successful sync** time and no **Last error**.

Publish before reconnecting. Publishing does not repair a refresh token that Google has already invalidated; reconnecting issues a new authorization.

Production removes Testing mode's seven-day refresh-token limitation. It does not guarantee that a token can never be revoked. Google may invalidate authorization because of user action, account security, credential changes, inactivity, token limits, or policy enforcement.

If **Bad Request** continues after a fresh Production authorization:

1. Confirm the OAuth client belongs to the same project Google approved for Business Profile API access.
2. Confirm all required Business Profile APIs remain enabled.
3. Use **Discover managed locations** to confirm the connected account still manages the location.
4. Do not delete the existing source or widget yet.
5. Record the complete error, the last-attempt time, and whether location discovery succeeds before requesting support.

## Token has expired or been revoked

Follow the same publish-and-reconnect procedure above.

If the app already shows **Production**, disconnect and reconnect anyway. Production status does not make an already invalid token valid again.

## Google My Business API is disabled

Location discovery can succeed while review retrieval fails because the operations use different APIs.

1. Select the same approved Google Cloud project.
2. Open **APIs & Services > Library**.
3. Find **Google My Business API**.
4. Select **Enable**. If the button says **Manage**, it is already enabled.
5. Wait several minutes for propagation.
6. Synchronize the managed source again.

## Project quota is 0 requests per minute

Enabling APIs and completing OAuth do not grant Business Profile Basic API Access.

- Check the Account Management API quota in the same project as the OAuth client.
- If Requests per minute is 0, wait for Google approval or follow up on the existing access case.
- Do not submit duplicate applications unless Google instructs you to do so.
- If quota is above 0, wait at least one minute, confirm the project IDs match, and retry.

## Synchronization succeeds but the public page remains blank

TomAwesome widgets are rendered into the page HTML by WordPress. A page cache can preserve the empty HTML generated while the source had no eligible reviews.

After a successful synchronization:

1. Purge the WordPress page cache.
2. Purge any hosting cache.
3. Purge any CDN or reverse-proxy cache.
4. With **WP Super Cache**, open **Settings > WP Super Cache** and select **Delete Cache**.
5. Reload the normal page URL while logged out or in a private browser window.

A cache-busting query string may show that current uncached output works, but it does not clear the normal cached URL and is not a permanent repair.

## Filters can legitimately produce zero results

If the source synchronizes and the Review Library contains current reviews, inspect the widget:

- Minimum rating may exclude all available reviews.
- **Written reviews only** may exclude rating-only reviews.
- Healthcare Privacy Mode displays only reviews with approved privacy copy.
- The widget may be assigned to a different source than expected.
- The widget or source may no longer be published.

## Places source errors

For a Places source, confirm:

- A billing account is attached to the project.
- **Places API (New)** is enabled.
- The API key is restricted to Places API (New), not blocked by browser-only HTTP-referrer restrictions.
- The Place ID is correct.
- The source has been synchronized after any plugin update that changes Places attribution handling.

Google supplies at most five reviews selected by relevance for a Places source.

## Daily synchronization and WordPress Cron

The plugin schedules daily synchronization through WordPress Cron. WordPress Cron normally runs when the site receives traffic.

Check that:

- The source is enabled for daily synchronization.
- WordPress Cron is not disabled without a replacement server cron.
- A security or caching layer is not blocking wp-cron.php.
- The source's **Last successful sync** advances over time.

Low-traffic sites may configure a real server cron to request wp-cron.php. Manually synchronize a repaired source immediately rather than waiting for the next scheduled event.

## Information to collect before requesting support

Provide:

- Plugin version.
- WordPress and PHP versions.
- Source type: Places or managed Business Profile.
- Last successful sync time.
- Last attempt time.
- Complete Last error text.
- Whether manual synchronization succeeds.
- Whether managed-location discovery succeeds.
- Whether the Review Library contains current reviews.
- Whether an uncached or logged-in page renders the widget.

Never post OAuth client secrets, refresh tokens, access tokens, API keys, authorization codes, or private support URLs in a public forum.
