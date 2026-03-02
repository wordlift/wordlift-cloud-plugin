<?php
/**
 * Service container/bootstrap orchestration for the plugin runtime.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates service registration and activation.
 */
class Wordlift_Cloud_Bootstrap {

	/**
	 * @var array<int,Wordlift_Cloud_Hookable_Service>
	 */
	private $hookable_services;

	/**
	 * @var array<int,Wordlift_Cloud_Activatable_Service>
	 */
	private $activatable_services;

	/**
	 * @param array<int,Wordlift_Cloud_Hookable_Service>    $hookable_services Services that register hooks.
	 * @param array<int,Wordlift_Cloud_Activatable_Service> $activatable_services Services requiring activation.
	 */
	public function __construct( $hookable_services, $activatable_services ) {
		$this->hookable_services    = $hookable_services;
		$this->activatable_services = $activatable_services;
	}

	/**
	 * Build default runtime service graph.
	 *
	 * @return self
	 */
	public static function build_default() {
		$taxonomy_service = new Wordlift_Cloud_Entity_Type_Taxonomy();
		$posthog_service  = new Wordlift_Cloud_Posthog_Integration();
		$script_service   = new Wordlift_Cloud_Frontend_Bootstrap_Script();

		return new self(
			array( $taxonomy_service, $posthog_service, $script_service ),
			array( $taxonomy_service )
		);
	}

	/**
	 * Register hooks for all hookable services.
	 */
	public function register_hooks() {
		foreach ( $this->hookable_services as $service ) {
			$service->register_hooks();
		}
	}

	/**
	 * Execute activation lifecycle on all activatable services.
	 */
	public function activate() {
		foreach ( $this->activatable_services as $service ) {
			$service->activate();
		}
	}
}

