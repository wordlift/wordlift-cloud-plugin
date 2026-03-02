<?php
/**
 * Contract for services that register WordPress hooks.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Describes services able to wire hooks.
 */
interface Wordlift_Cloud_Hookable_Service {

	/**
	 * Register all WordPress hooks required by the service.
	 *
	 * @return void
	 */
	public function register_hooks();
}

