<?php
declare( strict_types=1 );

final class PosthogIntegrationTest extends WordPressTestCase {

	public function test_sanitize_enabled_converts_to_boolean_int(): void {
		self::assertSame( 1, Wordlift_Cloud_Posthog_Integration::sanitize_enabled( 1 ) );
		self::assertSame( 0, Wordlift_Cloud_Posthog_Integration::sanitize_enabled( 0 ) );
		self::assertSame( 0, Wordlift_Cloud_Posthog_Integration::sanitize_enabled( 'yes' ) );
	}

	public function test_sanitize_project_token_strips_invalid_characters(): void {
		self::assertSame(
			'phc_Abc123-_',
			Wordlift_Cloud_Posthog_Integration::sanitize_project_token( ' phc_Abc123-_!@#$ ' )
		);
	}

	public function test_sanitize_api_host_keeps_valid_https_and_falls_back_for_invalid_values(): void {
		self::assertSame(
			'https://eu.i.posthog.com',
			Wordlift_Cloud_Posthog_Integration::sanitize_api_host( 'https://eu.i.posthog.com/' )
		);

		self::assertSame(
			Wordlift_Cloud_Posthog_Integration::DEFAULT_API_HOST,
			Wordlift_Cloud_Posthog_Integration::sanitize_api_host( 'http://eu.i.posthog.com' )
		);

		self::assertSame(
			Wordlift_Cloud_Posthog_Integration::DEFAULT_API_HOST,
			Wordlift_Cloud_Posthog_Integration::sanitize_api_host( 'not-a-url' )
		);
	}

	public function test_build_loader_script_contains_required_configuration(): void {
		$script = Wordlift_Cloud_Posthog_Integration::build_loader_script(
			'phc_test_token',
			'https://eu.i.posthog.com',
			'2026-01-30'
		);

		self::assertStringContainsString( 'posthog.init(', $script );
		self::assertStringContainsString( '"phc_test_token"', $script );
		self::assertStringContainsString( '"api_host":"https:\\/\\/eu.i.posthog.com"', $script );
		self::assertStringContainsString( '"defaults":"2026-01-30"', $script );
		self::assertStringContainsString( '"person_profiles":"identified_only"', $script );
		self::assertStringContainsString( '-assets.i.posthog.com', $script );
		self::assertStringContainsString( '/static/array.js', $script );
	}

	public function test_build_admin_loader_script_contains_admin_events_and_identify(): void {
		$script = Wordlift_Cloud_Posthog_Integration::build_admin_loader_script(
			'phc_test_token',
			'https://eu.i.posthog.com',
			'2026-01-30',
			array(
				'user_id'                 => 1,
				'hook_suffix'             => 'options-general.php',
				'is_entity_types_screen'  => false,
				'is_settings_screen'      => true,
				'settings_updated'        => true,
				'posthog_enabled'         => true,
				'api_host_configured'     => 'https://eu.i.posthog.com',
			)
		);

		self::assertStringContainsString( 'posthog.identify', $script );
		self::assertStringContainsString( 'wl_cloud_settings_viewed', $script );
		self::assertStringContainsString( 'wl_cloud_settings_saved', $script );
	}

	public function test_build_settings_action_link_returns_settings_anchor(): void {
		$link = Wordlift_Cloud_Posthog_Integration::build_settings_action_link( 'https://example.test/wp-admin/options-general.php?page=wordlift-cloud-settings' );

		self::assertStringContainsString( '<a href="https://example.test/wp-admin/options-general.php?page=wordlift-cloud-settings">', $link );
		self::assertStringContainsString( '>Settings</a>', $link );
	}

	public function test_register_settings_registers_frontend_toggle_option(): void {
		$service = new Wordlift_Cloud_Posthog_Integration();
		$service->register_settings();

		$names = array_map(
			static function ( $entry ) {
				return (string) $entry['name'];
			},
			$GLOBALS['wl_test_registered_settings']
		);

		self::assertContains( Wordlift_Cloud_Frontend_Bootstrap_Script::OPTION_ENABLED, $names );
		self::assertContains( Wordlift_Cloud_Frontend_Bootstrap_Script::OPTION_FAQ_TARGET_ID, $names );
		self::assertContains( Wordlift_Cloud_Frontend_Bootstrap_Script::OPTION_FAQ_TEMPLATE, $names );
	}

	public function test_add_privacy_policy_content_registers_text(): void {
		$service = new Wordlift_Cloud_Posthog_Integration();
		$service->add_privacy_policy_content();

		self::assertNotEmpty( $GLOBALS['wl_test_privacy_policy_content'] );
		self::assertSame( 'WordLift Cloud', $GLOBALS['wl_test_privacy_policy_content'][0]['plugin_name'] );
	}
}
