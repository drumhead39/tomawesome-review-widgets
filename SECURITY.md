# Security policy

## Supported versions

The latest tagged release receives security fixes. Developer-preview builds must be tested on a staging site before production use.

## Reporting a vulnerability

Do not publish exploitable details in a public issue. Use GitHub's private vulnerability reporting feature for the `drumhead39/tomawesome-review-widgets` repository after it becomes available.

Include the affected version, WordPress and PHP versions, reproduction steps, impact, and any proposed mitigation. Please allow reasonable time to investigate and prepare a coordinated fix.

## Credential handling

The plugin encrypts Google client credentials, API keys, and OAuth tokens with the WordPress authentication salt. Administrators should still protect WordPress administrator access, database backups, `wp-config.php`, server logs, and hosting credentials. Rotate Google credentials after any suspected compromise.
