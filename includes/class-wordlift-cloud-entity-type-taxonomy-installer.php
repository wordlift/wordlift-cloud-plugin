<?php
/**
 * Installer and sync logic for wl_entity_type taxonomy terms.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Synchronizes wl_entity_type terms with the distilled Schema.org dataset.
 */
class Wordlift_Cloud_Entity_Type_Taxonomy_Installer {

	/**
	 * Option used to track the dataset hash currently installed.
	 */
	const DATA_HASH_OPTION = 'wl_cloud_schemaorg_data_hash';

	/**
	 * Option used to track the dataset version currently installed.
	 */
	const DATA_VERSION_OPTION = 'wl_cloud_schemaorg_data_version';

	/**
	 * Term meta key for canonical schema class name.
	 */
	const NAME_META_KEY = '_wl_name';

	/**
	 * Term meta key for canonical schema URI.
	 */
	const URI_META_KEY = '_wl_uri';

	/**
	 * Term meta key listing child slugs.
	 */
	const PARENT_OF_META_KEY = '_wl_parent_of';

	/**
	 * @var string
	 */
	private $taxonomy_name;

	/**
	 * @var string
	 */
	private $dataset_file;

	/**
	 * @var string
	 */
	private $last_sync_status = 'not-run';

	/**
	 * @param string $taxonomy_name Taxonomy slug.
	 * @param string $dataset_file Distilled schema dataset path.
	 */
	public function __construct( $taxonomy_name, $dataset_file ) {
		$this->taxonomy_name = $taxonomy_name;
		$this->dataset_file  = $dataset_file;
	}

	/**
	 * Perform sync only when data changed or when forced.
	 *
	 * @param bool $force Force sync even if hash is unchanged.
	 *
	 * @return bool True when sync succeeded or was not needed.
	 */
	public function maybe_sync( $force = false ) {
		$dataset = $this->load_dataset();
		if ( null === $dataset ) {
			$this->last_sync_status = 'failed';
			return false;
		}

		$payload_hash = md5( wp_json_encode( $dataset ) );
		$stored_hash  = (string) get_option( self::DATA_HASH_OPTION, '' );
		$excluded_slugs = self::get_excluded_slugs( $dataset['schemaClasses'] );

		// Always enforce exclusion cleanup, even when the dataset hash did not change.
		$this->remove_excluded_terms( $excluded_slugs );
		if ( ! $force && '' !== $stored_hash && hash_equals( $stored_hash, $payload_hash ) ) {
			$this->last_sync_status = 'no-change';
			return true;
		}

		$this->sync_terms( $dataset['schemaClasses'], $excluded_slugs );
		update_option( self::DATA_HASH_OPTION, $payload_hash, true );
		update_option( self::DATA_VERSION_OPTION, (string) $dataset['datasetVersion'], true );
		$this->last_sync_status = 'synced';

		return true;
	}

	/**
	 * Return the status of the most recent sync execution.
	 *
	 * @return string
	 */
	public function get_last_sync_status() {
		return $this->last_sync_status;
	}

	/**
	 * Read and validate distilled schema data.
	 *
	 * @return array<string,mixed>|null
	 */
	private function load_dataset() {
		if ( ! file_exists( $this->dataset_file ) ) {
			return null;
		}

		$raw = file_get_contents( $this->dataset_file );
		if ( false === $raw ) {
			return null;
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) || ! isset( $decoded['schemaClasses'] ) || ! is_array( $decoded['schemaClasses'] ) ) {
			return null;
		}

		if ( ! isset( $decoded['datasetVersion'] ) || ! is_string( $decoded['datasetVersion'] ) ) {
			$decoded['datasetVersion'] = 'unknown';
		}

		return $decoded;
	}

	/**
	 * Upsert terms and refresh hierarchy/meta in two passes.
	 *
	 * @param array<int,array<string,mixed>> $classes Distilled classes.
	 * @param array<int,string>              $excluded_slugs Slugs to skip from synchronization.
	 */
	private function sync_terms( $classes, $excluded_slugs ) {
		$term_ids_by_slug = array();
		$excluded_lookup  = array_fill_keys( $excluded_slugs, true );

		foreach ( $classes as $class ) {
			if ( ! $this->is_valid_schema_class_record( $class ) ) {
				continue;
			}

			$slug        = $class['slug'];
			if ( isset( $excluded_lookup[ $slug ] ) ) {
				continue;
			}
			$description = $class['description'];
			$label       = $class['label'];
			$name        = $class['name'];
			$uri         = 'https://schema.org/' . $name;

			$term    = term_exists( $slug, $this->taxonomy_name );
			$term_id = 0;

			$args = array(
				'slug'        => $slug,
				'description' => $description,
				'parent'      => 0,
			);

			if ( is_array( $term ) && isset( $term['term_id'] ) ) {
				$term_id = (int) $term['term_id'];
				wp_update_term( $term_id, $this->taxonomy_name, array_merge( $args, array( 'name' => $label ) ) );
			} else {
				$inserted = wp_insert_term( $label, $this->taxonomy_name, $args );
				if ( is_wp_error( $inserted ) || ! isset( $inserted['term_id'] ) ) {
					continue;
				}
				$term_id = (int) $inserted['term_id'];
			}

			$term_ids_by_slug[ $slug ] = $term_id;

			update_term_meta( $term_id, self::NAME_META_KEY, $name );
			update_term_meta( $term_id, self::URI_META_KEY, $uri );

			delete_term_meta( $term_id, self::PARENT_OF_META_KEY );
			foreach ( $class['children'] as $child_slug ) {
				if ( isset( $excluded_lookup[ $child_slug ] ) ) {
					continue;
				}
				add_term_meta( $term_id, self::PARENT_OF_META_KEY, $child_slug );
			}
		}

		foreach ( $classes as $class ) {
			if ( ! $this->is_valid_schema_class_record( $class ) ) {
				continue;
			}

			$slug = $class['slug'];
			if ( ! isset( $term_ids_by_slug[ $slug ] ) ) {
				continue;
			}

			$parent_slug = null;
			foreach ( $class['parents'] as $candidate_parent_slug ) {
				if ( isset( $excluded_lookup[ $candidate_parent_slug ] ) ) {
					continue;
				}
				$parent_slug = $candidate_parent_slug;
				break;
			}
			$parent_id   = ( $parent_slug && isset( $term_ids_by_slug[ $parent_slug ] ) ) ? $term_ids_by_slug[ $parent_slug ] : 0;

			wp_update_term(
				$term_ids_by_slug[ $slug ],
				$this->taxonomy_name,
				array(
					'parent' => (int) $parent_id,
				)
			);
		}
	}

	/**
	 * @param mixed $class Schema class record candidate.
	 *
	 * @return bool
	 */
	private function is_valid_schema_class_record( $class ) {
		if ( ! is_array( $class ) ) {
			return false;
		}

		$required = array( 'name', 'label', 'slug', 'description', 'parents', 'children' );
		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $class ) ) {
				return false;
			}
		}

		if ( ! is_string( $class['name'] ) || ! is_string( $class['label'] ) || ! is_string( $class['slug'] ) || ! is_string( $class['description'] ) ) {
			return false;
		}

		if ( ! is_array( $class['parents'] ) || ! is_array( $class['children'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Remove terms that must be excluded from wl_entity_type.
	 *
	 * @param array<int,string> $excluded_slugs Slugs to remove.
	 */
	private function remove_excluded_terms( $excluded_slugs ) {
		foreach ( $excluded_slugs as $slug ) {
			$term = term_exists( $slug, $this->taxonomy_name );
			if ( ! is_array( $term ) || ! isset( $term['term_id'] ) ) {
				continue;
			}
			wp_delete_term( (int) $term['term_id'], $this->taxonomy_name );
		}
	}

	/**
	 * Return descendant slugs for an ancestor slug from distilled schema classes.
	 *
	 * @param array<int,array<string,mixed>> $classes Distilled classes.
	 * @param string                         $ancestor_slug Ancestor slug.
	 *
	 * @return array<int,string>
	 */
	public static function get_descendant_slugs( $classes, $ancestor_slug ) {
		$children_by_slug = array();
		foreach ( $classes as $class ) {
			if ( ! is_array( $class ) || ! isset( $class['slug'] ) || ! is_string( $class['slug'] ) ) {
				continue;
			}
			$slug = $class['slug'];
			if ( ! isset( $children_by_slug[ $slug ] ) ) {
				$children_by_slug[ $slug ] = array();
			}
			if ( isset( $class['children'] ) && is_array( $class['children'] ) ) {
				foreach ( $class['children'] as $child_slug ) {
					if ( is_string( $child_slug ) && '' !== $child_slug ) {
						$children_by_slug[ $slug ][] = $child_slug;
					}
				}
			}
			$children_by_slug[ $slug ] = array_values( array_unique( $children_by_slug[ $slug ] ) );
		}

		if ( ! isset( $children_by_slug[ $ancestor_slug ] ) ) {
			return array();
		}

		$queue   = $children_by_slug[ $ancestor_slug ];
		$visited = array();
		while ( ! empty( $queue ) ) {
			$current = array_shift( $queue );
			if ( isset( $visited[ $current ] ) ) {
				continue;
			}
			$visited[ $current ] = true;
			if ( isset( $children_by_slug[ $current ] ) ) {
				foreach ( $children_by_slug[ $current ] as $child_slug ) {
					if ( ! isset( $visited[ $child_slug ] ) ) {
						$queue[] = $child_slug;
					}
				}
			}
		}

		$descendants = array_keys( $visited );
		sort( $descendants );

		return $descendants;
	}

	/**
	 * Return all slugs that must be excluded from wl_entity_type.
	 *
	 * Currently excludes DataType and all its descendants.
	 *
	 * @param array<int,array<string,mixed>> $classes Distilled classes.
	 *
	 * @return array<int,string>
	 */
	public static function get_excluded_slugs( $classes ) {
		$excluded_slugs = array( 'data-type' );
		$excluded_slugs = array_merge( $excluded_slugs, self::get_descendant_slugs( $classes, 'data-type' ) );
		$excluded_slugs = array_values( array_unique( $excluded_slugs ) );
		sort( $excluded_slugs );

		return $excluded_slugs;
	}
}
