<?php
declare( strict_types=1 );

final class SchemaorgTaxonomyMetaboxTest extends WordPressTestCase {
	public function test_render_outputs_selected_all_and_az_tabs(): void {
		$post = (object) array( 'ID' => 42 );
		$box  = array(
			'args' => array(
				'taxonomy' => 'wl_entity_type',
			),
		);

		$GLOBALS['wl_test_taxonomies']['wl_entity_type'] = (object) array(
			'name' => 'wl_entity_type',
		);
		$GLOBALS['wl_test_post_terms']['42|wl_entity_type'] = array(
			new WP_Term( 11, 'Article', 'article' ),
			new WP_Term( 12, 'CreativeWork', 'creative-work' ),
		);
		$GLOBALS['wl_test_terms_list'] = array(
			new WP_Term( 11, 'Article', 'article' ),
			new WP_Term( 12, 'CreativeWork', 'creative-work' ),
		);
		$GLOBALS['wl_test_terms_checklist'] = '<li>All terms</li>';

		ob_start();
		Wordlift_Cloud_Schemaorg_Taxonomy_Metabox::render( $post, $box );
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'Selected', $output );
		self::assertStringContainsString( 'Most Used', $output );
		self::assertStringContainsString( 'A-Z', $output );
		self::assertStringContainsString( 'wl_entity_type-selected-list', $output );
		self::assertStringContainsString( 'Article', $output );
	}

	public function test_render_returns_early_when_taxonomy_missing(): void {
		$post = (object) array( 'ID' => 42 );
		$box  = array(
			'args' => array(
				'taxonomy' => 'wl_entity_type',
			),
		);

		ob_start();
		Wordlift_Cloud_Schemaorg_Taxonomy_Metabox::render( $post, $box );
		$output = (string) ob_get_clean();

		self::assertSame( '', $output );
	}
}

