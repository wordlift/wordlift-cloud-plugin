<?php
declare( strict_types=1 );

final class EntityTypeTaxonomyServiceTest extends WordPressTestCase {
	public function test_register_hooks_registers_all_expected_actions(): void {
		$service = new Wordlift_Cloud_Entity_Type_Taxonomy();

		$service->register_hooks();

		$hooks = array_map(
			static function ( $action ) {
				return $action['hook'];
			},
			$GLOBALS['wl_test_actions']
		);

		self::assertContains( 'init', $hooks );
		self::assertContains( 'wp_head', $hooks );
		self::assertContains( 'admin_enqueue_scripts', $hooks );
		self::assertContains( 'save_post', $hooks );
	}

	public function test_register_taxonomy_uses_supported_post_types_without_attachment(): void {
		$service = new Wordlift_Cloud_Entity_Type_Taxonomy();

		$service->register_taxonomy();

		self::assertCount( 1, $GLOBALS['wl_test_registered_taxonomy'] );
		$registered = $GLOBALS['wl_test_registered_taxonomy'][0];
		self::assertSame( Wordlift_Cloud_Entity_Type_Taxonomy::TAXONOMY_NAME, $registered['taxonomy'] );
		self::assertSame( array( 'post', 'page' ), $registered['object_type'] );
		self::assertSame( array( 'Wordlift_Cloud_Schemaorg_Taxonomy_Metabox', 'render' ), $registered['args']['meta_box_cb'] );
	}

	public function test_enqueue_admin_assets_adds_inline_script_on_post_edit_screens(): void {
		$service = new Wordlift_Cloud_Entity_Type_Taxonomy();

		$service->enqueue_admin_assets( 'post.php' );
		$service->enqueue_admin_assets( 'post-new.php' );
		$service->enqueue_admin_assets( 'edit.php' );

		self::assertCount( 2, $GLOBALS['wl_test_inline_scripts'] );
		self::assertCount( 2, $GLOBALS['wl_test_inline_styles'] );
		self::assertSame( 'post', $GLOBALS['wl_test_inline_scripts'][0]['handle'] );
		self::assertStringContainsString( 'wl_entity_type-tabs', $GLOBALS['wl_test_inline_scripts'][0]['data'] );
		self::assertSame( 'common', $GLOBALS['wl_test_inline_styles'][0]['handle'] );
		self::assertStringContainsString( '#taxonomy-wl_entity_type.postbox', $GLOBALS['wl_test_inline_styles'][0]['data'] );
	}

	public function test_enqueue_admin_assets_supports_block_editor_handles_when_registered(): void {
		$service = new Wordlift_Cloud_Entity_Type_Taxonomy();

		$GLOBALS['wl_test_registered_scripts']['post']         = false;
		$GLOBALS['wl_test_registered_scripts']['wp-edit-post'] = true;
		$GLOBALS['wl_test_registered_styles']['common']        = false;
		$GLOBALS['wl_test_registered_styles']['wp-edit-blocks']= true;

		$service->enqueue_admin_assets( 'post.php' );

		self::assertCount( 1, $GLOBALS['wl_test_inline_scripts'] );
		self::assertCount( 1, $GLOBALS['wl_test_inline_styles'] );
		self::assertSame( 'wp-edit-post', $GLOBALS['wl_test_inline_scripts'][0]['handle'] );
		self::assertSame( 'wp-edit-blocks', $GLOBALS['wl_test_inline_styles'][0]['handle'] );
	}

	public function test_output_frontend_entity_type_meta_outputs_multiple_tags(): void {
		$service = new Wordlift_Cloud_Entity_Type_Taxonomy();

		$GLOBALS['wl_test_is_singular']       = true;
		$GLOBALS['wl_test_queried_object_id'] = 123;
		$GLOBALS['wl_test_post_terms']['123|' . Wordlift_Cloud_Entity_Type_Taxonomy::TAXONOMY_NAME] = array(
			(object) array( 'slug' => 'article' ),
			(object) array( 'slug' => 'creative-work' ),
		);

		ob_start();
		$service->output_frontend_entity_type_meta();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( '<meta name="wl:entity_type" content="article" />', $output );
		self::assertStringContainsString( '<meta name="wl:entity_type" content="creative-work" />', $output );
	}

	public function test_capture_admin_selection_updated_sends_event_for_supported_post_types(): void {
		$service = new Wordlift_Cloud_Entity_Type_Taxonomy();

		$GLOBALS['wl_test_is_admin']          = true;
		$GLOBALS['wl_test_is_user_logged_in'] = true;
		$GLOBALS['wl_test_user_id']           = 5;
		$GLOBALS['wl_test_current_user_can']  = true;
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_ENABLED ]       = 1;
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_PROJECT_TOKEN ] = 'phc_test';
		$GLOBALS['wl_test_taxonomies'][ Wordlift_Cloud_Entity_Type_Taxonomy::TAXONOMY_NAME ] = (object) array(
			'object_type' => array( 'post' ),
		);
		$GLOBALS['wl_test_post_terms']['77|' . Wordlift_Cloud_Entity_Type_Taxonomy::TAXONOMY_NAME] = array(
			(object) array( 'slug' => 'article' ),
		);

		$post = (object) array( 'post_type' => 'post' );
		$service->capture_admin_selection_updated( 77, $post, true );

		self::assertCount( 1, $GLOBALS['wl_test_remote_posts'] );
		$body = (string) $GLOBALS['wl_test_remote_posts'][0]['args']['body'];
		self::assertStringContainsString( 'wl_entity_type_selection_updated', $body );
		self::assertStringContainsString( '"post_id":77', $body );
	}

	public function test_activate_registers_taxonomy_runs_sync_and_flushes_rewrite_rules(): void {
		$service = new Wordlift_Cloud_Entity_Type_Taxonomy();

		$fake_installer = new class() {
			/** @var array<int,bool> */
			public $force_args = array();
			public function maybe_sync( $force = false ) {
				$this->force_args[] = (bool) $force;
				return true;
			}
		};

		$reflection = new ReflectionClass( $service );
		$property   = $reflection->getProperty( 'installer' );
		$property->setAccessible( true );
		$property->setValue( $service, $fake_installer );

		$service->activate();

		self::assertSame( array( true ), $fake_installer->force_args );
		self::assertCount( 1, $GLOBALS['wl_test_registered_taxonomy'] );
		self::assertSame( 1, $GLOBALS['wl_test_flush_rewrite_rules'] );
	}

	public function test_maybe_sync_terms_captures_sync_event_when_status_is_synced_in_admin(): void {
		$service = new Wordlift_Cloud_Entity_Type_Taxonomy();

		$fake_installer = new class() {
			public function maybe_sync( $force = false ) {
				return true;
			}
			public function get_last_sync_status() {
				return 'synced';
			}
		};

		$reflection = new ReflectionClass( $service );
		$property   = $reflection->getProperty( 'installer' );
		$property->setAccessible( true );
		$property->setValue( $service, $fake_installer );

		$GLOBALS['wl_test_is_admin']          = true;
		$GLOBALS['wl_test_is_user_logged_in'] = true;
		$GLOBALS['wl_test_user_id']           = 7;
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_ENABLED ]       = 1;
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_PROJECT_TOKEN ] = 'phc_test';

		$service->maybe_sync_terms();

		self::assertCount( 1, $GLOBALS['wl_test_remote_posts'] );
		self::assertStringContainsString( 'wl_entity_type_sync_run', (string) $GLOBALS['wl_test_remote_posts'][0]['args']['body'] );
	}
}
