<?php
/**
 * wl_entity_type taxonomy registration and lifecycle hooks.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers wl_entity_type and keeps it synchronized with distilled schema data.
 */
class Wordlift_Cloud_Entity_Type_Taxonomy implements Wordlift_Cloud_Hookable_Service, Wordlift_Cloud_Activatable_Service {

	/**
	 * Taxonomy slug.
	 */
	const TAXONOMY_NAME = 'wl_entity_type';

	/**
	 * @var Wordlift_Cloud_Entity_Type_Taxonomy_Installer
	 */
	private $installer;

	/**
	 * Configure dependencies.
	 */
	public function __construct() {
		$this->installer = new Wordlift_Cloud_Entity_Type_Taxonomy_Installer(
			self::TAXONOMY_NAME,
			plugin_dir_path( dirname( __FILE__ ) ) . 'resources/schemaorg/schema-classes.distilled.json'
		);
	}

	/**
	 * Wire WordPress hooks.
	 */
	public function register_hooks() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'init', array( $this, 'maybe_sync_terms' ), 20 );
		add_action( 'wp_head', array( $this, 'output_frontend_entity_type_meta' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'save_post', array( $this, 'capture_admin_selection_updated' ), 20, 3 );
	}

	/**
	 * Enqueue admin assets required by the Entity Types metabox.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( ! $this->should_enqueue_metabox_tabs_script( $hook_suffix ) ) {
			return;
		}

		$style = $this->get_metabox_header_style();
		if ( function_exists( 'wp_style_is' ) && wp_style_is( 'common', 'registered' ) ) {
			wp_add_inline_style( 'common', $style );
		}
		if ( function_exists( 'wp_style_is' ) && wp_style_is( 'wp-edit-blocks', 'registered' ) ) {
			wp_add_inline_style( 'wp-edit-blocks', $style );
		}

		$script = $this->get_metabox_tabs_script();
		if ( function_exists( 'wp_script_is' ) && wp_script_is( 'post', 'registered' ) ) {
			wp_add_inline_script( 'post', $script );
		}
		if ( function_exists( 'wp_script_is' ) && wp_script_is( 'wp-edit-post', 'registered' ) ) {
			wp_add_inline_script( 'wp-edit-post', $script );
		}
	}

	/**
	 * Activation callback ensures taxonomy and terms are available immediately.
	 */
	public function activate() {
		$this->register_taxonomy();
		$this->installer->maybe_sync( true );
		flush_rewrite_rules();
	}

	/**
	 * Register the wl_entity_type taxonomy.
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'              => _x( 'Entity Types', 'taxonomy general name', 'wordlift-cloud' ),
			'singular_name'     => _x( 'Entity Type', 'taxonomy singular name', 'wordlift-cloud' ),
			'search_items'      => __( 'Search Entity Types', 'wordlift-cloud' ),
			'all_items'         => __( 'All Entity Types', 'wordlift-cloud' ),
			'parent_item'       => __( 'Parent Entity Type', 'wordlift-cloud' ),
			'parent_item_colon' => __( 'Parent Entity Type:', 'wordlift-cloud' ),
			'edit_item'         => __( 'Edit Entity Type', 'wordlift-cloud' ),
			'update_item'       => __( 'Update Entity Type', 'wordlift-cloud' ),
			'add_new_item'      => __( 'Add New Entity Type', 'wordlift-cloud' ),
			'new_item_name'     => __( 'New Entity Type', 'wordlift-cloud' ),
			'menu_name'         => __( 'Entity Types', 'wordlift-cloud' ),
		);

		$args = array(
			'labels'             => $labels,
			'hierarchical'       => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'show_in_quick_edit' => true,
			'publicly_queryable' => false,
			'capabilities'       => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			),
			'meta_box_cb'        => array( 'Wordlift_Cloud_Schemaorg_Taxonomy_Metabox', 'render' ),
		);

		register_taxonomy(
			self::TAXONOMY_NAME,
			$this->get_supported_post_types(),
			$args
		);
	}

	/**
	 * Keep taxonomy terms in sync with the bundled schema data.
	 */
	public function maybe_sync_terms() {
		$this->installer->maybe_sync();
		if ( is_admin() && 'synced' === $this->installer->get_last_sync_status() ) {
			Wordlift_Cloud_Posthog_Integration::capture_server_event(
				'wl_entity_type_sync_run',
				array(
					'sync_status' => 'synced',
				)
			);
		}
	}

	/**
	 * Capture selection update event on post save from authenticated admin actions.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Whether this is an existing post update.
	 */
	public function capture_admin_selection_updated( $post_id, $post, $update ) {
		if ( ! is_admin() || ! is_user_logged_in() ) {
			return;
		}

		if ( wp_is_post_revision( (int) $post_id ) || wp_is_post_autosave( (int) $post_id ) ) {
			return;
		}

		if ( ! is_object( $post ) || ! isset( $post->post_type ) ) {
			return;
		}

		$taxonomy = get_taxonomy( self::TAXONOMY_NAME );
		if ( ! $taxonomy || ! is_array( $taxonomy->object_type ) || ! in_array( (string) $post->post_type, $taxonomy->object_type, true ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', (int) $post_id ) ) {
			return;
		}

		$terms = wp_get_post_terms( (int) $post_id, self::TAXONOMY_NAME );
		if ( is_wp_error( $terms ) ) {
			return;
		}

		$slugs = self::get_term_slugs( $terms );
		Wordlift_Cloud_Posthog_Integration::capture_server_event(
			'wl_entity_type_selection_updated',
			array(
				'source'        => 'post_save',
				'post_id'       => (int) $post_id,
				'post_type'     => (string) $post->post_type,
				'is_update'     => (bool) $update,
				'selected_count'=> count( $slugs ),
				'selected_slugs'=> $slugs,
			)
		);
	}

	/**
	 * Output the selected wl_entity_type as a frontend meta tag.
	 *
	 * Example output:
	 * <meta name="wl:entity_type" content="article" />
	 * <meta name="wl:entity_type" content="creative-work" />
	 */
	public function output_frontend_entity_type_meta() {
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id < 1 ) {
			return;
		}

		$terms = wp_get_post_terms( $post_id, self::TAXONOMY_NAME );
		if ( is_wp_error( $terms ) ) {
			return;
		}

		$slugs = self::get_term_slugs( $terms );
		if ( empty( $slugs ) ) {
			return;
		}

		foreach ( $slugs as $slug ) {
			echo self::build_entity_type_meta_tag( $slug ) . "\n";
		}
	}

	/**
	 * Pick valid, unique term slugs from assigned taxonomy terms.
	 *
	 * @param array<int,mixed> $terms A list of WP_Term-like values.
	 *
	 * @return array<int,string>
	 */
	public static function get_term_slugs( $terms ) {
		$slugs = array();

		foreach ( (array) $terms as $term ) {
			if ( is_object( $term ) && isset( $term->slug ) && is_string( $term->slug ) && '' !== $term->slug ) {
				$slugs[] = $term->slug;
			}

			if ( is_array( $term ) && isset( $term['slug'] ) && is_string( $term['slug'] ) && '' !== $term['slug'] ) {
				$slugs[] = $term['slug'];
			}
		}

		return array_values( array_unique( $slugs ) );
	}

	/**
	 * Build the HTML meta tag for the selected entity type slug.
	 *
	 * @param string $slug Entity type slug.
	 *
	 * @return string
	 */
	public static function build_entity_type_meta_tag( $slug ) {
		return sprintf(
			'<meta name="wl:entity_type" content="%s" />',
			esc_attr( $slug )
		);
	}

	/**
	 * Supported object types for wl_entity_type.
	 *
	 * @return array<int,string>
	 */
	private function get_supported_post_types() {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'names'
		);
		unset( $post_types['attachment'] );

		return array_values( $post_types );
	}

	/**
	 * Determine if metabox tab fallback script should run on the current admin page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 *
	 * @return bool
	 */
	private function should_enqueue_metabox_tabs_script( $hook_suffix ) {
		return in_array( (string) $hook_suffix, array( 'post.php', 'post-new.php' ), true );
	}

	/**
	 * Inline fallback for metabox tab behavior in Classic Editor.
	 *
	 * @return string
	 */
	public function get_metabox_tabs_script() {
		$icon_url = plugins_url( 'resources/images/wordlift-favicon.ico', dirname( __FILE__ ) . '/../wordlift-cloud.php' );

		$script = <<<'JS'
jQuery( function ( $ ) {
	var metaboxIconUrl = __WL_ICON_URL__;

	function ensureBlockEditorBrandedHeader() {
		var brandedAny = false;
		$( '.interface-interface-skeleton__sidebar button, .editor-sidebar button' ).each( function () {
			var $button = $( this );
			if ( $.trim( $button.text() ) !== 'Entity Types' ) {
				return;
			}
			if ( $button.find( '.wl-entity-type-icon-block' ).length ) {
				brandedAny = true;
				return;
			}
			$button.css( {
				'justify-content': 'flex-start',
				'text-align': 'left'
			} );
			$button.prepend(
				'<img class="wl-entity-type-icon wl-entity-type-icon-block" src="' + metaboxIconUrl + '" alt="" width="16" height="16" ' +
				'style="display:inline-block;vertical-align:text-bottom;margin-right:6px;flex:0 0 auto;" />'
			);
			brandedAny = true;
		} );
		return brandedAny;
	}

	var blockBrandingAttempts = 0;
	var blockBrandingTimer = window.setInterval( function () {
		blockBrandingAttempts += 1;
		if ( ensureBlockEditorBrandedHeader() || blockBrandingAttempts > 20 ) {
			window.clearInterval( blockBrandingTimer );
		}
	}, 250 );
	ensureBlockEditorBrandedHeader();

	var $metabox = $( '#taxonomy-wl_entity_type.categorydiv' );
	if ( ! $metabox.length ) {
		return;
	}

	var selectedListSelector = '#wl_entity_type-selected-list';
	var selectedCountSelector = '#wl_entity_type-tabs .wl-selected-count';
	var checkboxSelector = 'input[type="checkbox"][name="tax_input[wl_entity_type][]"]';
	var postId = parseInt( $( '#post_ID' ).val(), 10 ) || 0;

	function ensureBrandedHeader() {
		var $title = $metabox.closest( '.postbox' ).find( '> .postbox-header .hndle, > .hndle' ).first();
		if ( ! $title.length || $title.find( '.wl-entity-type-icon' ).length ) {
			return;
		}
		$title.css( {
			'text-align': 'left',
			'justify-content': 'flex-start'
		} );
		$title.prepend(
			'<img class="wl-entity-type-icon" src="' + metaboxIconUrl + '" alt="" width="16" height="16" ' +
			'style="display:inline-block;vertical-align:text-bottom;margin-right:6px;" />'
		);
	}

	function captureEvent( eventName, properties ) {
		if ( ! window.posthog || 'function' !== typeof window.posthog.capture ) {
			return;
		}
		window.posthog.capture( eventName, properties || {} );
	}

	function getLabelForValue( value ) {
		var $source = $metabox.find( checkboxSelector + '[value="' + value + '"]' ).first().closest( 'label' );
		if ( ! $source.length ) {
			return '';
		}
		return $.trim( $source.text().replace( /\s+/g, ' ' ) );
	}

	function refreshSelectedList() {
		var selectedByValue = {};
		var values = [];

		$metabox.find( checkboxSelector + ':checked' ).each( function () {
			var value = $( this ).val();
			if ( selectedByValue[ value ] ) {
				return;
			}
			selectedByValue[ value ] = true;
			values.push( value );
		} );

		values.sort( function ( a, b ) {
			var aLabel = getLabelForValue( a ).toLowerCase();
			var bLabel = getLabelForValue( b ).toLowerCase();
			if ( aLabel < bLabel ) {
				return -1;
			}
			if ( aLabel > bLabel ) {
				return 1;
			}
			return 0;
		} );

		var items = [];
		if ( values.length < 1 ) {
			items.push( '<li class="wl-no-selected-entity-types">No Entity Types selected yet.</li>' );
		} else {
			values.forEach( function ( value ) {
				var label = getLabelForValue( value );
				var escapedLabel = $( '<div/>' ).text( label ).html();
				var escapedValue = $( '<div/>' ).text( String( value ) ).html();
				items.push(
					'<li data-term-id="' + escapedValue + '">' +
						'<label class="selectit">' +
							'<input value="' + escapedValue + '" type="checkbox" name="tax_input[wl_entity_type][]" id="in-wl_entity_type-selected-' + escapedValue + '" checked="checked" /> ' +
							escapedLabel +
						'</label>' +
					'</li>'
				);
			} );
		}

		$metabox.find( selectedListSelector ).html( items.join( '' ) );
		$metabox.find( selectedCountSelector ).text( String( values.length ) );
	}

	$metabox.on( 'change.wordliftCloudSync', checkboxSelector, function () {
		var value = $( this ).val();
		var checked = $( this ).is( ':checked' );
		$metabox.find( checkboxSelector + '[value="' + value + '"]' ).prop( 'checked', checked );
		refreshSelectedList();
		captureEvent( 'wl_entity_type_term_toggled', {
			source: 'metabox_checkbox',
			post_id: postId,
			term_id: value,
			checked: checked
		} );
	} );

	$metabox.find( '#wl_entity_type-tabs a' ).on( 'click.wordliftCloudTabs', function ( event ) {
		var target = this.getAttribute( 'href' );
		if ( ! target || target.charAt( 0 ) !== '#' ) {
			return;
		}

		event.preventDefault();
		$metabox.find( '#wl_entity_type-tabs li' ).removeClass( 'tabs' );
		$( this ).parent().addClass( 'tabs' );
		$metabox.find( '> .tabs-panel' ).hide();
		$metabox.find( target ).show();
		captureEvent( 'wl_entity_type_tab_switched', {
			source: 'metabox_tab',
			post_id: postId,
			tab: target.replace( '#wl_entity_type-', '' )
		} );
	} );

	$( '#post' ).on( 'submit.wordliftCloudSelection', function () {
		var selectedValues = [];
		$metabox.find( checkboxSelector + ':checked' ).each( function () {
			var value = String( $( this ).val() );
			if ( selectedValues.indexOf( value ) < 0 ) {
				selectedValues.push( value );
			}
		} );
		captureEvent( 'wl_entity_type_selection_updated', {
			source: 'editor_submit',
			post_id: postId,
			selected_count: selectedValues.length,
			selected_term_ids: selectedValues
		} );
	} );

	refreshSelectedList();
	ensureBrandedHeader();
	captureEvent( 'wl_entity_type_metabox_viewed', {
		source: 'metabox_load',
		post_id: postId
	} );
} );
JS;

		return str_replace(
			'__WL_ICON_URL__',
			(string) wp_json_encode( (string) $icon_url ),
			$script
		);
	}

	/**
	 * Inline CSS for branding/alignment of the Entity Types metabox header.
	 *
	 * @return string
	 */
	private function get_metabox_header_style() {
		return '#taxonomy-wl_entity_type.postbox > .postbox-header .hndle, #taxonomy-wl_entity_type.postbox > .hndle{justify-content:flex-start;text-align:left;}#taxonomy-wl_entity_type.postbox .wl-entity-type-icon{margin-right:6px;}';
	}
}
