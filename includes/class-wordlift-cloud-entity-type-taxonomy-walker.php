<?php
/**
 * Walker that renders wl_entity_type choices as radio controls.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Walker_Category_Checklist' ) ) {
	require_once ABSPATH . 'wp-admin/includes/template.php';
}

/**
 * Force exclusive selection for wl_entity_type terms.
 */
class Wordlift_Cloud_Entity_Type_Taxonomy_Walker extends Walker_Category_Checklist {

	/**
	 * Replace checkbox controls with radio controls in taxonomy checklist output.
	 *
	 * @param array<int,mixed> $elements Terms to render.
	 * @param int              $max_depth Hierarchy max depth.
	 * @param array<int,mixed> $args Additional args.
	 *
	 * @return string
	 */
	public function walk( $elements, $max_depth, ...$args ) {
		$output = parent::walk( $elements, 0, ...$args );

		return str_replace(
			array( 'type="checkbox"', "type='checkbox'" ),
			array( 'type="radio"', "type='radio'" ),
			$output
		);
	}
}

