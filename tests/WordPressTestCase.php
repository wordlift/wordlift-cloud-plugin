<?php
declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

abstract class WordPressTestCase extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		wordlift_test_reset_wp_state();
		$_GET = array();
	}
}

