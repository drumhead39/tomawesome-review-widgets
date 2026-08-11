# TomAwesome Review Widgets

TomAwesome Review Widgets is a self-hosted WordPress plugin for creating unlimited review shortcodes from multiple Google Business Profile and Places sources.

> Status: 0.2.4 developer preview. The code is structurally complete, but a real approved Google Business Profile project and WordPress staging site are still required for integration and Plugin Check testing before a 1.0.0 release.

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

1. Install and activate the plugin on a staging WordPress site.
2. WordPress opens **Review Widgets > Getting Started** after activation. Choose the managed-business or public Places connection path.
3. Follow [`docs/INSTALLATION.md`](docs/INSTALLATION.md) when you need the complete Google Cloud walkthrough.
4. Add or import a source and synchronize it.
5. Publish a widget and embed `[tomawesome_reviews id="123"]`.

Read [`docs/HEALTHCARE-PRIVACY-MODE.md`](docs/HEALTHCARE-PRIVACY-MODE.md) before enabling privacy mode. It is a technical safeguard, not a HIPAA certification or legal advice.

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
