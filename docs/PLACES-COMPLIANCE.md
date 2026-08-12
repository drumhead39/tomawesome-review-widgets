# Google Places compliance guide

This guide explains why widgets using a **Google Places API (New)** source contain public attribution and review-selection information that does not appear on widgets using a managed **Google Business Profile** source.

It is written for site owners, administrators, support staff, and developers. The implementation was reviewed against Google's [Places API policies and attribution requirements](https://developers.google.com/maps/documentation/places/web-service/policies), last updated by Google on August 7, 2026. Google can change its policies; the current Google documentation remains authoritative.

## The short explanation

Places API reviews are Google Maps Platform content. When a site republishes them without displaying an accompanying Google Map, Google requires the site to identify Google Maps as the source, credit review authors and any returned data providers, link visitors to each individual source review, and clearly explain how the displayed reviews were selected, filtered, and ordered.

TomAwesome Review Widgets enforces those elements automatically for every widget whose selected Review Source is **Google Places**. They are not advertising, and they are not optional display settings.

When a widget is changed to a synchronized **managed Google Business Profile** source, the Places-only elements disappear automatically on the next page load. No cleanup or separate compliance toggle is required.

## What a Places widget displays and why

| Public element | Why it exists | Plugin behavior | After switching the widget to a managed source |
| --- | --- | --- | --- |
| `Google Maps` attribution | Required when Google Maps Platform content is displayed without a Google Map | Displayed in the widget footer using Google's required text treatment | The Places attribution disappears; the footer uses the plugin's ordinary `Reviews from Google` source label |
| Reviewer name, profile link, and available photo | Places policies require author credit for reviews | The plugin displays all author information returned by Google and does not let the optional photo setting suppress an available Places author photo | Photos become optional; ordinary managed widgets still show reviewer names unless Healthcare Privacy Mode is used |
| `View this review on Google Maps` | Visitors must have direct access to each individual source review using Google's returned review URL | Displayed on every Places review card after a policy-ready synchronization | The per-review Places link disappears; optional Read all reviews and Leave a review links can remain |
| Returned data-provider credit | Third-party attribution supplied with a Places result must be displayed | Displayed beside the Google Maps attribution when Google returns it | Disappears because it belongs to the Places response |
| Review-selection and filter notice | Places policies require a clear notice describing how reviews are ordered and filtered | Displayed beside the Google Maps attribution and generated from the widget's current count, minimum rating, written-text requirement, and order settings | Disappears automatically |
| Site Terms of Use and Privacy Policy | Places applications must provide public terms and privacy disclosures that incorporate Google's applicable terms and privacy policy | The plugin adds suggested Privacy Policy wording, but the site owner must publish and maintain the site pages | Site owners should keep generally applicable Google-service disclosures that still describe their actual configuration |

The common notice for a widget that displays the available written five-star reviews newest first is:

> Google selects up to five reviews by relevance; this widget shows written 5-star reviews, newest first.

If the administrator changes a filter, the notice changes with it. For example, a three-review widget with a two-star minimum and oldest-first ordering says:

> Google selects up to five reviews by relevance; this widget shows up to 3 reviews rated 2 stars or higher, oldest first.

Do not hide, obscure, recolor into invisibility, remove, or replace these elements with Custom CSS, a theme override, the `tarw_widget_html` filter, or another plugin.

## Required and recommended items

The plugin treats the following Places policy items as required safeguards:

- Google Maps attribution within the review widget container.
- Available review-author attribution.
- Direct access to each individual review on Google Maps.
- Any third-party data-provider attribution returned by Google.
- A clear description of review selection, filtering, and ordering.
- A successful policy-ready synchronization before cached Places reviews render.
- Public site Terms of Use and Privacy Policy disclosures maintained by the site owner.

Google currently labels relative publish dates, content-reporting affordances, and an explanation of how Google verifies reviews as recommendations rather than universal display requirements. The plugin can display review dates when enabled. Each required individual-review link opens the source review on Google Maps, where Google provides its reporting workflow.

## Switching a widget to a managed Business Profile source

Use the managed source when the Google account owns or manages the business and Google has approved the Cloud project for Business Profile API access.

1. Open **Review Widgets > Google Connection** and connect Google Business Profile.
2. Choose **Discover managed locations**.
3. Import the desired location as a new Review Source.
4. Open the new managed source and choose **Synchronize now**.
5. Edit each affected Review Widget.
6. Change **Review source** from the Places source to the new managed Business Profile source.
7. Update the widget and clear any page cache used by the site.

Do not convert the existing Places source by changing its type. A Places source stores a Place ID, while an imported managed source stores Business Profile account and location resource names. Keep the two sources separate until every widget has been checked.

After the widget uses the managed source:

- The selection/filter notice disappears.
- The Places-specific Google Maps attribution and returned provider credit disappear.
- The individual `View this review on Google Maps` links disappear.
- Reviewer photos return to the widget's optional display setting.
- Healthcare Privacy Mode becomes available.
- Synchronization can retrieve the complete paginated review list rather than Google's Places sample of up to five relevance-selected reviews.

The change is determined from the selected source type every time the shortcode renders. It does not depend on a migration flag or a separate display option.

## Where administrators can find this explanation

The plugin repeats the essential guidance in several places so an administrator does not need to find this file first:

- **Review Widgets > Getting Started > Public Places** contains the policy explanation and managed-source transition instructions.
- A Google Places **Review Source** displays a link labeled **Why these elements appear**.
- The **Widget Configuration** screen explains that Places attribution overrides optional author-photo and privacy settings.
- The WordPress plugin FAQ explains why the notice appears and how it is removed.
- `docs/INSTALLATION.md` includes the setup and transition steps.

## Developer implementation map

The compliance behavior is intentionally tied to the source type instead of user-editable settings:

- `includes/class-places-client.php` requests reviews, Google Maps URIs, author attribution, aggregate data, and returned provider attribution.
- `includes/class-sync-service.php` normalizes the returned fields and records the Places policy-sync marker only when the response contains the fields needed for compliant rendering.
- `includes/class-shortcode.php` blocks pre-policy cached Places output, adds each review's source link, and renders the compliance footer.
- `includes/class-widget-display.php` disables Healthcare Privacy Mode for Places, preserves available author photos, and generates the dynamic selection/filter notice.
- `assets/css/frontend.css` keeps the Google Maps attribution and notice visible and legible near the Places content.
- `tests/WidgetDisplayTest.php` verifies the Places privacy boundary and representative notice wording.

If Google introduces a new required response field or public element, maintainers should:

1. Re-read the current Places policy and API response reference.
2. Add the field to the Places field mask and normalized storage as needed.
3. Update front-end rendering without making the new required element optional.
4. Change the policy-sync marker and require a fresh Places synchronization if old cached records lack the new data.
5. Add tests for the new invariant and for the managed-source boundary.
6. Update this guide, Getting Started, `docs/INSTALLATION.md`, `readme.txt`, `README.md`, and the changelog in the same release.

## Site-owner responsibilities

The plugin supplies technical safeguards but cannot accept Google's terms, publish site policies, determine whether a particular use is lawful, or monitor future policy changes for the site owner. Before production use, the site owner should review:

- [Google Places API policies and attribution requirements](https://developers.google.com/maps/documentation/places/web-service/policies)
- [Google Maps Platform Terms of Service](https://cloud.google.com/maps-platform/terms)
- [Google Terms of Service](https://policies.google.com/terms)
- [Google Privacy Policy](https://policies.google.com/privacy)

This documentation is operational guidance, not legal advice.
