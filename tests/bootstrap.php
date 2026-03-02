<?php
declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
	}
}

if ( ! class_exists( 'WP_Term' ) ) {
	class WP_Term {
		/** @var int */
		public $term_id;
		/** @var string */
		public $name;
		/** @var string */
		public $slug;

		public function __construct( $term_id = 0, $name = '', $slug = '' ) {
			$this->term_id = (int) $term_id;
			$this->name    = (string) $name;
			$this->slug    = (string) $slug;
		}
	}
}

if ( ! class_exists( 'Walker_Category_Checklist' ) ) {
	class Walker_Category_Checklist {
		public function walk( $elements, $max_depth, ...$args ) {
			return '<li><label><input type="checkbox" /></label></li>';
		}
	}
}

function wordlift_test_reset_wp_state() {
	$GLOBALS['wl_test_actions']             = array();
	$GLOBALS['wl_test_filters']             = array();
	$GLOBALS['wl_test_options']             = array();
	$GLOBALS['wl_test_inline_scripts']      = array();
	$GLOBALS['wl_test_inline_styles']       = array();
	$GLOBALS['wl_test_registered_scripts']  = array(
		'post'         => true,
		'wp-edit-post' => false,
	);
	$GLOBALS['wl_test_registered_styles']   = array(
		'common'         => true,
		'wp-edit-blocks' => false,
	);
	$GLOBALS['wl_test_remote_posts']        = array();
	$GLOBALS['wl_test_remote_post_result']  = array( 'ok' => true );
	$GLOBALS['wl_test_registered_taxonomy'] = array();
	$GLOBALS['wl_test_terms_by_slug']       = array();
	$GLOBALS['wl_test_term_updates']        = array();
	$GLOBALS['wl_test_term_inserts']        = array();
	$GLOBALS['wl_test_term_deletes']        = array();
	$GLOBALS['wl_test_term_meta']           = array();
	$GLOBALS['wl_test_is_admin']            = false;
	$GLOBALS['wl_test_is_user_logged_in']   = false;
	$GLOBALS['wl_test_user_id']             = 0;
	$GLOBALS['wl_test_current_user_can']    = true;
	$GLOBALS['wl_test_taxonomies']          = array();
	$GLOBALS['wl_test_post_terms']          = array();
	$GLOBALS['wl_test_post_types']          = array(
		'post'       => 'post',
		'page'       => 'page',
		'attachment' => 'attachment',
	);
	$GLOBALS['wl_test_is_singular']         = false;
	$GLOBALS['wl_test_queried_object_id']   = 0;
	$GLOBALS['wl_test_revision_post_ids']   = array();
	$GLOBALS['wl_test_autosave_post_ids']   = array();
	$GLOBALS['wl_test_flush_rewrite_rules'] = 0;
	$GLOBALS['wl_test_next_term_id']        = 100;
	$GLOBALS['wl_test_terms_list']          = array();
	$GLOBALS['wl_test_popular_terms']       = array( 10, 20 );
	$GLOBALS['wl_test_terms_checklist']     = '';
	$GLOBALS['wl_test_added_options_pages'] = array();
	$GLOBALS['wl_test_registered_settings'] = array();
	$GLOBALS['wl_test_settings_sections']   = array();
	$GLOBALS['wl_test_settings_fields']     = array();
}

wordlift_test_reset_wp_state();

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = null ) {
		return (string) $text;
	}
}

if ( ! function_exists( 'esc_html_x' ) ) {
	function esc_html_x( $text, $context, $domain = null ) {
		return (string) $text;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return (string) $text;
	}
}

if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = null ) {
		return (string) $text;
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return rtrim( dirname( (string) $file ), '/\\' ) . '/';
	}
}

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return basename( (string) $file );
	}
}

if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( $path = '', $plugin = '' ) {
		$base = 'https://example.test/wp-content/plugins/wordlift-cloud-plugin/';
		return $base . ltrim( (string) $path, '/' );
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['wl_test_actions'][] = array(
			'hook'          => (string) $hook,
			'callback'      => $callback,
			'priority'      => (int) $priority,
			'accepted_args' => (int) $accepted_args,
		);
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['wl_test_filters'][] = array(
			'hook'          => (string) $hook,
			'callback'      => $callback,
			'priority'      => (int) $priority,
			'accepted_args' => (int) $accepted_args,
		);
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) {
		$args  = func_get_args();
		$value = $args[1];
		foreach ( $GLOBALS['wl_test_filters'] as $filter ) {
			if ( $filter['hook'] !== (string) $hook ) {
				continue;
			}
			$callback = $filter['callback'];
			$value    = $callback( $value );
		}
		return $value;
	}
}

if ( ! function_exists( 'register_setting' ) ) {
	function register_setting( $group, $name, $args = array() ) {
		$GLOBALS['wl_test_registered_settings'][] = compact( 'group', 'name', 'args' );
	}
}

if ( ! function_exists( 'add_settings_section' ) ) {
	function add_settings_section( $id, $title, $callback, $page ) {
		$GLOBALS['wl_test_settings_sections'][] = compact( 'id', 'title', 'callback', 'page' );
	}
}

if ( ! function_exists( 'add_settings_field' ) ) {
	function add_settings_field( $id, $title, $callback, $page, $section ) {
		$GLOBALS['wl_test_settings_fields'][] = compact( 'id', 'title', 'callback', 'page', 'section' );
	}
}

if ( ! function_exists( 'add_options_page' ) ) {
	function add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback ) {
		$GLOBALS['wl_test_added_options_pages'][] = compact( 'page_title', 'menu_title', 'capability', 'menu_slug', 'callback' );
		return 'options-general.php?page=' . (string) $menu_slug;
	}
}

if ( ! function_exists( 'settings_fields' ) ) {
	function settings_fields( $group ) {
		echo '<input type="hidden" name="option_page" value="' . esc_attr( (string) $group ) . '" />';
	}
}

if ( ! function_exists( 'do_settings_sections' ) ) {
	function do_settings_sections( $page ) {
		echo '<div data-settings-page="' . esc_attr( (string) $page ) . '"></div>';
	}
}

if ( ! function_exists( 'submit_button' ) ) {
	function submit_button() {
		echo '<button type="submit">Save Changes</button>';
	}
}

if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $echo = true ) {
		$output = ( $checked == $current ) ? 'checked="checked"' : '';
		if ( $echo ) {
			echo $output;
		}
		return $output;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return (bool) $GLOBALS['wl_test_is_admin'];
	}
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in() {
		return (bool) $GLOBALS['wl_test_is_user_logged_in'];
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		if ( array_key_exists( (string) $name, $GLOBALS['wl_test_options'] ) ) {
			return $GLOBALS['wl_test_options'][ (string) $name ];
		}
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $name, $value, $autoload = null ) {
		$GLOBALS['wl_test_options'][ (string) $name ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_add_inline_script' ) ) {
	function wp_add_inline_script( $handle, $data, $position = 'after' ) {
		$GLOBALS['wl_test_inline_scripts'][] = array(
			'handle'   => (string) $handle,
			'data'     => (string) $data,
			'position' => (string) $position,
		);
		return true;
	}
}

if ( ! function_exists( 'wp_add_inline_style' ) ) {
	function wp_add_inline_style( $handle, $data ) {
		$GLOBALS['wl_test_inline_styles'][] = array(
			'handle' => (string) $handle,
			'data'   => (string) $data,
		);
		return true;
	}
}

if ( ! function_exists( 'wp_script_is' ) ) {
	function wp_script_is( $handle, $status = 'enqueued' ) {
		return ! empty( $GLOBALS['wl_test_registered_scripts'][ (string) $handle ] );
	}
}

if ( ! function_exists( 'wp_style_is' ) ) {
	function wp_style_is( $handle, $status = 'enqueued' ) {
		return ! empty( $GLOBALS['wl_test_registered_styles'][ (string) $handle ] );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return (int) $GLOBALS['wl_test_user_id'];
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value ) {
		return json_encode( $value );
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args = array() ) {
		$GLOBALS['wl_test_remote_posts'][] = array(
			'url'  => (string) $url,
			'args' => $args,
		);
		return $GLOBALS['wl_test_remote_post_result'];
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		return (bool) $GLOBALS['wl_test_current_user_can'];
	}
}

if ( ! function_exists( 'get_taxonomy' ) ) {
	function get_taxonomy( $taxonomy ) {
		$name = (string) $taxonomy;
		return isset( $GLOBALS['wl_test_taxonomies'][ $name ] ) ? $GLOBALS['wl_test_taxonomies'][ $name ] : null;
	}
}

if ( ! function_exists( 'wp_get_post_terms' ) ) {
	function wp_get_post_terms( $post_id, $taxonomy ) {
		$key = (int) $post_id . '|' . (string) $taxonomy;
		return isset( $GLOBALS['wl_test_post_terms'][ $key ] ) ? $GLOBALS['wl_test_post_terms'][ $key ] : array();
	}
}

if ( ! function_exists( 'wp_is_post_revision' ) ) {
	function wp_is_post_revision( $post_id ) {
		return in_array( (int) $post_id, $GLOBALS['wl_test_revision_post_ids'], true );
	}
}

if ( ! function_exists( 'wp_is_post_autosave' ) ) {
	function wp_is_post_autosave( $post_id ) {
		return in_array( (int) $post_id, $GLOBALS['wl_test_autosave_post_ids'], true );
	}
}

if ( ! function_exists( 'is_singular' ) ) {
	function is_singular() {
		return (bool) $GLOBALS['wl_test_is_singular'];
	}
}

if ( ! function_exists( 'get_queried_object_id' ) ) {
	function get_queried_object_id() {
		return (int) $GLOBALS['wl_test_queried_object_id'];
	}
}

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( $args = array(), $output = 'names' ) {
		return $GLOBALS['wl_test_post_types'];
	}
}

if ( ! function_exists( 'register_taxonomy' ) ) {
	function register_taxonomy( $taxonomy, $object_type, $args = array() ) {
		$GLOBALS['wl_test_registered_taxonomy'][] = array(
			'taxonomy'    => (string) $taxonomy,
			'object_type' => (array) $object_type,
			'args'        => (array) $args,
		);
		return true;
	}
}

if ( ! function_exists( 'flush_rewrite_rules' ) ) {
	function flush_rewrite_rules( $hard = true ) {
		++$GLOBALS['wl_test_flush_rewrite_rules'];
	}
}

if ( ! function_exists( 'term_exists' ) ) {
	function term_exists( $term, $taxonomy = '', $parent_term = null ) {
		$slug = (string) $term;
		return isset( $GLOBALS['wl_test_terms_by_slug'][ $slug ] ) ? $GLOBALS['wl_test_terms_by_slug'][ $slug ] : null;
	}
}

if ( ! function_exists( 'wp_update_term' ) ) {
	function wp_update_term( $term_id, $taxonomy, $args = array() ) {
		$GLOBALS['wl_test_term_updates'][] = array(
			'term_id'  => (int) $term_id,
			'taxonomy' => (string) $taxonomy,
			'args'     => (array) $args,
		);
		return array( 'term_id' => (int) $term_id );
	}
}

if ( ! function_exists( 'wp_insert_term' ) ) {
	function wp_insert_term( $term, $taxonomy, $args = array() ) {
		if ( isset( $args['force_error'] ) && $args['force_error'] ) {
			return new WP_Error();
		}
		$term_id = (int) $GLOBALS['wl_test_next_term_id'];
		++$GLOBALS['wl_test_next_term_id'];
		$slug = isset( $args['slug'] ) ? (string) $args['slug'] : sanitize_key( (string) $term );
		$GLOBALS['wl_test_terms_by_slug'][ $slug ] = array( 'term_id' => $term_id );
		$GLOBALS['wl_test_term_inserts'][] = array(
			'term'     => (string) $term,
			'taxonomy' => (string) $taxonomy,
			'args'     => (array) $args,
			'term_id'  => $term_id,
		);
		return array( 'term_id' => $term_id );
	}
}

if ( ! function_exists( 'update_term_meta' ) ) {
	function update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
		$GLOBALS['wl_test_term_meta'][ (int) $term_id ][ (string) $meta_key ] = (array) array( $meta_value );
		return true;
	}
}

if ( ! function_exists( 'delete_term_meta' ) ) {
	function delete_term_meta( $term_id, $meta_key, $meta_value = '' ) {
		unset( $GLOBALS['wl_test_term_meta'][ (int) $term_id ][ (string) $meta_key ] );
		return true;
	}
}

if ( ! function_exists( 'add_term_meta' ) ) {
	function add_term_meta( $term_id, $meta_key, $meta_value, $unique = false ) {
		if ( ! isset( $GLOBALS['wl_test_term_meta'][ (int) $term_id ][ (string) $meta_key ] ) ) {
			$GLOBALS['wl_test_term_meta'][ (int) $term_id ][ (string) $meta_key ] = array();
		}
		$GLOBALS['wl_test_term_meta'][ (int) $term_id ][ (string) $meta_key ][] = $meta_value;
		return true;
	}
}

if ( ! function_exists( 'wp_delete_term' ) ) {
	function wp_delete_term( $term_id, $taxonomy, $args = array() ) {
		$GLOBALS['wl_test_term_deletes'][] = array(
			'term_id'  => (int) $term_id,
			'taxonomy' => (string) $taxonomy,
		);
		return true;
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		return array_merge( (array) $defaults, (array) $args );
	}
}

if ( ! function_exists( 'wp_popular_terms_checklist' ) ) {
	function wp_popular_terms_checklist( $taxonomy, $default = 0, $number = 10, $echo = true ) {
		if ( ! $echo ) {
			return $GLOBALS['wl_test_popular_terms'];
		}
		echo '<li>Popular Terms</li>';
		return null;
	}
}

if ( ! function_exists( 'wp_terms_checklist' ) ) {
	function wp_terms_checklist( $post_id, $args = array() ) {
		echo (string) $GLOBALS['wl_test_terms_checklist'];
	}
}

if ( ! function_exists( 'get_terms' ) ) {
	function get_terms( $args = array() ) {
		return $GLOBALS['wl_test_terms_list'];
	}
}

if ( ! function_exists( 'wp_get_object_terms' ) ) {
	function wp_get_object_terms( $object_id, $taxonomies, $args = array() ) {
		$key = (int) $object_id . '|' . (string) $taxonomies;
		$terms = isset( $GLOBALS['wl_test_post_terms'][ $key ] ) ? $GLOBALS['wl_test_post_terms'][ $key ] : array();
		if ( isset( $args['fields'] ) && 'ids' === $args['fields'] ) {
			$ids = array();
			foreach ( (array) $terms as $term ) {
				if ( is_object( $term ) && isset( $term->term_id ) ) {
					$ids[] = (int) $term->term_id;
				}
				if ( is_array( $term ) && isset( $term['term_id'] ) ) {
					$ids[] = (int) $term['term_id'];
				}
			}
			return $ids;
		}
		return $terms;
	}
}

require_once __DIR__ . '/../includes/class-wordlift-cloud-schemaorg-distiller.php';
require_once __DIR__ . '/../includes/class-wordlift-cloud-entity-type-taxonomy-installer.php';
require_once __DIR__ . '/../includes/class-wordlift-cloud-entity-type-taxonomy-walker.php';
require_once __DIR__ . '/../includes/class-wordlift-cloud-schemaorg-taxonomy-metabox.php';
require_once __DIR__ . '/../includes/interface-wordlift-cloud-hookable-service.php';
require_once __DIR__ . '/../includes/interface-wordlift-cloud-activatable-service.php';
require_once __DIR__ . '/../includes/interface-wordlift-cloud-analytics-provider.php';
require_once __DIR__ . '/../includes/class-wordlift-cloud-entity-type-taxonomy.php';
require_once __DIR__ . '/../includes/class-wordlift-cloud-posthog-integration.php';
require_once __DIR__ . '/../includes/class-wordlift-cloud-frontend-bootstrap-script.php';
require_once __DIR__ . '/../includes/class-wordlift-cloud-bootstrap.php';
require_once __DIR__ . '/WordPressTestCase.php';
