<?php
/**
 * Contract for analytics integrations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marker interface for analytics providers.
 */
interface Wordlift_Cloud_Analytics_Provider extends Wordlift_Cloud_Hookable_Service {
}

