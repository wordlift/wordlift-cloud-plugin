=== WordLift Cloud ===
Contributors: wordlift
Tags: wordlift, cloud, seo
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds WordLift Cloud integration, manages Entity Types taxonomy, and supports optional admin-only telemetry.

== Description ==

WordLift Cloud connects your WordPress site to WordLift Cloud and provides the Entity Type taxonomy experience used in post edit screens.

Features:

* Injects the WordLift Cloud bootstrap script in frontend pages.
* Registers and manages `wl_entity_type`.
* Adds a custom Entity Type metabox with All / Most Used / A-Z tabs.
* Outputs selected `wl_entity_type` terms as `<meta name="wl:entity_type" ...>` on singular frontend pages (one meta per selected term).
* Bootstraps and updates Entity Type terms from distilled Schema.org data.
* Optional telemetry integration for authenticated WordPress admin usage via `Settings > WordLift Cloud`.

== External services ==

This plugin connects to external services:

1. WordLift Cloud bootstrap
* Service URL: `https://cloud.wordlift.io/app/bootstrap.js`
* Purpose: load WordLift Cloud frontend functionality.
* Data sent: browser request metadata (IP/user-agent/referrer) and any data handled by that script runtime.
* Service provider: WordLift
* Terms: https://wordlift.io/terms-of-service/
* Privacy policy: https://wordlift.io/privacy-policy/

2. Telemetry service (optional, only when enabled by admin settings)
* Asset URL pattern: `https://*.i.posthog.com` and `https://*-assets.i.posthog.com/static/array.js`
* Purpose: authenticated backend analytics for WordPress admin feature usage.
* Data sent: admin page/event telemetry as configured by plugin event hooks.
* Service provider: telemetry endpoint configured by site owner.
* Terms: provided by your telemetry provider.
* Privacy policy: provided by your telemetry provider.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Changelog ==

= 1.1.1 =
* Improved Entity Types branding/alignment in Classic and Block editor panels.
* Added Plugins screen `Settings` action link.
* Refined admin telemetry wording and settings visibility.

= 1.1.0 =
* Added `wl_entity_type` taxonomy registration and synchronization.
* Added Entity Type metabox handling for post edit screens.
* Added frontend meta tag output for selected `wl_entity_type`.
* Added distilled Schema.org bootstrap dataset and generator script.
* Added tests for the schema distiller.
* Added optional telemetry integration settings and conditional admin loader.

= 1.0.0 =
* Initial release.
