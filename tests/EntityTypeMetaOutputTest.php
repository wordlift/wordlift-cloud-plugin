<?php
declare( strict_types=1 );

final class EntityTypeMetaOutputTest extends WordPressTestCase {

	public function test_get_term_slugs_reads_object_term(): void {
		$term_one       = new stdClass();
		$term_one->slug = 'article';
		$term_two       = new stdClass();
		$term_two->slug = 'person';

		self::assertSame( array( 'article', 'person' ), Wordlift_Cloud_Entity_Type_Taxonomy::get_term_slugs( array( $term_one, $term_two ) ) );
	}

	public function test_get_term_slugs_reads_array_terms_and_skips_invalid_values(): void {
		$terms = array(
			array( 'name' => 'NoSlug' ),
			array( 'slug' => '' ),
			array( 'slug' => 'person' ),
			array( 'slug' => 'person' ),
		);

		self::assertSame( array( 'person' ), Wordlift_Cloud_Entity_Type_Taxonomy::get_term_slugs( $terms ) );
	}

	public function test_get_term_slugs_returns_empty_array_when_missing(): void {
		self::assertSame( array(), Wordlift_Cloud_Entity_Type_Taxonomy::get_term_slugs( array( new stdClass(), array( 'slug' => '' ), array() ) ) );
	}

	public function test_build_entity_type_meta_tag_escapes_and_formats_output(): void {
		$output = Wordlift_Cloud_Entity_Type_Taxonomy::build_entity_type_meta_tag( 'a"b<c' );

		self::assertSame(
			'<meta name="wl:entity_type" content="a&quot;b&lt;c" />',
			$output
		);
	}

	public function test_get_metabox_tabs_script_targets_entity_type_metabox_tabs(): void {
		$service = new Wordlift_Cloud_Entity_Type_Taxonomy();
		$script  = $service->get_metabox_tabs_script();

		self::assertStringContainsString( '#taxonomy-wl_entity_type.categorydiv', $script );
		self::assertStringContainsString( '#wl_entity_type-tabs a', $script );
		self::assertStringContainsString( '> .tabs-panel', $script );
		self::assertStringContainsString( '#wl_entity_type-selected-list', $script );
		self::assertStringContainsString( '.wl-selected-count', $script );
		self::assertStringContainsString( 'wordlift-favicon.ico', $script );
		self::assertStringContainsString( '.wl-entity-type-icon', $script );
	}
}
