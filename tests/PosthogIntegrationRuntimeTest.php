<?php
declare( strict_types=1 );

final class PosthogIntegrationRuntimeTest extends WordPressTestCase {
	public function test_register_hooks_registers_actions_and_filter(): void {
		$integration = new Wordlift_Cloud_Posthog_Integration();

		$integration->register_hooks();

		self::assertCount( 4, $GLOBALS['wl_test_actions'] );
		self::assertCount( 1, $GLOBALS['wl_test_filters'] );
		self::assertSame( 'admin_init', $GLOBALS['wl_test_actions'][0]['hook'] );
		self::assertStringContainsString( 'plugin_action_links_', $GLOBALS['wl_test_filters'][0]['hook'] );
	}

	public function test_register_settings_registers_expected_fields(): void {
		$integration = new Wordlift_Cloud_Posthog_Integration();
		$integration->register_settings();

		self::assertCount( 6, $GLOBALS['wl_test_registered_settings'] );
		self::assertCount( 1, $GLOBALS['wl_test_settings_sections'] );
		self::assertCount( 4, $GLOBALS['wl_test_settings_fields'] );
	}

	public function test_register_settings_page_and_render_settings_page_output(): void {
		$integration = new Wordlift_Cloud_Posthog_Integration();

		$integration->register_settings_page();
		self::assertCount( 1, $GLOBALS['wl_test_added_options_pages'] );

		ob_start();
		$integration->render_settings_page();
		$output = (string) ob_get_clean();
		self::assertStringContainsString( 'WordLift Cloud Settings', $output );
		self::assertStringContainsString( 'option_page', $output );
	}

	public function test_render_settings_section_and_enabled_field_output(): void {
		$integration = new Wordlift_Cloud_Posthog_Integration();

		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_ENABLED ] = 1;

		ob_start();
		$integration->render_settings_section();
		$section_output = (string) ob_get_clean();
		self::assertStringContainsString( 'FAQ rendering options and optional admin telemetry', $section_output );

		ob_start();
		$integration->render_enabled_field();
		$field_output = (string) ob_get_clean();
		self::assertStringContainsString( 'Enable telemetry for authenticated admin usage.', $field_output );
		self::assertStringContainsString( 'checked="checked"', $field_output );
	}

	public function test_add_plugin_action_links_prepends_settings_link(): void {
		$integration = new Wordlift_Cloud_Posthog_Integration();
		$links       = array( '<a href="#">Deactivate</a>' );

		$result = $integration->add_plugin_action_links( $links );

		self::assertSame( 2, count( $result ) );
		self::assertStringContainsString( 'options-general.php?page=wordlift-cloud-settings', $result[0] );
	}

	public function test_enqueue_admin_assets_adds_inline_script_when_enabled_and_consented(): void {
		$integration = new Wordlift_Cloud_Posthog_Integration();

		$GLOBALS['wl_test_is_admin']          = true;
		$GLOBALS['wl_test_is_user_logged_in'] = true;
		$GLOBALS['wl_test_user_id']           = 3;
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_ENABLED ] = 1;
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_PROJECT_TOKEN ] = 'phc_test';
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_API_HOST ] = 'https://eu.i.posthog.com';
		$_GET['page']                         = Wordlift_Cloud_Posthog_Integration::SETTINGS_PAGE;
		$_GET['settings-updated']             = 'true';

		$integration->enqueue_admin_assets( 'options-general.php' );

		self::assertGreaterThanOrEqual( 1, count( $GLOBALS['wl_test_inline_scripts'] ) );
		self::assertSame( 'common', $GLOBALS['wl_test_inline_scripts'][ count( $GLOBALS['wl_test_inline_scripts'] ) - 1 ]['handle'] );
		self::assertStringContainsString( 'posthog.init', $GLOBALS['wl_test_inline_scripts'][ count( $GLOBALS['wl_test_inline_scripts'] ) - 1 ]['data'] );
	}

	public function test_capture_server_event_returns_false_when_not_authorized(): void {
		self::assertFalse( Wordlift_Cloud_Posthog_Integration::capture_server_event( 'evt' ) );
		self::assertSame( array(), $GLOBALS['wl_test_remote_posts'] );
	}

	public function test_capture_server_event_dispatches_non_blocking_request_when_allowed(): void {
		$GLOBALS['wl_test_is_admin']          = true;
		$GLOBALS['wl_test_is_user_logged_in'] = true;
		$GLOBALS['wl_test_user_id']           = 11;
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_ENABLED ] = 1;
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_PROJECT_TOKEN ] = 'phc_token';
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_API_HOST ] = 'https://eu.i.posthog.com';

		$result = Wordlift_Cloud_Posthog_Integration::capture_server_event(
			'my_event',
			array( 'source' => 'test' )
		);

		self::assertTrue( $result );
		self::assertCount( 1, $GLOBALS['wl_test_remote_posts'] );
		self::assertSame( 'https://eu.i.posthog.com/capture/', $GLOBALS['wl_test_remote_posts'][0]['url'] );
		self::assertSame( false, $GLOBALS['wl_test_remote_posts'][0]['args']['blocking'] );
		self::assertStringContainsString( '"event":"my_event"', (string) $GLOBALS['wl_test_remote_posts'][0]['args']['body'] );
	}

	public function test_get_project_token_and_api_host_allow_filter_overrides(): void {
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_PROJECT_TOKEN ] = 'phc_one';
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_API_HOST ] = 'https://us.i.posthog.com';

		add_filter(
			'wordlift_cloud_posthog_project_token',
			static function () {
				return 'phc_filter_token';
			}
		);
		add_filter(
			'wordlift_cloud_posthog_api_host',
			static function () {
				return 'https://eu.i.posthog.com';
			}
		);

		self::assertSame( 'phc_filter_token', Wordlift_Cloud_Posthog_Integration::get_project_token() );
		self::assertSame( 'https://eu.i.posthog.com', Wordlift_Cloud_Posthog_Integration::get_api_host() );
	}

	public function test_capture_server_event_returns_false_for_missing_user_or_wp_error(): void {
		$GLOBALS['wl_test_is_admin']          = true;
		$GLOBALS['wl_test_is_user_logged_in'] = true;
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_ENABLED ] = 1;
		$GLOBALS['wl_test_options'][ Wordlift_Cloud_Posthog_Integration::OPTION_PROJECT_TOKEN ] = 'phc_token';

		$GLOBALS['wl_test_user_id'] = 0;
		self::assertFalse( Wordlift_Cloud_Posthog_Integration::capture_server_event( 'event_without_user' ) );

		$GLOBALS['wl_test_user_id']          = 22;
		$GLOBALS['wl_test_remote_post_result'] = new WP_Error();
		self::assertFalse( Wordlift_Cloud_Posthog_Integration::capture_server_event( 'event_with_error' ) );
	}
}
