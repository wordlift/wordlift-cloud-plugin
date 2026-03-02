<?php
/**
 * Custom taxonomy metabox for wl_entity_type in classic and block editors.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render wl_entity_type metabox with All / Most Used / A-Z tabs.
 */
class Wordlift_Cloud_Schemaorg_Taxonomy_Metabox {

	/**
	 * Render callback registered via taxonomy meta_box_cb.
	 *
	 * @param WP_Post $post Current post.
	 * @param array   $box Metabox config.
	 */
	public static function render( $post, $box ) {
		$defaults = array( 'taxonomy' => 'category' );
		$args     = isset( $box['args'] ) && is_array( $box['args'] ) ? $box['args'] : array();
		$config   = wp_parse_args( $args, $defaults );

		$taxonomy_name = (string) $config['taxonomy'];
		$taxonomy      = get_taxonomy( $taxonomy_name );
		if ( ! $taxonomy ) {
			return;
		}

		// Return popular IDs without printing markup; printing here breaks the metabox layout.
		$popular_ids = wp_popular_terms_checklist( $taxonomy_name, 0, 10, false );
		$input_name  = 'category' === $taxonomy_name ? 'post_category' : 'tax_input[' . $taxonomy_name . ']';
		?>
		<div id="taxonomy-<?php echo esc_attr( $taxonomy_name ); ?>" class="categorydiv">
			<ul id="<?php echo esc_attr( $taxonomy_name ); ?>-tabs" class="category-tabs">
				<li class="tabs">
					<a href="#<?php echo esc_attr( $taxonomy_name ); ?>-selected"><?php echo esc_html__( 'Selected', 'wordlift-cloud' ); ?> (<span class="wl-selected-count"><?php echo esc_html( (string) self::count_selected_terms( $post->ID, $taxonomy_name ) ); ?></span>)</a>
				</li>
				<li>
					<a href="#<?php echo esc_attr( $taxonomy_name ); ?>-all"><?php echo esc_html_x( 'All', 'Entity Types metabox', 'wordlift-cloud' ); ?></a>
				</li>
				<li class="hide-if-no-js">
					<a href="#<?php echo esc_attr( $taxonomy_name ); ?>-pop"><?php echo esc_html__( 'Most Used' ); ?></a>
				</li>
				<li>
					<a href="#<?php echo esc_attr( $taxonomy_name ); ?>-legacy"><?php echo esc_html_x( 'A-Z', 'Entity Types metabox', 'wordlift-cloud' ); ?></a>
				</li>
			</ul>
			<div id="<?php echo esc_attr( $taxonomy_name ); ?>-selected" class="tabs-panel">
				<ul id="<?php echo esc_attr( $taxonomy_name ); ?>-selected-list" class="categorychecklist form-no-clear">
					<?php self::render_selected_terms_list( $post->ID, $taxonomy_name, $input_name ); ?>
				</ul>
			</div>
			<div id="<?php echo esc_attr( $taxonomy_name ); ?>-all" class="tabs-panel" style="display:none;">
				<ul id="<?php echo esc_attr( $taxonomy_name ); ?>checklist-all" data-wp-lists="list:<?php echo esc_attr( $taxonomy_name ); ?>" class="categorychecklist form-no-clear">
					<?php
					wp_terms_checklist(
						$post->ID,
						array(
							'taxonomy'     => $taxonomy_name,
							'popular_cats' => $popular_ids,
							'checked_ontop' => false,
						)
					);
					?>
				</ul>
			</div>
			<div id="<?php echo esc_attr( $taxonomy_name ); ?>-pop" class="tabs-panel" style="display:none;">
				<ul id="<?php echo esc_attr( $taxonomy_name ); ?>checklist-pop" class="categorychecklist form-no-clear">
					<?php
					wp_popular_terms_checklist( $taxonomy_name );
					?>
				</ul>
			</div>
			<div id="<?php echo esc_attr( $taxonomy_name ); ?>-legacy" class="tabs-panel" style="display:none;">
				<input type="hidden" name="<?php echo esc_attr( $input_name ); ?>[]" value="0" />
				<ul id="<?php echo esc_attr( $taxonomy_name ); ?>checklist" data-wp-lists="list:<?php echo esc_attr( $taxonomy_name ); ?>" class="categorychecklist form-no-clear">
					<?php
					self::render_flat_terms_checklist( $post->ID, $taxonomy_name, $input_name );
					?>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a flat alphabetical checklist for the A-Z tab.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $taxonomy_name Taxonomy slug.
	 * @param string $input_name Input name prefix.
	 */
	private static function render_flat_terms_checklist( $post_id, $taxonomy_name, $input_name ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy_name,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		$selected_term_ids = wp_get_object_terms(
			(int) $post_id,
			$taxonomy_name,
			array(
				'fields' => 'ids',
			)
		);
		if ( is_wp_error( $selected_term_ids ) ) {
			$selected_term_ids = array();
		}

		$selected_lookup = array_fill_keys( array_map( 'intval', (array) $selected_term_ids ), true );
		foreach ( $terms as $term ) {
			if ( ! ( $term instanceof WP_Term ) ) {
				continue;
			}
			$term_id = (int) $term->term_id;
			?>
			<li id="<?php echo esc_attr( $taxonomy_name . '-' . $term_id ); ?>">
				<label class="selectit">
					<input
						value="<?php echo esc_attr( (string) $term_id ); ?>"
						type="checkbox"
						name="<?php echo esc_attr( $input_name ); ?>[]"
						id="in-<?php echo esc_attr( $taxonomy_name . '-' . $term_id ); ?>"
						<?php checked( isset( $selected_lookup[ $term_id ] ) ); ?>
					/>
					<?php echo esc_html( $term->name ); ?>
				</label>
			</li>
			<?php
		}
	}

	/**
	 * Count selected terms for current post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $taxonomy_name Taxonomy slug.
	 *
	 * @return int
	 */
	private static function count_selected_terms( $post_id, $taxonomy_name ) {
		$selected_term_ids = wp_get_object_terms(
			(int) $post_id,
			$taxonomy_name,
			array(
				'fields' => 'ids',
			)
		);
		if ( is_wp_error( $selected_term_ids ) ) {
			return 0;
		}

		return count( array_unique( array_map( 'intval', (array) $selected_term_ids ) ) );
	}

	/**
	 * Render selected term names as a compact list for quick visibility.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $taxonomy_name Taxonomy slug.
	 */
	private static function render_selected_terms_list( $post_id, $taxonomy_name, $input_name ) {
		$selected_terms = wp_get_object_terms(
			(int) $post_id,
			$taxonomy_name,
			array(
				'orderby' => 'name',
				'order'   => 'ASC',
			)
		);
		if ( is_wp_error( $selected_terms ) || empty( $selected_terms ) ) {
			echo '<li class="wl-no-selected-entity-types">' . esc_html__( 'No Entity Types selected yet.', 'wordlift-cloud' ) . '</li>';
			return;
		}

		foreach ( $selected_terms as $term ) {
			if ( ! ( $term instanceof WP_Term ) ) {
				continue;
			}
			$term_id = (int) $term->term_id;
			?>
			<li data-term-id="<?php echo esc_attr( (string) $term_id ); ?>">
				<label class="selectit">
					<input
						value="<?php echo esc_attr( (string) $term_id ); ?>"
						type="checkbox"
						name="<?php echo esc_attr( $input_name ); ?>[]"
						id="in-<?php echo esc_attr( $taxonomy_name . '-selected-' . $term_id ); ?>"
						checked="checked"
					/>
					<?php echo esc_html( $term->name ); ?>
				</label>
			</li>
			<?php
		}
	}
}
