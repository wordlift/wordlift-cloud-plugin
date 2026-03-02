<?php
declare( strict_types=1 );

final class EntityTypeTaxonomyWalkerTest extends WordPressTestCase {
	public function test_walk_replaces_checkboxes_with_radios(): void {
		$walker = new Wordlift_Cloud_Entity_Type_Taxonomy_Walker();

		$output = $walker->walk( array(), 0 );

		self::assertStringContainsString( 'type="radio"', $output );
		self::assertStringNotContainsString( 'type="checkbox"', $output );
	}
}

