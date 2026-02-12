<?php
/**
 * Plugin Name: WordLift Cloud
 * Plugin URI: https://wordlift.io
 * Description: Injects the WordLift Cloud bootstrap script into the head of every page.
 * Version: 1.0.0
 * Author: WordLift
 * Author URI: https://wordlift.io
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects the WordLift Cloud bootstrap script into the head of every page.
 */
function wordlift_cloud_inject_script() {
	echo '<script async type="text/javascript" src="https://cloud.wordlift.io/app/bootstrap.js"></script>' . "
";
}
add_action( 'wp_head', 'wordlift_cloud_inject_script' );
