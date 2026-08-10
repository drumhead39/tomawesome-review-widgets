# WordPress.org release checklist

Do not submit a developer-preview version until it has completed real Google API and staging-site testing. The first public-directory candidate should be version 1.0.0.

## Before requesting a slug

1. Confirm the final display name and permanent slug. The current proposal is **TomAwesome Review Widgets** / `tomawesome-review-widgets`.
2. Search WordPress.org for confusingly similar names and run Plugin Check's naming tool when available.
3. Test both source types on a fresh WordPress staging site.
4. Test PHP 7.4 and current PHP, current WordPress, a default theme, and at least one common page builder.
5. Run WordPress.org's Plugin Check “Plugin repo” category with zero unresolved errors.
6. Run `composer quality` and confirm GitHub Actions passes.
7. Verify that `readme.txt` renders correctly with the official WordPress readme validator.
8. Review all Google and HHS policy links for changes.
9. Build the distribution archive from a committed Git tag, not from an uncommitted worktree.
10. Install the final ZIP on a clean site and repeat activation, connection, synchronization, shortcode, deactivation, reactivation, and optional uninstall tests.

## Submission

1. Update versions in the plugin header, `TARW_VERSION`, `readme.txt`, and changelogs.
2. Set `Stable tag: 1.0.0` in `readme.txt`.
3. Tag `v1.0.0` in GitHub and attach the tested distribution ZIP.
4. Upload the exact tested ZIP through the WordPress.org **Add Your Plugin** page while signed in as `TomAwesome`.
5. Respond to reviewer questions in the existing email thread and keep changes focused on requested issues.

## After approval

WordPress.org supplies a permanent SVN repository. Check it out into a directory separate from the Git development checkout.

- Copy finished release files—not the containing plugin folder—into SVN `trunk/`.
- Exclude GitHub workflows, tests, Composer development files, and repository metadata according to `.distignore`.
- Commit `trunk/` with a meaningful message.
- Copy the same tested release from `trunk/` to `tags/1.0.0/`.
- Ensure `Stable tag` exactly matches the SVN tag.
- Commit release artwork only under SVN `assets/`, not plugin `trunk/`.
- Confirm the WordPress.org page, ZIP, version, and public installation after propagation.

GitHub and WordPress SVN must be updated separately for every public release.
