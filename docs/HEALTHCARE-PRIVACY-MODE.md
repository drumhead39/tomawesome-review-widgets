# Healthcare Privacy Mode

Healthcare Privacy Mode is a privacy-oriented publishing workflow. It is not a “HIPAA compliance switch,” legal advice, certification, or a substitute for organizational policies and legal review.

## Why the plugin uses careful language

HIPAA compliance depends on the covered entity or business associate, the information involved, the purpose and legal basis for using it, policies, training, safeguards, contracts, and actual behavior. A WordPress plugin controls only a small part of that system.

The U.S. Department of Health and Human Services has announced enforcement settlements involving providers that disclosed protected health information while responding to online reviews. See [HHS: Manasa Health Center settlement](https://www.hhs.gov/hipaa/for-professionals/compliance-enforcement/agreements/manasa/index.html) and [HHS: New Vision Dental settlement](https://www.hhs.gov/hipaa/for-professionals/compliance-enforcement/agreements/new-vision/index.html).

## What the mode enforces

When Healthcare Privacy Mode is enabled for a widget:

- Only reviews with administrator-approved privacy copy are eligible.
- Imported reviewer names become “Google Reviewer.”
- Reviewer photos are neither rendered nor requested from Google by the visitor's browser.
- Reviewer profile links are removed.
- Exact review dates are hidden.
- Read-all and leave-a-review links are suppressed to reduce linkage back to unsanitized source content.
- Owner responses are not displayed; the plugin does not store them at all.
- The original synchronized review and privacy-reviewed display copy remain separate database fields.
- Imported content and its derived privacy copy expire after 30 days unless synchronization succeeds again.

These controls cannot determine whether a person is identifiable from context, whether authorization is valid, or whether the review may lawfully be used for marketing.

## Recommended internal workflow

1. Assign privacy-copy approval only to trained staff.
2. Review the entire original post, not only the sentence intended for display.
3. Remove names, initials, dates, ages, diagnoses, treatments, body parts, appointment details, family relationships, locations, employers, and unusual facts that could identify the reviewer.
4. Avoid language that confirms the reviewer was a patient or received services.
5. Record any authorization outside this plugin in the organization's approved recordkeeping system.
6. Require a second reviewer for sensitive or borderline content.
7. Re-review approved copy after each synchronization and whenever organizational policy changes.
8. Keep public responses generic and never confirm a treatment relationship. This plugin intentionally does not publish owner responses.

## Google attribution and API terms

Google's API and attribution requirements apply in addition to privacy law. They can change. As of Google's August 2026 Places policy update, Places reviews must display available author attribution and provide direct access to each individual review on Google Maps. Those requirements conflict with this mode's de-identification controls, so the plugin does not allow Healthcare Privacy Mode with a Google Places source.

Healthcare Privacy Mode remains available for managed Google Business Profile sources. The site owner must still determine whether its presentation is permitted under the applicable Google API agreement and whether publishing the privacy-reviewed copy is lawful.

Relevant Google documents:

- [Business Profile API policies](https://developers.google.com/my-business/content/policies)
- [Places API policies and attribution](https://developers.google.com/maps/documentation/places/web-service/policies)
- [Google Terms of Service](https://policies.google.com/terms)

## Suggested public wording

Describe the output as “Reviews from Google” or “Privacy-reviewed excerpts from public Google reviews.” Do not label reviewers as “verified patients” and do not say that use of the plugin makes the website HIPAA compliant.
