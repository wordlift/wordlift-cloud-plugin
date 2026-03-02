<?php
/**
 * Schema.org distiller used to create and validate the wl_entity_type seed data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Converts Schema.org JSON-LD into a compact class dataset tailored for taxonomy bootstrap.
 */
class Wordlift_Cloud_Schemaorg_Distiller {

	/**
	 * Create a distilled schema classes payload from a JSON-LD document.
	 *
	 * @param string $jsonld Raw schema.org JSON-LD.
	 * @param string $dataset_version Version marker saved with the generated data.
	 *
	 * @return array<string,mixed>
	 */
	public function distill( $jsonld, $dataset_version ) {
		$decoded = json_decode( $jsonld, true );
		if ( ! is_array( $decoded ) || ! isset( $decoded['@graph'] ) || ! is_array( $decoded['@graph'] ) ) {
			throw new InvalidArgumentException( 'Invalid JSON-LD: expected @graph.' );
		}

		$classes = array();
		foreach ( $decoded['@graph'] as $node ) {
			if ( ! $this->is_rdfs_class_node( $node ) ) {
				continue;
			}

			$name = $this->extract_class_name( $node );
			if ( null === $name ) {
				continue;
			}

			$classes[ $name ] = array(
				'name'        => $name,
				'label'       => $this->extract_label( $node, $name ),
				'slug'        => $this->rewrite_slug( $name ),
				'description' => $this->extract_description( $node ),
				'parents'     => array(),
				'children'    => array(),
			);
		}

		foreach ( $decoded['@graph'] as $node ) {
			if ( ! $this->is_rdfs_class_node( $node ) ) {
				continue;
			}

			$name = $this->extract_class_name( $node );
			if ( null === $name || ! isset( $classes[ $name ] ) ) {
				continue;
			}

			$parents = $this->extract_parent_names( $node );
			foreach ( $parents as $parent_name ) {
				if ( ! isset( $classes[ $parent_name ] ) ) {
					continue;
				}
				$classes[ $name ]['parents'][]                = $classes[ $parent_name ]['slug'];
				$classes[ $parent_name ]['children'][]        = $classes[ $name ]['slug'];
			}
		}

		foreach ( $classes as &$class ) {
			$class['parents']  = array_values( array_unique( $class['parents'] ) );
			$class['children'] = array_values( array_unique( $class['children'] ) );
			sort( $class['parents'] );
			sort( $class['children'] );
		}
		unset( $class );

		usort(
			$classes,
			static function ( $left, $right ) {
				return strcmp( $left['name'], $right['name'] );
			}
		);

		return array(
			'datasetVersion' => (string) $dataset_version,
			'schemaClasses'  => array_values( $classes ),
		);
	}

	/**
	 * Convert a schema class name to the taxonomy slug format.
	 *
	 * @param string $name Schema class CamelCase name.
	 *
	 * @return string
	 */
	public function rewrite_slug( $name ) {
		$slug = preg_replace( '/([A-Z]+)([A-Z][a-z])/', '$1-$2', $name );
		$slug = preg_replace( '/([a-z\\d])([A-Z])/', '$1-$2', $slug );
		$slug = preg_replace( '/([A-Za-z])(\\d)/', '$1-$2', $slug );
		$slug = preg_replace( '/(\\d)([A-Za-z])/', '$1-$2', $slug );
		$slug = strtolower( $slug );
		$slug = preg_replace( '/[^a-z0-9]+/', '-', $slug );
		$slug = trim( $slug, '-' );

		return $slug;
	}

	/**
	 * @param mixed $node Decoded JSON-LD node.
	 *
	 * @return bool
	 */
	private function is_rdfs_class_node( $node ) {
		if ( ! is_array( $node ) || ! isset( $node['@type'] ) ) {
			return false;
		}

		$type = $node['@type'];
		if ( is_string( $type ) ) {
			return 'rdfs:Class' === $type;
		}

		if ( is_array( $type ) ) {
			return in_array( 'rdfs:Class', $type, true );
		}

		return false;
	}

	/**
	 * @param array<string,mixed> $node JSON-LD node.
	 *
	 * @return string|null
	 */
	private function extract_class_name( $node ) {
		if ( ! isset( $node['@id'] ) || ! is_string( $node['@id'] ) ) {
			return null;
		}

		$id = $node['@id'];
		$id = preg_replace( '#^https?://schema.org/#', '', $id );
		$id = preg_replace( '#^schema:#', '', $id );

		if ( ! preg_match( '/^[A-Za-z][A-Za-z0-9]*$/', $id ) ) {
			return null;
		}

		return $id;
	}

	/**
	 * @param array<string,mixed> $node JSON-LD node.
	 * @param string              $fallback Default class label.
	 *
	 * @return string
	 */
	private function extract_label( $node, $fallback ) {
		if ( isset( $node['rdfs:label'] ) ) {
			$label = $this->extract_value_string( $node['rdfs:label'] );
			if ( null !== $label && '' !== $label ) {
				return $label;
			}
		}

		return $fallback;
	}

	/**
	 * @param array<string,mixed> $node JSON-LD node.
	 *
	 * @return string
	 */
	private function extract_description( $node ) {
		if ( isset( $node['rdfs:comment'] ) ) {
			$description = $this->extract_value_string( $node['rdfs:comment'] );
			if ( null !== $description ) {
				return $description;
			}
		}

		return '';
	}

	/**
	 * @param array<string,mixed> $node JSON-LD node.
	 *
	 * @return array<int,string>
	 */
	private function extract_parent_names( $node ) {
		if ( ! isset( $node['rdfs:subClassOf'] ) ) {
			return array();
		}

		$raw_parents = $node['rdfs:subClassOf'];
		if ( ! is_array( $raw_parents ) ) {
			return array();
		}

		$parents = array();

		// Single object case.
		if ( isset( $raw_parents['@id'] ) && is_string( $raw_parents['@id'] ) ) {
			$parent_name = $this->normalize_class_id( $raw_parents['@id'] );
			if ( null !== $parent_name ) {
				$parents[] = $parent_name;
			}

			return $parents;
		}

		// Array-of-objects case.
		foreach ( $raw_parents as $raw_parent ) {
			if ( ! is_array( $raw_parent ) || ! isset( $raw_parent['@id'] ) || ! is_string( $raw_parent['@id'] ) ) {
				continue;
			}
			$parent_name = $this->normalize_class_id( $raw_parent['@id'] );
			if ( null !== $parent_name ) {
				$parents[] = $parent_name;
			}
		}

		return $parents;
	}

	/**
	 * @param mixed $raw JSON-LD value.
	 *
	 * @return string|null
	 */
	private function extract_value_string( $raw ) {
		if ( is_string( $raw ) ) {
			return trim( $raw );
		}

		if ( is_array( $raw ) && isset( $raw['@value'] ) && is_string( $raw['@value'] ) ) {
			return trim( $raw['@value'] );
		}

		if ( is_array( $raw ) ) {
			foreach ( $raw as $item ) {
				if ( is_array( $item ) && isset( $item['@value'] ) && is_string( $item['@value'] ) ) {
					return trim( $item['@value'] );
				}
				if ( is_string( $item ) ) {
					return trim( $item );
				}
			}
		}

		return null;
	}

	/**
	 * @param string $id Class identifier.
	 *
	 * @return string|null
	 */
	private function normalize_class_id( $id ) {
		$id = preg_replace( '#^https?://schema.org/#', '', $id );
		$id = preg_replace( '#^schema:#', '', $id );

		if ( ! preg_match( '/^[A-Za-z][A-Za-z0-9]*$/', $id ) ) {
			return null;
		}

		return $id;
	}
}

