<?php
/**
 * Plugin Name: WordLift Cloud
 * Plugin URI: https://wordlift.io
 * Description: Adds WordLift Cloud integration, manages Entity Types taxonomy, and supports optional admin-only telemetry.
 * Version: 1.2.1
 * Author: WordLift
 * Author URI: https://wordlift.io
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WORDLIFT_CLOUD_VERSION' ) ) {
	define( 'WORDLIFT_CLOUD_VERSION', '1.2.1' );
}

require_once __DIR__ . '/includes/class-wordlift-cloud-schemaorg-distiller.php';
require_once __DIR__ . '/includes/class-wordlift-cloud-entity-type-taxonomy-walker.php';
require_once __DIR__ . '/includes/class-wordlift-cloud-schemaorg-taxonomy-metabox.php';
require_once __DIR__ . '/includes/class-wordlift-cloud-entity-type-taxonomy-installer.php';
require_once __DIR__ . '/includes/interface-wordlift-cloud-hookable-service.php';
require_once __DIR__ . '/includes/interface-wordlift-cloud-activatable-service.php';
require_once __DIR__ . '/includes/interface-wordlift-cloud-analytics-provider.php';
require_once __DIR__ . '/includes/class-wordlift-cloud-entity-type-taxonomy.php';
require_once __DIR__ . '/includes/class-wordlift-cloud-posthog-integration.php';
require_once __DIR__ . '/includes/class-wordlift-cloud-frontend-bootstrap-script.php';
require_once __DIR__ . '/includes/class-wordlift-cloud-bootstrap.php';

/**
 * Resolve plugin bootstrap container.
 *
 * @return Wordlift_Cloud_Bootstrap
 */
function wordlift_cloud_get_bootstrap() {
	static $bootstrap = null;

	if ( null === $bootstrap ) {
		$bootstrap = Wordlift_Cloud_Bootstrap::build_default();
	}

	return $bootstrap;
}

/**
 * Bootstrap callback.
 */
function wordlift_cloud_bootstrap() {
	wordlift_cloud_get_bootstrap()->register_hooks();
}

/**
 * Activation callback.
 */
function wordlift_cloud_activate() {
	wordlift_cloud_get_bootstrap()->activate();
}

register_activation_hook( __FILE__, 'wordlift_cloud_activate' );
add_action( 'plugins_loaded', 'wordlift_cloud_bootstrap' );
