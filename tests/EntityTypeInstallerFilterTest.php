<?php
declare( strict_types=1 );

final class EntityTypeInstallerFilterTest extends WordPressTestCase {

	public function test_get_descendant_slugs_for_data_type_tree(): void {
		$classes = array(
			array(
				'slug'     => 'thing',
				'children' => array( 'creative-work' ),
			),
			array(
				'slug'     => 'creative-work',
				'children' => array(),
			),
			array(
				'slug'     => 'data-type',
				'children' => array( 'text', 'number' ),
			),
			array(
				'slug'     => 'text',
				'children' => array( 'url' ),
			),
			array(
				'slug'     => 'number',
				'children' => array(),
			),
			array(
				'slug'     => 'url',
				'children' => array(),
			),
		);

		self::assertSame(
			array( 'number', 'text', 'url' ),
			Wordlift_Cloud_Entity_Type_Taxonomy_Installer::get_descendant_slugs( $classes, 'data-type' )
		);
	}

	public function test_get_descendant_slugs_returns_empty_for_unknown_ancestor(): void {
		$classes = array(
			array(
				'slug'     => 'thing',
				'children' => array( 'creative-work' ),
			),
			array(
				'slug'     => 'creative-work',
				'children' => array(),
			),
		);

		self::assertSame(
			array(),
			Wordlift_Cloud_Entity_Type_Taxonomy_Installer::get_descendant_slugs( $classes, 'data-type' )
		);
	}

	public function test_get_excluded_slugs_includes_data_type_and_descendants(): void {
		$classes = array(
			array(
				'slug'     => 'thing',
				'children' => array( 'creative-work' ),
			),
			array(
				'slug'     => 'data-type',
				'children' => array( 'text', 'number' ),
			),
			array(
				'slug'     => 'text',
				'children' => array( 'url' ),
			),
			array(
				'slug'     => 'number',
				'children' => array(),
			),
			array(
				'slug'     => 'url',
				'children' => array(),
			),
		);

		self::assertSame(
			array( 'data-type', 'number', 'text', 'url' ),
			Wordlift_Cloud_Entity_Type_Taxonomy_Installer::get_excluded_slugs( $classes )
		);
	}
}
