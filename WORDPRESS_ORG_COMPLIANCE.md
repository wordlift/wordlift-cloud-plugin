# WordPress.org Compliance Checklist

Last reviewed: 2026-03-02
Guideline source: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
Common issues source: https://developer.wordpress.org/plugins/wordpress-org/common-issues/

## Summary

- Status: largely compliant for a first submission pass.
- Remaining recommended work: add an admin notice/help panel for external-service failure handling and expand integration tests in a real WP runtime.

## Checklist

- [x] GPL-compatible licensing declared in plugin headers and readme.
- [x] No obfuscated or hidden code patterns.
- [x] No "phoning home" by default for optional telemetry.
  - Telemetry is disabled by default and only loads in authenticated wp-admin when enabled.
- [x] Frontend external service call (`bootstrap.js`) is explicit opt-in.
  - Controlled by `wordlift_cloud_frontend_enabled`, default `0`.
- [x] External services are disclosed in `readme.txt`.
  - Service URLs, purpose, trigger, data flow, terms, and privacy links are documented.
- [x] Settings screens are capability-protected.
  - `manage_options` check is enforced when rendering settings.
- [x] User input from request variables is sanitized/unslashed before use.
- [x] Privacy policy helper content is registered via `wp_add_privacy_policy_content`.
- [x] Frontend and telemetry consent filters are available:
  - `wordlift_cloud_has_bootstrap_consent`
  - `wordlift_cloud_has_analytics_consent`
- [x] Automated tests pass and source line coverage is above target.
  - PHPUnit: 54 tests passing.
  - Line coverage: 91.45%.

## Remaining recommendations before submission

- [ ] Add a small admin notice/help text that explains what happens when external services are unreachable and where to troubleshoot.
- [ ] Add WP core integration tests (beyond unit test stubs) for activation/update sync flows.
