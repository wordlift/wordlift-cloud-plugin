#!/usr/bin/env php
<?php
/**
 * Build a distilled schema class dataset for wl_entity_type bootstrap.
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/../' );

require_once __DIR__ . '/../includes/class-wordlift-cloud-schemaorg-distiller.php';

$schema_url = 'https://schema.org/version/latest/schemaorg-current-https.jsonld';
$jsonld     = file_get_contents( $schema_url );

if ( false === $jsonld ) {
	fwrite( STDERR, "Failed to fetch $schema_url\n" );
	exit( 1 );
}

$distiller = new Wordlift_Cloud_Schemaorg_Distiller();
$version   = 'schemaorg-current-https@' . gmdate( 'Y-m-d' );
$payload   = $distiller->distill( $jsonld, $version );

$output_file = __DIR__ . '/../resources/schemaorg/schema-classes.distilled.json';
$output_dir  = dirname( $output_file );
if ( ! is_dir( $output_dir ) && ! mkdir( $output_dir, 0755, true ) && ! is_dir( $output_dir ) ) {
	fwrite( STDERR, "Failed to create directory $output_dir\n" );
	exit( 1 );
}

$encoded = json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
if ( false === $encoded ) {
	fwrite( STDERR, "Failed to encode distilled payload.\n" );
	exit( 1 );
}

$result = file_put_contents( $output_file, $encoded . "\n" );
if ( false === $result ) {
	fwrite( STDERR, "Failed to write $output_file\n" );
	exit( 1 );
}

fwrite(
	STDOUT,
	sprintf(
		"Generated %s with %d classes.\n",
		$output_file,
		count( $payload['schemaClasses'] )
	)
);

