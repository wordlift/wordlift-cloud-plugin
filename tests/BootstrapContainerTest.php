<?php
declare( strict_types=1 );

final class BootstrapContainerTest extends WordPressTestCase {

	public function test_register_hooks_calls_all_hookable_services(): void {
		$hookable_one = new class() implements Wordlift_Cloud_Hookable_Service {
			public $called = 0;
			public function register_hooks() {
				++$this->called;
			}
		};

		$hookable_two = new class() implements Wordlift_Cloud_Hookable_Service {
			public $called = 0;
			public function register_hooks() {
				++$this->called;
			}
		};

		$bootstrap = new Wordlift_Cloud_Bootstrap(
			array( $hookable_one, $hookable_two ),
			array()
		);

		$bootstrap->register_hooks();

		self::assertSame( 1, $hookable_one->called );
		self::assertSame( 1, $hookable_two->called );
	}

	public function test_activate_calls_all_activatable_services(): void {
		$activatable_one = new class() implements Wordlift_Cloud_Activatable_Service {
			public $called = 0;
			public function activate() {
				++$this->called;
			}
		};

		$activatable_two = new class() implements Wordlift_Cloud_Activatable_Service {
			public $called = 0;
			public function activate() {
				++$this->called;
			}
		};

		$bootstrap = new Wordlift_Cloud_Bootstrap(
			array(),
			array( $activatable_one, $activatable_two )
		);

		$bootstrap->activate();

		self::assertSame( 1, $activatable_one->called );
		self::assertSame( 1, $activatable_two->called );
	}

	public function test_build_default_wires_runtime_services(): void {
		$bootstrap  = Wordlift_Cloud_Bootstrap::build_default();
		$reflection = new ReflectionClass( $bootstrap );

		$hookable_property = $reflection->getProperty( 'hookable_services' );
		$hookable_property->setAccessible( true );
		$hookable = $hookable_property->getValue( $bootstrap );

		$activatable_property = $reflection->getProperty( 'activatable_services' );
		$activatable_property->setAccessible( true );
		$activatable = $activatable_property->getValue( $bootstrap );

		self::assertCount( 3, $hookable );
		self::assertCount( 1, $activatable );
		self::assertInstanceOf( Wordlift_Cloud_Entity_Type_Taxonomy::class, $hookable[0] );
		self::assertInstanceOf( Wordlift_Cloud_Posthog_Integration::class, $hookable[1] );
		self::assertInstanceOf( Wordlift_Cloud_Frontend_Bootstrap_Script::class, $hookable[2] );
	}
}
