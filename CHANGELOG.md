# Changelog

All notable changes to TomAwesome Review Widgets are documented here.

## 0.2.4 — 2026-08-11

- Updated Places widgets for Google Maps' August 2026 attribution requirements.
- Added exact Google Maps attribution, returned third-party data-provider credit, and a direct Google Maps link for every Places review.
- Made all available Places author attribution visible even when the optional reviewer-photo setting is off.
- Added a public notice describing Google's relevance-selected sample and the widget's rating, written-text, count, and order filters.
- Disabled Healthcare Privacy Mode for Places sources because suppressing author attribution conflicts with current Places requirements.
- Added a post-upgrade synchronization guard so older cached Places content cannot render without the newly requested attribution fields and individual review links.
- Expanded onboarding, privacy-policy suggestions, and documentation with the site-level Terms of Use and Privacy Policy requirements for Places content.

## 0.2.3 — 2026-08-11

- Added optional per-widget color pickers for the container, review cards, text, links, buttons, stars, and borders.
- Added visual controls for widget margin, padding, border width, corner radius, card padding, and card corner radius.
- Kept existing widgets visually unchanged until a custom style value is entered.
- Clarified how advanced CSS classes connect to theme or WordPress Additional CSS rules.

## 0.2.2 — 2026-08-11

- Replaced visitor-facing internal source titles with the synchronized Google business name.
- Added an optional per-widget Public heading override.
- Added a generic Google Reviews fallback so internal source labels are never exposed publicly.

## 0.2.1 — 2026-08-10

- Replaced raw Business Profile quota errors with actionable administrator guidance.
- Added direct links to the Google Cloud quota screen and Basic API Access application.
- Distinguished an unapproved project's zero quota from a temporary per-minute rate limit.
- Preserved Google's original response in an expandable technical-details section.
- Added tests for quota-error detection and ordinary API-error handling.

## 0.2.0 — 2026-08-10

- Added a Getting Started screen with connection-path guidance and live setup progress.
- Added an automatic first-run redirect after plugin activation.
- Added permanent Getting Started and Google Connection links to the Plugins screen.
- Expanded the managed Business Profile instructions with exact Cloud project, API access, API enablement, OAuth test-user, scope, redirect URI, and production-readiness steps.
- Clarified that Business Profile API approval and OAuth app verification are separate Google processes.
- Corrected existing WordPress Coding Standards issues in the administrator and uninstall routines.
- Updated GitHub Actions packaging to honor `.distignore` and exclude development-only files from release ZIPs.

## 0.1.0 — 2026-08-10

- Initial developer preview.
- Added Google Business Profile OAuth with location discovery.
- Added Google Places API (New) sources.
- Added unlimited shortcode widget configurations and four responsive layouts.
- Added daily and manual review synchronization.
- Added encrypted local credential storage.
- Added automatic 30-day Google API content expiration.
- Added Healthcare Privacy Mode and a manual privacy-copy approval workflow.
- Added WordPress privacy-policy suggestions and external-service disclosures.
- Added coding standards, unit tests, and GitHub Actions quality checks.
