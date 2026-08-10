# Architecture

## Data flow

1. An administrator configures either Google Business Profile OAuth or a Places API key.
2. The server retrieves review data during a manual or scheduled synchronization.
3. Normalized review fields are stored in the custom `{prefix}tarw_reviews` table with a 30-day expiration.
4. Unlimited private `tarw_widget` posts store independent display configurations.
5. The `[tomawesome_reviews id="..."]` shortcode queries only unexpired rows and renders escaped HTML.
6. The front end makes no review API request. A standard widget may request Google-hosted reviewer images when the administrator enables avatars.

## WordPress storage

- `tarw_source` private custom post type: business/location identifiers, public links, synchronization status, aggregate rating.
- `tarw_widget` private custom post type: layout and filtering configuration.
- `{prefix}tarw_reviews`: minimal normalized review data and separate privacy-approval fields.
- `tarw_settings`: encrypted credentials, encrypted OAuth token JSON, and uninstall preference.

The plugin does not store raw API response payloads, owner responses, visitor analytics, cookies, or front-end identifiers.

## Security model

- Administration requires `manage_options`.
- State-changing requests require WordPress nonces.
- OAuth state is random, user-specific, hashed in a ten-minute transient, and consumed once.
- Secrets use AES-256-GCM with a key derived from `wp_salt( 'auth' )`.
- API URLs use fixed HTTPS Google hosts and WordPress safe HTTP functions.
- Inputs are validated for their expected type; output is escaped at render time.
- API keys and OAuth tokens never appear in shortcode HTML or front-end JavaScript.
- Google content expires after 30 days and is deleted when a source is permanently removed.

## Extension points

- `tarw_widget_html` filters final widget HTML and receives the widget ID, source ID, settings, and review rows.

Additional hooks should be added conservatively so extensions cannot bypass privacy-mode enforcement accidentally.
