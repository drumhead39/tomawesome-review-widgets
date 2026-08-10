# Contributing

Thank you for helping improve TomAwesome Review Widgets.

## Before opening a pull request

1. Create a focused branch from `main`.
2. Keep the plugin compatible with PHP 7.4 and WordPress 6.2 or newer.
3. Follow WordPress Coding Standards and escape output at the latest practical point.
4. Require `manage_options` and a nonce for administrator mutations.
5. Never expose Google credentials or OAuth tokens to front-end code.
6. Preserve the 30-day Google API content expiration.
7. Do not weaken Healthcare Privacy Mode defaults or imply legal certification.
8. Add or update tests and documentation for behavior changes.
9. Run `composer quality` and JavaScript syntax checks.

Pull requests should explain the user impact, security/privacy impact, tests performed, and any changes to Google API behavior or external-service disclosures.

Report vulnerabilities privately as described in `SECURITY.md`.
