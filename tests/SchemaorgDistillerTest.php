<?php
declare( strict_types=1 );

final class SchemaorgDistillerTest extends WordPressTestCase {

	public function test_rewrite_slug_handles_acronyms_and_camel_case(): void {
		$distiller = new Wordlift_Cloud_Schemaorg_Distiller();

		self::assertSame( 'am-radio-channel', $distiller->rewrite_slug( 'AMRadioChannel' ) );
		self::assertSame( 'creative-work', $distiller->rewrite_slug( 'CreativeWork' ) );
		self::assertSame( 'wp-header', $distiller->rewrite_slug( 'WPHeader' ) );
		self::assertSame( '3-d-model', $distiller->rewrite_slug( '3DModel' ) );
	}

	public function test_distill_extracts_classes_hierarchy_and_metadata(): void {
		$distiller = new Wordlift_Cloud_Schemaorg_Distiller();

		$jsonld = json_encode(
			array(
				'@graph' => array(
					array(
						'@id'          => 'schema:Thing',
						'@type'        => 'rdfs:Class',
						'rdfs:label'   => 'Thing',
						'rdfs:comment' => 'Base class.',
					),
					array(
						'@id'            => 'https://schema.org/CreativeWork',
						'@type'          => array( 'owl:Class', 'rdfs:Class' ),
						'rdfs:label'     => array( '@value' => 'CreativeWork', '@language' => 'en' ),
						'rdfs:comment'   => array( '@value' => 'A creative work.', '@language' => 'en' ),
						'rdfs:subClassOf' => array( '@id' => 'schema:Thing' ),
					),
					array(
						'@id'            => 'schema:APIReference',
						'@type'          => 'rdfs:Class',
						'rdfs:subClassOf' => array(
							array( '@id' => 'schema:CreativeWork' ),
							array( '@id' => 'https://schema.org/Thing' ),
						),
					),
					array(
						'@id'   => 'schema:ignoredProperty',
						'@type' => 'rdf:Property',
					),
				),
			)
		);

		$result = $distiller->distill( $jsonld, 'test-version' );

		self::assertSame( 'test-version', $result['datasetVersion'] );
		self::assertCount( 3, $result['schemaClasses'] );

		$classes_by_name = array();
		foreach ( $result['schemaClasses'] as $class ) {
			$classes_by_name[ $class['name'] ] = $class;
		}

		self::assertSame( 'creative-work', $classes_by_name['CreativeWork']['slug'] );
		self::assertSame( 'A creative work.', $classes_by_name['CreativeWork']['description'] );
		self::assertSame( array( 'thing' ), $classes_by_name['CreativeWork']['parents'] );

		self::assertSame( array( 'creative-work', 'thing' ), $classes_by_name['APIReference']['parents'] );
		self::assertSame( array(), $classes_by_name['APIReference']['children'] );

		self::assertSame( array( 'api-reference', 'creative-work' ), $classes_by_name['Thing']['children'] );
	}

	public function test_distill_throws_on_invalid_jsonld(): void {
		$distiller = new Wordlift_Cloud_Schemaorg_Distiller();

		$this->expectException( InvalidArgumentException::class );
		$distiller->distill( '{"invalid":true}', 'test-version' );
	}

	public function test_distill_handles_edge_cases_and_filters_invalid_nodes(): void {
		$distiller = new Wordlift_Cloud_Schemaorg_Distiller();

		$jsonld = json_encode(
			array(
				'@graph' => array(
					array(
						'@id'          => 'schema:WPHeader',
						'@type'        => 'rdfs:Class',
						'rdfs:label'   => array( 'WPHeader' ),
						'rdfs:comment' => 'Header description.',
						'rdfs:subClassOf' => 'not-an-array',
					),
					array(
						'@id'          => 'schema:ValidClass1',
						'@type'        => array( 'rdfs:Class' ),
						'rdfs:label'   => array(
							array( '@value' => 'ValidClassOne', '@language' => 'en' ),
						),
						'rdfs:subClassOf' => array(
							array( '@id' => 'schema:WPHeader' ),
							array( '@id' => 'schema:invalid-value-with-dash' ),
							array( 'missing-id' => true ),
						),
					),
					array(
						'@id'          => 'https://schema.org/NoLabelClass',
						'@type'        => 'rdfs:Class',
						'rdfs:comment' => array(
							array( '@value' => 'No label comment.' ),
						),
					),
					array(
						'@id'   => 'schema:invalid-value-with-dash',
						'@type' => 'rdfs:Class',
					),
					array(
						'@id'   => 'schema:notAClass',
						'@type' => array( 'rdf:Property' ),
					),
				),
			)
		);

		$result         = $distiller->distill( $jsonld, 'edge-version' );
		$classes_by_name = array();
		foreach ( $result['schemaClasses'] as $class ) {
			$classes_by_name[ $class['name'] ] = $class;
		}

		self::assertArrayHasKey( 'WPHeader', $classes_by_name );
		self::assertArrayHasKey( 'ValidClass1', $classes_by_name );
		self::assertArrayHasKey( 'NoLabelClass', $classes_by_name );
		self::assertArrayNotHasKey( 'invalid-value-with-dash', $classes_by_name );

		self::assertSame( 'wp-header', $classes_by_name['WPHeader']['slug'] );
		self::assertSame( array(), $classes_by_name['WPHeader']['parents'] );
		self::assertSame( array( 'valid-class-1' ), $classes_by_name['WPHeader']['children'] );

		self::assertSame( 'ValidClassOne', $classes_by_name['ValidClass1']['label'] );
		self::assertSame( array( 'wp-header' ), $classes_by_name['ValidClass1']['parents'] );
		self::assertSame( '', $classes_by_name['ValidClass1']['description'] );

		self::assertSame( 'NoLabelClass', $classes_by_name['NoLabelClass']['label'] );
		self::assertSame( 'No label comment.', $classes_by_name['NoLabelClass']['description'] );
	}
}
