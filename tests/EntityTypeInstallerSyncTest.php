<?php
declare( strict_types=1 );

final class EntityTypeInstallerSyncTest extends WordPressTestCase {
	public function test_maybe_sync_updates_terms_meta_and_options(): void {
		$dataset_file = tempnam( sys_get_temp_dir(), 'wl-schema-' );
		file_put_contents(
			$dataset_file,
			(string) json_encode(
				array(
					'datasetVersion' => 'v1',
					'schemaClasses'  => array(
						array(
							'name'        => 'Thing',
							'label'       => 'Thing',
							'slug'        => 'thing',
							'description' => 'Root',
							'parents'     => array(),
							'children'    => array( 'creative-work' ),
						),
						array(
							'name'        => 'CreativeWork',
							'label'       => 'CreativeWork',
							'slug'        => 'creative-work',
							'description' => 'Descendant',
							'parents'     => array( 'thing' ),
							'children'    => array(),
						),
						array(
							'name'        => 'DataType',
							'label'       => 'DataType',
							'slug'        => 'data-type',
							'description' => 'Excluded',
							'parents'     => array( 'thing' ),
							'children'    => array( 'text' ),
						),
						array(
							'name'        => 'Text',
							'label'       => 'Text',
							'slug'        => 'text',
							'description' => 'Excluded child',
							'parents'     => array( 'data-type' ),
							'children'    => array(),
						),
					),
				)
			)
		);

		$GLOBALS['wl_test_terms_by_slug']['thing']        = array( 'term_id' => 2 );
		$GLOBALS['wl_test_terms_by_slug']['data-type']    = array( 'term_id' => 9 );
		$GLOBALS['wl_test_terms_by_slug']['text']         = array( 'term_id' => 10 );

		$installer = new Wordlift_Cloud_Entity_Type_Taxonomy_Installer( 'wl_entity_type', $dataset_file );
		$result    = $installer->maybe_sync();

		self::assertTrue( $result );
		self::assertSame( 'synced', $installer->get_last_sync_status() );
		self::assertArrayHasKey( Wordlift_Cloud_Entity_Type_Taxonomy_Installer::DATA_HASH_OPTION, $GLOBALS['wl_test_options'] );
		self::assertSame( 'v1', $GLOBALS['wl_test_options'][ Wordlift_Cloud_Entity_Type_Taxonomy_Installer::DATA_VERSION_OPTION ] );
		self::assertNotEmpty( $GLOBALS['wl_test_term_inserts'] );
		self::assertNotEmpty( $GLOBALS['wl_test_term_updates'] );
		self::assertNotEmpty( $GLOBALS['wl_test_term_deletes'] );

		unlink( $dataset_file );
	}

	public function test_maybe_sync_returns_no_change_when_hash_matches_and_not_forced(): void {
		$dataset_file = tempnam( sys_get_temp_dir(), 'wl-schema-' );
		$dataset      = array(
			'datasetVersion' => 'v1',
			'schemaClasses'  => array(
				array(
					'name'        => 'Thing',
					'label'       => 'Thing',
					'slug'        => 'thing',
					'description' => 'Root',
					'parents'     => array(),
					'children'    => array(),
				),
			),
		);
		file_put_contents( $dataset_file, (string) json_encode( $dataset ) );

		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Entity_Type_Taxonomy_Installer::DATA_HASH_OPTION ] = md5( (string) wp_json_encode( $dataset ) );
		$installer = new Wordlift_Cloud_Entity_Type_Taxonomy_Installer( 'wl_entity_type', $dataset_file );

		self::assertTrue( $installer->maybe_sync( false ) );
		self::assertSame( 'no-change', $installer->get_last_sync_status() );
		self::assertSame( array(), $GLOBALS['wl_test_term_inserts'] );

		unlink( $dataset_file );
	}

	public function test_maybe_sync_fails_for_missing_dataset(): void {
		$installer = new Wordlift_Cloud_Entity_Type_Taxonomy_Installer( 'wl_entity_type', __DIR__ . '/does-not-exist.json' );

		self::assertFalse( $installer->maybe_sync() );
		self::assertSame( 'failed', $installer->get_last_sync_status() );
	}

	public function test_maybe_sync_defaults_missing_dataset_version_to_unknown(): void {
		$dataset_file = tempnam( sys_get_temp_dir(), 'wl-schema-' );
		file_put_contents(
			$dataset_file,
			(string) json_encode(
				array(
					'schemaClasses' => array(
						array(
							'name'        => 'Thing',
							'label'       => 'Thing',
							'slug'        => 'thing',
							'description' => 'Root',
							'parents'     => array(),
							'children'    => array(),
						),
					),
				)
			)
		);

		$installer = new Wordlift_Cloud_Entity_Type_Taxonomy_Installer( 'wl_entity_type', $dataset_file );
		self::assertTrue( $installer->maybe_sync() );
		self::assertSame( 'unknown', $GLOBALS['wl_test_options'][ Wordlift_Cloud_Entity_Type_Taxonomy_Installer::DATA_VERSION_OPTION ] );

		unlink( $dataset_file );
	}

	public function test_maybe_sync_removes_non_dataset_terms_even_when_hash_is_unchanged(): void {
		$dataset_file = tempnam( sys_get_temp_dir(), 'wl-schema-' );
		$dataset      = array(
			'datasetVersion' => 'v1',
			'schemaClasses'  => array(
				array(
					'name'        => 'Service',
					'label'       => 'Service',
					'slug'        => 'service',
					'description' => 'Service type',
					'parents'     => array(),
					'children'    => array(),
				),
			),
		);
		file_put_contents( $dataset_file, (string) json_encode( $dataset ) );

		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Entity_Type_Taxonomy_Installer::DATA_HASH_OPTION ] = md5( (string) wp_json_encode( $dataset ) );
		$GLOBALS['wl_test_terms_list'] = array(
			(object) array(
				'term_id' => 41,
				'slug'    => 'service',
			),
			(object) array(
				'term_id' => 42,
				'slug'    => 'service-fr',
			),
		);

		$installer = new Wordlift_Cloud_Entity_Type_Taxonomy_Installer( 'wl_entity_type', $dataset_file );

		self::assertTrue( $installer->maybe_sync( false ) );
		self::assertSame( 'no-change', $installer->get_last_sync_status() );
		self::assertCount( 1, $GLOBALS['wl_test_term_deletes'] );
		self::assertSame( 42, $GLOBALS['wl_test_term_deletes'][0]['term_id'] );

		unlink( $dataset_file );
	}
}
