<?php
/**
 * Optional telemetry integration for authenticated admin analytics.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles telemetry settings and conditional script injection.
 */
class Wordlift_Cloud_Posthog_Integration implements Wordlift_Cloud_Analytics_Provider {

	/**
	 * Settings group slug.
	 */
	const SETTINGS_GROUP = 'wordlift_cloud_settings';

	/**
	 * Settings page slug.
	 */
	const SETTINGS_PAGE = 'wordlift-cloud-settings';

	/**
	 * Option key used to toggle PostHog.
	 */
	const OPTION_ENABLED = 'wordlift_cloud_posthog_enabled';

	/**
	 * Option key storing the PostHog project token.
	 */
	const OPTION_PROJECT_TOKEN = 'wordlift_cloud_posthog_project_token';

	/**
	 * Option key storing PostHog API host.
	 */
	const OPTION_API_HOST = 'wordlift_cloud_posthog_api_host';

	/**
	 * PostHog config snapshot used as defaults.
	 */
	const DEFAULTS_SNAPSHOT = '2026-01-30';

	/**
	 * Only create person profiles for identified users.
	 */
	const PERSON_PROFILES = 'identified_only';

	/**
	 * Default API host for US cloud.
	 */
	const DEFAULT_API_HOST = 'https://us.i.posthog.com';

	/**
	 * Register admin hooks.
	 */
	public function register_hooks() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) . '/wordlift-cloud.php' ), array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 * Register WordPress options and settings fields.
	 */
	public function register_settings() {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_ENABLED,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( __CLASS__, 'sanitize_enabled' ),
				'default'           => 0,
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_PROJECT_TOKEN,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_project_token' ),
				'default'           => '',
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_API_HOST,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_api_host' ),
				'default'           => self::DEFAULT_API_HOST,
			)
		);

		add_settings_section(
			'wordlift_cloud_posthog_section',
			__( 'Usage Telemetry', 'wordlift-cloud' ),
			array( $this, 'render_settings_section' ),
			self::SETTINGS_PAGE
		);

		add_settings_field(
			self::OPTION_ENABLED,
			__( 'Enable telemetry', 'wordlift-cloud' ),
			array( $this, 'render_enabled_field' ),
			self::SETTINGS_PAGE,
			'wordlift_cloud_posthog_section'
		);
	}

	/**
	 * Add a shortcut to plugin settings from the plugins screen.
	 *
	 * @param array<int,string> $links Existing row action links.
	 *
	 * @return array<int,string>
	 */
	public function add_plugin_action_links( $links ) {
		array_unshift( $links, self::build_settings_action_link( admin_url( 'options-general.php?page=' . self::SETTINGS_PAGE ) ) );
		return $links;
	}

	/**
	 * Build HTML for the settings action link.
	 *
	 * @param string $url Target URL.
	 *
	 * @return string
	 */
	public static function build_settings_action_link( $url ) {
		return '<a href="' . esc_attr( (string) $url ) . '">' . esc_html__( 'Settings', 'wordlift-cloud' ) . '</a>';
	}

	/**
	 * Register settings page.
	 */
	public function register_settings_page() {
		add_options_page(
			__( 'WordLift Cloud', 'wordlift-cloud' ),
			__( 'WordLift Cloud', 'wordlift-cloud' ),
			'manage_options',
			self::SETTINGS_PAGE,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page content.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'WordLift Cloud Settings', 'wordlift-cloud' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::SETTINGS_PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Section intro text.
	 */
	public function render_settings_section() {
		echo '<p>' . esc_html__( 'Configure optional telemetry for authenticated WordPress admin usage.', 'wordlift-cloud' ) . '</p>';
	}

	/**
	 * Render enabled checkbox.
	 */
	public function render_enabled_field() {
		$enabled = (int) get_option( self::OPTION_ENABLED, 0 );
		?>
		<label for="<?php echo esc_attr( self::OPTION_ENABLED ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( self::OPTION_ENABLED ); ?>"
				name="<?php echo esc_attr( self::OPTION_ENABLED ); ?>"
				value="1"
				<?php checked( 1, $enabled ); ?>
			/>
			<?php echo esc_html__( 'Enable telemetry for authenticated admin usage.', 'wordlift-cloud' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Telemetry is limited to authenticated admin feature usage.', 'wordlift-cloud' ); ?></p>
		<?php
	}

	/**
	 * Enqueue PostHog admin snippet when enabled.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( ! is_admin() || ! is_user_logged_in() ) {
			return;
		}

		if ( ! self::is_enabled() ) {
			return;
		}

		$has_consent = (bool) apply_filters( 'wordlift_cloud_has_analytics_consent', true );
		if ( ! $has_consent ) {
			return;
		}

		$token = self::get_project_token();
		if ( '' === $token ) {
			return;
		}

		$api_host = self::get_api_host();
		$context  = $this->build_admin_context( $hook_suffix );
		wp_add_inline_script( 'common', self::build_admin_loader_script( $token, $api_host, self::DEFAULTS_SNAPSHOT, $context ) );
	}

	/**
	 * Sanitize checkbox-like values.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return int
	 */
	public static function sanitize_enabled( $value ) {
		return ( 1 === (int) $value ) ? 1 : 0;
	}

	/**
	 * Sanitize project token.
	 *
	 * @param mixed $value Raw token.
	 *
	 * @return string
	 */
	public static function sanitize_project_token( $value ) {
		$token = trim( (string) $value );
		return preg_replace( '/[^A-Za-z0-9_\-]/', '', $token );
	}

	/**
	 * Sanitize API host URL.
	 *
	 * @param mixed $value Raw URL.
	 *
	 * @return string
	 */
	public static function sanitize_api_host( $value ) {
		$host = rtrim( trim( (string) $value ), '/' );
		if ( '' === $host ) {
			return self::DEFAULT_API_HOST;
		}

		$parts = parse_url( $host );
		if ( ! is_array( $parts ) || ! isset( $parts['scheme'] ) || ! isset( $parts['host'] ) ) {
			return self::DEFAULT_API_HOST;
		}

		if ( 'https' !== strtolower( (string) $parts['scheme'] ) ) {
			return self::DEFAULT_API_HOST;
		}

		return $host;
	}

	/**
	 * Return whether PostHog is enabled by settings.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return 1 === (int) get_option( self::OPTION_ENABLED, 0 );
	}

	/**
	 * Get sanitized project token from options.
	 *
	 * @return string
	 */
	public static function get_project_token() {
		$token = '';
		if ( defined( 'WORDLIFT_CLOUD_POSTHOG_PROJECT_TOKEN' ) ) {
			$token = (string) constant( 'WORDLIFT_CLOUD_POSTHOG_PROJECT_TOKEN' );
		}
		if ( '' === $token ) {
			$token = (string) get_option( self::OPTION_PROJECT_TOKEN, '' );
		}
		$token = (string) apply_filters( 'wordlift_cloud_posthog_project_token', $token );

		return self::sanitize_project_token( $token );
	}

	/**
	 * Get sanitized API host from options.
	 *
	 * @return string
	 */
	public static function get_api_host() {
		$api_host = '';
		if ( defined( 'WORDLIFT_CLOUD_POSTHOG_API_HOST' ) ) {
			$api_host = (string) constant( 'WORDLIFT_CLOUD_POSTHOG_API_HOST' );
		}
		if ( '' === $api_host ) {
			$api_host = (string) get_option( self::OPTION_API_HOST, self::DEFAULT_API_HOST );
		}
		$api_host = (string) apply_filters( 'wordlift_cloud_posthog_api_host', $api_host );

		return self::sanitize_api_host( $api_host );
	}

	/**
	 * Build PostHog loader + init script body.
	 *
	 * @param string $project_token Project token.
	 * @param string $api_host API host.
	 * @param string $defaults_snapshot Defaults snapshot date.
	 *
	 * @return string
	 */
	public static function build_loader_script( $project_token, $api_host, $defaults_snapshot ) {
		$token_json    = self::json_encode_safe( (string) $project_token );
		$config_json   = self::json_encode_safe(
			array(
				'api_host'        => (string) $api_host,
				'defaults'        => (string) $defaults_snapshot,
				'person_profiles' => self::PERSON_PROFILES,
			)
		);

		return "!function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split('.');2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement('script')).type='text/javascript',p.crossOrigin='anonymous',p.async=!0,p.src=s.api_host.replace('.i.posthog.com','-assets.i.posthog.com')+'/static/array.js',(r=t.getElementsByTagName('script')[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a='posthog',u.people=u.people||[],u.toString=function(t){var e='posthog';return'posthog'!==a&&(e+='.'+a),t||(e+=' (stub)'),e},u.people.toString=function(){return u.toString(1)+'.people (stub)'},o='init capture register register_once register_for_session unregister unregister_for_session getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey getNextSurveyStep identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty createPersonProfile opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing debug'.split(' '),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);posthog.init("
			. $token_json .
			',' . $config_json . ');';
	}

	/**
	 * Build admin script that initializes PostHog and captures admin feature usage events.
	 *
	 * @param string               $project_token Project token.
	 * @param string               $api_host API host.
	 * @param string               $defaults_snapshot Defaults snapshot date.
	 * @param array<string,mixed>  $context Admin request context.
	 *
	 * @return string
	 */
	public static function build_admin_loader_script( $project_token, $api_host, $defaults_snapshot, $context ) {
		$base_script  = self::build_loader_script( $project_token, $api_host, $defaults_snapshot );
		$context_json = self::json_encode_safe( $context );

		return $base_script . "(function(){var ctx=" . $context_json . ";if(!window.posthog){return;}if(ctx&&ctx.user_id){window.posthog.identify('wp-user-'+String(ctx.user_id),{wp_user_id:ctx.user_id,wp_area:'admin'});}if(ctx&&ctx.is_entity_types_screen){window.posthog.capture('wl_entity_type_term_management_used',{taxonomy:'wl_entity_type',hook:ctx.hook_suffix});}if(ctx&&ctx.is_settings_screen){window.posthog.capture('wl_cloud_settings_viewed',{hook:ctx.hook_suffix});if(ctx.settings_updated){window.posthog.capture('wl_cloud_settings_saved',{posthog_enabled:ctx.posthog_enabled,api_host:ctx.api_host_configured,hook:ctx.hook_suffix});}}})();";
	}

	/**
	 * Capture server-side admin event via PostHog capture endpoint.
	 *
	 * @param string               $event_name Event name.
	 * @param array<string,mixed>  $properties Event properties.
	 *
	 * @return bool True when the event request is dispatched.
	 */
	public static function capture_server_event( $event_name, $properties = array() ) {
		if ( ! is_admin() || ! is_user_logged_in() || ! self::is_enabled() ) {
			return false;
		}

		$token = self::get_project_token();
		if ( '' === $token ) {
			return false;
		}

		$user_id = (int) get_current_user_id();
		if ( $user_id < 1 ) {
			return false;
		}

		$payload = array(
			'api_key'     => $token,
			'event'       => (string) $event_name,
			'properties'  => array_merge(
				array(
					'distinct_id' => 'wp-user-' . $user_id,
					'wp_user_id'  => $user_id,
					'wp_area'     => 'admin',
				),
				(array) $properties
			),
		);

		$request = wp_remote_post(
			rtrim( self::get_api_host(), '/' ) . '/capture/',
			array(
				'headers'  => array( 'Content-Type' => 'application/json' ),
				'body'     => self::json_encode_safe( $payload ),
				'timeout'  => 2,
				'blocking' => false,
			)
		);

		return ! is_wp_error( $request );
	}

	/**
	 * Build request context used by admin-side event capture.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 *
	 * @return array<string,mixed>
	 */
	private function build_admin_context( $hook_suffix ) {
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( (string) $_GET['taxonomy'] ) : '';
		$page     = isset( $_GET['page'] ) ? sanitize_key( (string) $_GET['page'] ) : '';

		return array(
			'user_id'               => (int) get_current_user_id(),
			'hook_suffix'           => (string) $hook_suffix,
			'taxonomy'              => $taxonomy,
			'page'                  => $page,
			'is_entity_types_screen'=> ( 'edit-tags.php' === (string) $hook_suffix && 'wl_entity_type' === $taxonomy ),
			'is_settings_screen'    => ( 'options-general.php' === (string) $hook_suffix && self::SETTINGS_PAGE === $page ),
			'settings_updated'      => isset( $_GET['settings-updated'] ) && 'true' === (string) $_GET['settings-updated'],
				'posthog_enabled'       => self::is_enabled(),
				'api_host_configured'   => '' !== self::get_api_host(),
			);
		}

	/**
	 * JSON encode with WordPress helper fallback.
	 *
	 * @param mixed $value Value to encode.
	 *
	 * @return string
	 */
	private static function json_encode_safe( $value ) {
		if ( function_exists( 'wp_json_encode' ) ) {
			return (string) wp_json_encode( $value );
		}

		return (string) json_encode( $value );
	}
}
