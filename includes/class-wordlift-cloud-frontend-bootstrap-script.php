<?php
/**
 * Frontend WordLift Cloud bootstrap script integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects the WordLift Cloud bootstrap script on frontend pages.
 */
class Wordlift_Cloud_Frontend_Bootstrap_Script implements Wordlift_Cloud_Hookable_Service {

	/**
	 * Register frontend hook.
	 */
	public function register_hooks() {
		add_action( 'wp_head', array( $this, 'inject_script' ) );
	}

	/**
	 * Output WordLift Cloud bootstrap script if consent is granted.
	 */
	public function inject_script() {
		if ( is_admin() ) {
			return;
		}

		$has_consent = (bool) apply_filters( 'wordlift_cloud_has_bootstrap_consent', true );
		if ( ! $has_consent ) {
			return;
		}

		echo '<script async type="text/javascript" src="https://cloud.wordlift.io/app/bootstrap.js"></script>' . "\n";
	}
}

