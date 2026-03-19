<?php
/**
 * Frontend WordLift Cloud bootstrap script integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects the WordLift Cloud bootstrap script on frontend pages.
 */
class Wordlift_Cloud_Frontend_Bootstrap_Script implements Wordlift_Cloud_Hookable_Service {
	/**
	 * Option key enabling FAQ rendering.
	 */
	const OPTION_ENABLED = 'wordlift_cloud_frontend_enabled';
	const OPTION_FAQ_TARGET_ID = 'wordlift_cloud_faq_target_id';
	const OPTION_FAQ_TEMPLATE  = 'wordlift_cloud_faq_template';
	const POST_META_FAQ_OVERRIDE = '_wordlift_cloud_faq_override';
	const FAQ_TEMPLATE_ID = 'wl-faq-template';
	const FAQ_OVERRIDE_INHERIT = 'inherit';
	const FAQ_OVERRIDE_ENABLE  = 'enable';
	const FAQ_OVERRIDE_DISABLE = 'disable';

	/**
	 * Register frontend hook.
	 */
	public function register_hooks() {
		add_action( 'wp_head', array( $this, 'inject_script' ) );
		add_action( 'wp_head', array( $this, 'render_faq_template_script' ), 21 );
		add_action( 'wp_footer', array( $this, 'render_missing_target_notice_script' ), 99 );
		add_action( 'add_meta_boxes', array( $this, 'register_faq_metaboxes' ) );
		add_action( 'save_post', array( $this, 'save_faq_override' ), 20, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Output WordLift Cloud bootstrap script if consent is granted.
	 */
	public function inject_script() {
		if ( is_admin() ) {
			return;
		}

		$has_consent = (bool) apply_filters( 'wordlift_cloud_has_bootstrap_consent', true );
		if ( ! $has_consent ) {
			return;
		}

		$attributes = array(
			'async type="text/javascript" src="https://cloud.wordlift.io/app/bootstrap.js"',
		);

		if ( $this->is_faq_effectively_enabled() ) {
			$target_id = self::get_faq_target_id();
			if ( '' !== $target_id ) {
				$attributes[] = 'data-faq="true"';
				$attributes[] = 'data-faq-target-id="' . esc_attr( $target_id ) . '"';
				$attributes[] = 'data-faq-template-id="' . esc_attr( self::FAQ_TEMPLATE_ID ) . '"';
			}
		}

		echo '<script ' . implode( ' ', $attributes ) . '></script>' . "\n";
	}

	/**
	 * Render FAQ template script when FAQ rendering is enabled.
	 */
	public function render_faq_template_script() {
		if ( is_admin() || ! is_singular() || ! $this->is_faq_effectively_enabled() ) {
			return;
		}

		$template = self::get_faq_template();
		if ( '' === trim( $template ) ) {
			return;
		}

		echo '<script type="text/template" id="' . esc_attr( self::FAQ_TEMPLATE_ID ) . '" hidden>' . "\n";
		echo $template . "\n";
		echo '</script>' . "\n";
	}

	/**
	 * Show a frontend admin notice when FAQ rendering is enabled but target is missing.
	 */
	public function render_missing_target_notice_script() {
		if ( is_admin() || ! is_singular() || ! $this->is_faq_effectively_enabled() ) {
			return;
		}

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$target_id = self::get_faq_target_id();
		if ( '' === $target_id ) {
			return;
		}

		$target_json = self::json_encode_safe( $target_id );
		$message_json = self::json_encode_safe( 'WordLift Cloud FAQ rendering is enabled, but the target element #' . $target_id . ' was not found on this page.' );

		echo '<script>(function(){var targetId=' . $target_json . ';var message=' . $message_json . ';function renderNotice(){if(document.getElementById(targetId)){return;}var notice=document.createElement("div");notice.setAttribute("role","status");notice.style.cssText="position:fixed;top:0;left:0;right:0;z-index:2147483647;padding:12px 16px;background:#fef3c7;color:#7c2d12;border-bottom:1px solid #f59e0b;font:600 14px/1.4 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;";notice.textContent=message;document.body.insertBefore(notice,document.body.firstChild);}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",renderNotice);}else{renderNotice();}})();</script>' . "\n";
	}

	/**
	 * Register FAQ metabox for all public post types.
	 */
	public function register_faq_metaboxes() {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'names'
		);

		foreach ( (array) $post_types as $post_type ) {
			add_meta_box(
				'wordlift-cloud-faq-rendering',
				__( 'FAQ', 'wordlift-cloud' ),
				array( $this, 'render_faq_metabox' ),
				(string) $post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render FAQ override metabox UI.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_faq_metabox( $post ) {
		$post_id  = is_object( $post ) && isset( $post->ID ) ? (int) $post->ID : 0;
		$override = self::get_post_override( $post_id );

		wp_nonce_field( 'wordlift_cloud_faq_override', 'wordlift_cloud_faq_override_nonce' );
		?>
		<div class="wordlift-cloud-faq-metabox">
			<p>
				<strong><?php echo esc_html__( 'FAQ', 'wordlift-cloud' ); ?></strong>
			</p>
			<p>
				<label for="wordlift_cloud_faq_override"><?php echo esc_html__( 'Post override', 'wordlift-cloud' ); ?></label><br />
				<select id="wordlift_cloud_faq_override" name="wordlift_cloud_faq_override">
					<option value="<?php echo esc_attr( self::FAQ_OVERRIDE_INHERIT ); ?>" <?php selected( self::FAQ_OVERRIDE_INHERIT, $override ); ?>><?php echo esc_html__( 'Inherit (default)', 'wordlift-cloud' ); ?></option>
					<option value="<?php echo esc_attr( self::FAQ_OVERRIDE_ENABLE ); ?>" <?php selected( self::FAQ_OVERRIDE_ENABLE, $override ); ?>><?php echo esc_html__( 'Enable', 'wordlift-cloud' ); ?></option>
					<option value="<?php echo esc_attr( self::FAQ_OVERRIDE_DISABLE ); ?>" <?php selected( self::FAQ_OVERRIDE_DISABLE, $override ); ?>><?php echo esc_html__( 'Disable', 'wordlift-cloud' ); ?></option>
				</select>
			</p>
			<p class="description"><?php echo esc_html__( 'Use this post-level override to control FAQ rendering for this content only.', 'wordlift-cloud' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Save FAQ override metabox value.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function save_faq_override( $post_id, $post ) {
		if ( wp_is_post_revision( (int) $post_id ) || wp_is_post_autosave( (int) $post_id ) ) {
			return;
		}

		if ( ! is_object( $post ) || ! isset( $post->post_type ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', (int) $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST['wordlift_cloud_faq_override_nonce'] ) ? (string) wp_unslash( $_POST['wordlift_cloud_faq_override_nonce'] ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'wordlift_cloud_faq_override' ) ) {
			return;
		}

		$raw_value = isset( $_POST['wordlift_cloud_faq_override'] ) ? (string) wp_unslash( $_POST['wordlift_cloud_faq_override'] ) : self::FAQ_OVERRIDE_INHERIT;
		$value     = self::sanitize_faq_override( $raw_value );
		if ( self::FAQ_OVERRIDE_INHERIT === $value ) {
			delete_post_meta( (int) $post_id, self::POST_META_FAQ_OVERRIDE );
			return;
		}

		update_post_meta( (int) $post_id, self::POST_META_FAQ_OVERRIDE, $value );
	}

	/**
	 * Add metabox branding style in editors.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( ! in_array( (string) $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$style = '#wordlift-cloud-faq-rendering.postbox > .postbox-header .hndle,#wordlift-cloud-faq-rendering.postbox > .hndle{justify-content:flex-start;text-align:left;}';
		if ( function_exists( 'wp_style_is' ) && wp_style_is( 'common', 'registered' ) ) {
			wp_add_inline_style( 'common', $style );
		}
		if ( function_exists( 'wp_style_is' ) && wp_style_is( 'wp-edit-blocks', 'registered' ) ) {
			wp_add_inline_style( 'wp-edit-blocks', $style );
		}

		$script = $this->get_faq_metabox_branding_script();
		if ( function_exists( 'wp_script_is' ) && wp_script_is( 'post', 'registered' ) ) {
			wp_add_inline_script( 'post', $script );
		}
		if ( function_exists( 'wp_script_is' ) && wp_script_is( 'wp-edit-post', 'registered' ) ) {
			wp_add_inline_script( 'wp-edit-post', $script );
		}
	}

	/**
	 * Resolve if FAQ rendering is enabled for the current request.
	 *
	 * @return bool
	 */
	public function is_faq_effectively_enabled() {
		$global_enabled = self::is_enabled();
		if ( ! is_singular() ) {
			return $global_enabled;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id < 1 ) {
			return $global_enabled;
		}

		$override = self::get_post_override( $post_id );
		if ( self::FAQ_OVERRIDE_ENABLE === $override ) {
			return true;
		}

		if ( self::FAQ_OVERRIDE_DISABLE === $override ) {
			return false;
		}

		return $global_enabled;
	}

	/**
	 * Return whether FAQ rendering is globally enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return 1 === (int) get_option( self::OPTION_ENABLED, 0 );
	}

	/**
	 * Get FAQ target element ID.
	 *
	 * @return string
	 */
	public static function get_faq_target_id() {
		$value = (string) get_option( self::OPTION_FAQ_TARGET_ID, 'faq-container' );
		return self::sanitize_faq_target_id( $value );
	}

	/**
	 * Get FAQ template string.
	 *
	 * @return string
	 */
	public static function get_faq_template() {
		$template = (string) get_option( self::OPTION_FAQ_TEMPLATE, self::default_faq_template() );
		$sanitized = self::sanitize_faq_template( $template );
		if ( '' === trim( $sanitized ) ) {
			return self::default_faq_template();
		}
		return $sanitized;
	}

	/**
	 * Sanitize target element ID.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function sanitize_faq_target_id( $value ) {
		$id = trim( (string) $value );
		$id = preg_replace( '/[^A-Za-z0-9\-\_:]/', '', $id );
		return (string) $id;
	}

	/**
	 * Sanitize FAQ template.
	 *
	 * @param mixed $value Raw template.
	 *
	 * @return string
	 */
	public static function sanitize_faq_template( $value ) {
		$template = trim( (string) $value );
		if ( '' === $template ) {
			return self::default_faq_template();
		}

		return wp_kses_post( $template );
	}

	/**
	 * Sanitize FAQ override.
	 *
	 * @param mixed $value Raw override value.
	 *
	 * @return string
	 */
	public static function sanitize_faq_override( $value ) {
		$normalized = sanitize_key( (string) $value );
		$allowed    = array(
			self::FAQ_OVERRIDE_INHERIT,
			self::FAQ_OVERRIDE_ENABLE,
			self::FAQ_OVERRIDE_DISABLE,
		);

		if ( in_array( $normalized, $allowed, true ) ) {
			return $normalized;
		}

		return self::FAQ_OVERRIDE_INHERIT;
	}

	/**
	 * Read sanitized post-level override.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	public static function get_post_override( $post_id ) {
		if ( $post_id < 1 ) {
			return self::FAQ_OVERRIDE_INHERIT;
		}

		$value = get_post_meta( (int) $post_id, self::POST_META_FAQ_OVERRIDE, true );
		return self::sanitize_faq_override( $value );
	}

	/**
	 * Default FAQ template.
	 *
	 * @return string
	 */
	public static function default_faq_template() {
		return '<ul class="faq-list">
  {{#faqs}}
    <li class="faq-item">
      <strong class="faq-question">{{question}}</strong>
      <div class="faq-answer">{{{answer}}}</div>
    </li>
  {{/faqs}}
</ul>';
	}

	/**
	 * JSON encode helper with WordPress fallback.
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

	/**
	 * Build editor branding script for FAQ metabox header and block editor panel.
	 *
	 * @return string
	 */
	private function get_faq_metabox_branding_script() {
		$icon_url = plugins_url( 'resources/images/wordlift-favicon.ico', dirname( __FILE__ ) . '/../wordlift-cloud.php' );

		$script = <<<'JS'
jQuery(function($){
	var metaboxIconUrl = __WL_ICON_URL__;

	function addIconToClassicHeader() {
		var $title = $('#wordlift-cloud-faq-rendering.postbox > .postbox-header .hndle, #wordlift-cloud-faq-rendering.postbox > .hndle').first();
		if (!$title.length || $title.find('.wl-faq-icon').length) {
			return;
		}
		$title.prepend('<img class="wl-faq-icon" src="' + metaboxIconUrl + '" alt="" width="16" height="16" style="display:inline-block;vertical-align:text-bottom;margin-right:6px;" />');
	}

	function addIconToBlockPanel() {
		var branded = false;
		$('.interface-interface-skeleton__sidebar button, .editor-sidebar button').each(function(){
			var $button = $(this);
			if ($.trim($button.text()) !== 'FAQ') {
				return;
			}
			if ($button.find('.wl-faq-icon-block').length) {
				branded = true;
				return;
			}
			$button.css({'justify-content':'flex-start','text-align':'left'});
			$button.prepend('<img class="wl-faq-icon wl-faq-icon-block" src="' + metaboxIconUrl + '" alt="" width="16" height="16" style="display:inline-block;vertical-align:text-bottom;margin-right:6px;flex:0 0 auto;" />');
			branded = true;
		});
		return branded;
	}

	var tries = 0;
	var timer = window.setInterval(function(){
		tries += 1;
		addIconToClassicHeader();
		if (addIconToBlockPanel() || tries > 20) {
			window.clearInterval(timer);
		}
	}, 250);

	addIconToClassicHeader();
	addIconToBlockPanel();
});
JS;

		return str_replace(
			'__WL_ICON_URL__',
			(string) self::json_encode_safe( (string) $icon_url ),
			$script
		);
	}
}
