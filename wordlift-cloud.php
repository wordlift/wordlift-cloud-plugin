<?php
/**
 * Plugin Name: WordLift Cloud
 * Plugin URI: https://wordlift.io
 * Description: Injects the WordLift Cloud bootstrap script and entity type meta tags into the head of every page.
 * Version: 1.0.0
 * Author: WordLift
 * Author URI: https://wordlift.io
 * License: GPL2
 *
 * This plugin has two responsibilities:
 *
 * 1. Injects the WordLift Cloud bootstrap script (cloud.wordlift.io) into every page.
 *    This enables WordLift's cloud-based features on the frontend.
 *
 * 2. Reads the wl_entity_type taxonomy terms assigned to the current post and outputs
 *    them as <meta property="entityType"> tags in the <head>. This allows the cloud
 *    script (and any other consumer) to know what entity types are associated with the
 *    current page.
 *
 * Dependencies:
 *   - The wl_entity_type taxonomy must be registered by another plugin (e.g. the full
 *     WordLift plugin in production, or the WL Entity Type Simulator in local dev).
 *   - Entity type terms (Thing, Person, Place, etc.) must exist in the taxonomy.
 *   - Blog posts must have entity type terms assigned via the editor sidebar checkboxes.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Injects the WordLift Cloud bootstrap script into the <head> of every page.
 *
 * This script is loaded asynchronously and enables WordLift's cloud features
 * such as entity highlighting, content recommendations, and knowledge graph
 * integration on the frontend.
 */
function wordlift_cloud_inject_script()
{
	echo '<script async type="text/javascript" src="https://cloud.wordlift.io/app/bootstrap.js"></script>' . "
";
}
add_action('wp_head', 'wordlift_cloud_inject_script');

/**
 * Reads the wl_entity_type taxonomy terms assigned to the current post and
 * outputs them as meta tags in the <head>.
 *
 * Only runs on singular pages (single posts/pages). Skips archive, home,
 * search, and other non-singular views.
 *
 * Output example for a post tagged with "Person" and "Organization":
 *   <meta property="entityType" content="Person">
 *   <meta property="entityType" content="Organization">
 */
function wordlift_entity_type_inject_script()
{
	// Only inject on single post/page views, not on archives or listing pages.
	if (!is_singular()) {
		return;
	}

	// Get the current post ID.
	$post_id = get_the_ID();
	if (!$post_id) {
		return;
	}

	// Bail if the taxonomy is not registered (Wordlift or simulator is inactive).
	if (!taxonomy_exists('wl_entity_type')) {
		return;
	}

	// Fetch the wl_entity_type terms assigned to this post.
	$terms = wp_get_object_terms($post_id, 'wl_entity_type');
	if (is_wp_error($terms) || empty($terms)) {
		return;
	}

	// Output a meta tag for each assigned entity type.
	foreach ($terms as $term) {
		echo '<meta property="schema:type" content="' . esc_attr($term->name) . '">' . "\n";
	}
}
add_action('wp_head', 'wordlift_entity_type_inject_script');