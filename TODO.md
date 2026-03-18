# TODO

## Completed

- [x] Add `wl_entity_type` taxonomy registration to WordLift Cloud plugin.
- [x] Add custom Entity Type metabox handling (All / Most Used / A-Z).
- [x] Enforce exclusive Entity Type assignment on post save.
- [x] Add installer/sync flow for taxonomy bootstrap and updates.
- [x] Generate distilled Schema.org data from `schemaorg-current-https.jsonld`.
- [x] Add automated tests for schema distillation and slug/name rewrite behavior.
- [x] Output selected `wl_entity_type` as frontend meta tag (`wl:entity_type`) on singular pages.
- [x] Add one-command local Docker developer environment (`./scripts/dev up`) with WP bootstrap and plugin activation.
- [x] Exclude and remove `DataType` and all its subclasses from `wl_entity_type`.
- [x] Render `A-Z` Entity Types as a flat alphabetical checklist in Classic Editor.
- [x] Add `Selected` tab with live selected-term count/list to keep chosen Entity Types visible while browsing large taxonomies.
- [x] Add optional telemetry integration (settings page + conditional admin loader + consent filter hook).
- [x] Introduce service interfaces and a bootstrap container to centralize plugin wiring (`Hookable`, `Activatable`, `AnalyticsProvider`).
- [x] Restrict telemetry to backend authenticated usage and add event tracking for Entity Types/admin settings interactions.
- [x] Add a `Settings` action link on the Plugins screen and use generic telemetry wording in wp-admin settings copy.
- [x] Fix Classic Editor Entity Types metabox rendering and tab switching (`All` / `Most Used` / `A-Z`) while preserving multi-select behavior.
- [x] Expand PHPUnit source coverage scope to all `includes/` files and raise line coverage above 90%.
- [x] Add Docusaurus documentation for the WordLift Cloud Plugin in the Apps, Tools & Plugins section, including step-by-step Entity Types selection guides with screenshots.
- [x] Bump plugin minor version to `1.2.0` and prepare release tag.
- [x] Harden `wl_entity_type` against WPML translation side effects by shipping `wpml-config.xml` and pruning non-canonical translated term slugs during sync.
- [x] Move taxonomy sync execution off global `init`; run sync only on activation and admin-side plugin version changes.
- [x] Add explicit frontend bootstrap opt-in setting (default off) and align external-service disclosures accordingly for WordPress.org compliance.
- [x] Harden settings UX/compliance with capability guard, unslashed request sanitization, and privacy policy helper content.

## Next

- [ ] Add WordPress integration tests (WP core test suite) for taxonomy registration and term sync in a real WP runtime.
- [ ] Add WordPress integration tests (WP core test suite) for service-container bootstrapping/hooks and taxonomy registration in a real WP runtime.
- [ ] Add admin capability fine-tuning for editing/deleting `wl_entity_type` terms if required by product policy.
- [x] Add explicit external service disclosure in `readme.txt` for WordPress.org compliance (service dependency, data flow, privacy policy URL, terms URL).
- [x] Add an explicit opt-in/consent flow before loading `https://cloud.wordlift.io/app/bootstrap.js` on the frontend.
- [ ] Add plugin settings/admin notice documenting external requests and behavior when the remote service is unavailable.
- [x] Run a pre-release compliance checklist against https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/ and record results.
