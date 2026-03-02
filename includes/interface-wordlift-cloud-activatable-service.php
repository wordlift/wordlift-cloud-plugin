<?php
/**
 * Contract for services that require activation lifecycle handling.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Describes services with activation logic.
 */
interface Wordlift_Cloud_Activatable_Service {

	/**
	 * Execute activation logic.
	 *
	 * @return void
	 */
	public function activate();
}

