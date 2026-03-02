<?php
declare( strict_types=1 );

final class FrontendBootstrapScriptTest extends WordPressTestCase {
	public function test_register_hooks_adds_wp_head_action(): void {
		$service = new Wordlift_Cloud_Frontend_Bootstrap_Script();

		$service->register_hooks();

		self::assertCount( 1, $GLOBALS['wl_test_actions'] );
		self::assertSame( 'wp_head', $GLOBALS['wl_test_actions'][0]['hook'] );
	}

	public function test_inject_script_outputs_bootstrap_when_frontend_and_consented(): void {
		$service = new Wordlift_Cloud_Frontend_Bootstrap_Script();

		ob_start();
		$service->inject_script();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'https://cloud.wordlift.io/app/bootstrap.js', $output );
	}

	public function test_inject_script_skips_admin_and_no_consent(): void {
		$service = new Wordlift_Cloud_Frontend_Bootstrap_Script();

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
}

