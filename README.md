# TomAwesome Review Widgets

TomAwesome Review Widgets is a self-hosted WordPress plugin for creating unlimited review shortcodes from multiple Google Business Profile and Places sources.

> Status: 1.0.0 stable release. Places and approved managed Business Profile sources have completed real WordPress 7.1 integration testing.

## Google setup is required

The plugin does not display reviews immediately after activation and does not provide shared Google credentials.

- **Places API is the easier, limited path.** It requires Google Cloud billing, Places API (New), an API key, and a Place ID. Google returns at most five reviews selected by relevance.
- **Managed Business Profile is the advanced, complete path.** It requires a verified managed profile, Google's Basic API Access approval, seven enabled APIs, Google Auth Platform, and an OAuth web client. Approval may take days or longer and is controlled entirely by Google.

Read the complete beginner walkthrough in [docs/INSTALLATION.md](docs/INSTALLATION.md) before installing for managed-business use.

## Highlights

- Multiple managed Business Profile locations through one local OAuth connection
- Multiple public Places sources
- Unlimited grid, list, carousel, and featured-review widgets
- Server-side synchronization with a 30-day Google-content expiration
- Locally encrypted API credentials and OAuth tokens
- Healthcare Privacy Mode with manual privacy-copy approval
- Guided first-run onboarding with live setup progress
- Beginner-focused Google Cloud, API approval, and OAuth instructions
- Actionable guidance when Google reports zero or exhausted API quota
- Google business-name headings with optional per-widget custom wording
- Per-widget color, spacing, border, and corner-radius controls
- Current Google Maps attribution, author attribution, individual review links, provider credit, and filter disclosure for Places widgets
- Places-specific safeguards that prevent author attribution from being hidden
- No TomAwesome proxy, telemetry, advertising, or paid service

## Quick start

1. Read the setup-path comparison in [docs/INSTALLATION.md](docs/INSTALLATION.md).
2. Install and activate the plugin.
3. WordPress opens **Review Widgets > Getting Started** after activation.
4. Create and save the Google credentials required by the selected path.
5. Add or import a source and select **Synchronize now**.
6. Publish a widget only after that source synchronizes successfully.

Read [`docs/HEALTHCARE-PRIVACY-MODE.md`](docs/HEALTHCARE-PRIVACY-MODE.md) before enabling privacy mode. It is a technical safeguard, not a HIPAA certification or legal advice.

Read [`docs/PLACES-COMPLIANCE.md`](docs/PLACES-COMPLIANCE.md) before publishing a Places source. It explains every Places-only public element, where it is enforced, and what disappears automatically after a widget switches to a managed Business Profile source.

## Development

Requirements:

- PHP 7.4 or newer
- Composer 2

Run checks:

```bash
composer install
composer quality
node --check assets/js/admin.js
node --check assets/js/frontend.js
```

GitHub Actions runs PHP syntax checks, WordPress Coding Standards, PHP compatibility checks, unit tests, JavaScript syntax checks, and produces an installable ZIP artifact after checks pass.

## Release model

GitHub is the development, issue, pull-request, and release-history repository. After WordPress.org approves the permanent plugin slug, finished releases are copied separately into the WordPress.org SVN repository. SVN is a release channel, not the day-to-day development repository.

## License and trademarks

Code is licensed under GPL-2.0-or-later.

TomAwesome Review Widgets is not affiliated with, sponsored by, or endorsed by Google LLC. Google and Google Business Profile are trademarks of Google LLC.
