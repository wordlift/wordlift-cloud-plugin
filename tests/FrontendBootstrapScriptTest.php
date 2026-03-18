<?php
declare( strict_types=1 );

final class FrontendBootstrapScriptTest extends WordPressTestCase {
	public function test_register_hooks_adds_wp_head_action(): void {
		$service = new Wordlift_Cloud_Frontend_Bootstrap_Script();

		$service->register_hooks();

		self::assertCount( 6, $GLOBALS['wl_test_actions'] );
		self::assertSame( 'wp_head', $GLOBALS['wl_test_actions'][0]['hook'] );
	}

	public function test_inject_script_outputs_bootstrap_when_frontend_enabled_and_consented(): void {
		$service = new Wordlift_Cloud_Frontend_Bootstrap_Script();
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Frontend_Bootstrap_Script::OPTION_ENABLED ] = 1;

		ob_start();
		$service->inject_script();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'https://cloud.wordlift.io/app/bootstrap.js', $output );
	}

	public function test_inject_script_outputs_bootstrap_when_frontend_injection_uses_default_enabled_state(): void {
		$service = new Wordlift_Cloud_Frontend_Bootstrap_Script();

		ob_start();
		$service->inject_script();
		$output = (string) ob_get_clean();
		self::assertStringContainsString( 'https://cloud.wordlift.io/app/bootstrap.js', $output );
		self::assertStringNotContainsString( 'data-faq="true"', $output );
	}

	public function test_inject_script_skips_admin_and_no_consent(): void {
		$service = new Wordlift_Cloud_Frontend_Bootstrap_Script();
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Frontend_Bootstrap_Script::OPTION_ENABLED ] = 1;

		$GLOBALS['wl_test_is_admin'] = true;
		ob_start();
		$service->inject_script();
		$admin_output = (string) ob_get_clean();
		self::assertSame( '', $admin_output );

		$GLOBALS['wl_test_is_admin'] = false;
		add_filter(
			'wordlift_cloud_has_bootstrap_consent',
			static function () {
				return false;
			}
		);
		ob_start();
		$service->inject_script();
		$consent_output = (string) ob_get_clean();
		self::assertSame( '', $consent_output );
	}

	public function test_inject_script_outputs_faq_attributes_when_faq_enabled_and_target_present(): void {
		$service = new Wordlift_Cloud_Frontend_Bootstrap_Script();
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Frontend_Bootstrap_Script::OPTION_ENABLED ] = 1;
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Frontend_Bootstrap_Script::OPTION_FAQ_TARGET_ID ] = 'faq-container';
		$GLOBALS['wl_test_is_singular']       = true;
		$GLOBALS['wl_test_queried_object_id'] = 15;

		ob_start();
		$service->inject_script();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'data-faq="true"', $output );
		self::assertStringContainsString( 'data-faq-target-id="faq-container"', $output );
		self::assertStringContainsString( 'data-faq-template-id="wl-faq-template"', $output );
	}

	public function test_render_faq_template_script_outputs_template_when_enabled(): void {
		$service = new Wordlift_Cloud_Frontend_Bootstrap_Script();
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Frontend_Bootstrap_Script::OPTION_ENABLED ] = 1;
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Frontend_Bootstrap_Script::OPTION_FAQ_TEMPLATE ] = '<div>{{#faqs}}{{question}}{{{answer}}}{{/faqs}}</div>';
		$GLOBALS['wl_test_is_singular']       = true;
		$GLOBALS['wl_test_queried_object_id'] = 16;

		ob_start();
		$service->render_faq_template_script();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'type="text/template"', $output );
		self::assertStringContainsString( 'id="wl-faq-template"', $output );
		self::assertStringContainsString( '{{#faqs}}', $output );
	}

	public function test_save_faq_override_persists_enable_and_deletes_on_inherit(): void {
		$service = new Wordlift_Cloud_Frontend_Bootstrap_Script();
		$post    = (object) array(
			'ID'        => 42,
			'post_type' => 'post',
		);

		$_POST['wordlift_cloud_faq_override_nonce'] = 'nonce-wordlift_cloud_faq_override';
		$_POST['wordlift_cloud_faq_override']       = Wordlift_Cloud_Frontend_Bootstrap_Script::FAQ_OVERRIDE_ENABLE;
		$service->save_faq_override( 42, $post );
		self::assertSame(
			Wordlift_Cloud_Frontend_Bootstrap_Script::FAQ_OVERRIDE_ENABLE,
			$GLOBALS['wl_test_post_meta'][42][ Wordlift_Cloud_Frontend_Bootstrap_Script::POST_META_FAQ_OVERRIDE ]
		);

		$_POST['wordlift_cloud_faq_override'] = Wordlift_Cloud_Frontend_Bootstrap_Script::FAQ_OVERRIDE_INHERIT;
		$service->save_faq_override( 42, $post );
		self::assertArrayNotHasKey(
			Wordlift_Cloud_Frontend_Bootstrap_Script::POST_META_FAQ_OVERRIDE,
			$GLOBALS['wl_test_post_meta'][42]
		);
	}

	public function test_register_faq_metaboxes_adds_metabox_for_public_post_types(): void {
		$service = new Wordlift_Cloud_Frontend_Bootstrap_Script();
		$GLOBALS['wl_test_post_types'] = array(
			'post' => 'post',
			'page' => 'page',
			'book' => 'book',
		);

		$service->register_faq_metaboxes();

		self::assertCount( 3, $GLOBALS['wl_test_meta_boxes'] );
		self::assertSame( 'wordlift-cloud-faq-rendering', $GLOBALS['wl_test_meta_boxes'][0]['id'] );
	}
}
