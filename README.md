# WordLift Cloud Plugin for WordPress

A WordPress plugin that:
- injects the WordLift Cloud bootstrap script into the frontend, and
- manages an `Entity Type` taxonomy (`wl_entity_type`) on post edit screens.
- provides optional backend telemetry for authenticated admin usage via plugin settings.

## Installation

1. Clone or download this repository.
2. Upload the `wordlift-cloud-plugin` folder to your WordPress `wp-content/plugins/` directory.
3. Activate the plugin in the WordPress admin dashboard.

## Local Development (One Command)

Use Docker:

```bash
./scripts/dev up
```

This starts MySQL + WordPress, installs WordPress if needed, and activates this plugin.
It also installs and activates the Classic Editor plugin, and sets Classic Editor as default.

Defaults:
- Site URL: `http://localhost:8090`
- Admin user: `admin`
- Admin password: `admin`

Useful commands:

```bash
./scripts/dev down
./scripts/dev reset
./scripts/dev logs
./scripts/dev wp plugin list
```

Classic editor defaults configured by setup:
- `classic-editor-replace = classic`
- `classic-editor-allow-users = 1`

## Features

- Injects `<script async type="text/javascript" src="https://cloud.wordlift.io/app/bootstrap.js"></script>` into `wp_head`.
  - consent gate filter: `wordlift_cloud_has_bootstrap_consent` (defaults to `true`)
- Registers the `wl_entity_type` taxonomy for public post types (except attachments).
- Uses a custom `Entity Type` metabox (classic and block editor legacy metabox area) with:
  - `Selected` (always-visible summary of currently checked terms with live count)
  - `All`
  - `Most Used`
  - `A-Z` (flat alphabetical list)
- Supports selecting multiple entity types (checkbox behavior).
- Adds a Classic Editor metabox tab fallback script to keep `All` / `Most Used` / `A-Z` tab switching reliable.
- Excludes `DataType` and all `DataType` descendants from selectable Entity Types.
- Optional telemetry integration:
  - configurable in `Settings > WordLift Cloud`
  - admin UI field: enable/disable only
  - loaded in WordPress admin only (no public frontend telemetry snippet)
  - tracks backend authenticated feature usage (metabox interactions, settings usage, taxonomy management)
  - consent gate filter: `wordlift_cloud_has_analytics_consent` (defaults to `true`)
- Outputs selected entity type(s) on singular frontend pages as one meta per term:
  - `<meta name="wl:entity_type" content="{term-slug}" />`
- Bootstraps and updates taxonomy terms from a distilled Schema.org dataset:
  - source: `https://schema.org/version/latest/schemaorg-current-https.jsonld`
  - distilled data file: `resources/schemaorg/schema-classes.distilled.json`
- WPML compatibility:
  - ships `wpml-config.xml` to keep `wl_entity_type` non-translatable
  - removes non-canonical slugs (for example language-suffixed duplicates) during sync
  - sync runs on plugin activation and plugin update (admin path), not on every page load

## Schema Data Build

Regenerate the distilled dataset:

```bash
php scripts/build-schemaorg-distilled.php
```

The distiller extracts `rdfs:Class` nodes, rewrites names into canonical slugs (for example `AMRadioChannel` -> `am-radio-channel`, `CreativeWork` -> `creative-work`, `WPHeader` -> `wp-header`), and stores parent/child relationships for taxonomy sync.

## Testing

Install dependencies and run tests:

```bash
composer install
composer test
composer test:coverage
```

Coverage is configured for all plugin source files under `includes/`.

## Architecture

- Plugin runtime is orchestrated by a bootstrap container (`Wordlift_Cloud_Bootstrap`) that wires services through interfaces:
  - `Wordlift_Cloud_Hookable_Service`
  - `Wordlift_Cloud_Activatable_Service`
  - `Wordlift_Cloud_Analytics_Provider`
- Core services are instantiated via `Wordlift_Cloud_Bootstrap::build_default()`.

## Telemetry Integration Example

To gate telemetry loading on your consent mechanism:

```php
add_filter(
	'wordlift_cloud_has_analytics_consent',
	function ( $has_consent ) {
		// Replace with your CMP/cookie check.
		return isset( $_COOKIE['analytics_consent'] ) && 'yes' === $_COOKIE['analytics_consent'];
	}
);
```

Telemetry credential configuration (hidden from admin UI):

```php
define( 'WORDLIFT_CLOUD_POSTHOG_PROJECT_TOKEN', 'phc_xxx' );
define( 'WORDLIFT_CLOUD_POSTHOG_API_HOST', 'https://eu.i.posthog.com' );
```

Optional filters:
- `wordlift_cloud_posthog_project_token`
- `wordlift_cloud_posthog_api_host`
