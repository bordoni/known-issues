<?php
/**
 * REST API initialization.
 *
 * @package KnownIssues
 */

namespace KnownIssues\RestApi;

/**
 * Class RestInit
 *
 * Initializes REST API endpoints.
 */
class RestInit {
	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'known-issues/v1';

	/**
	 * Affected Users controller.
	 *
	 * @var AffectedUsersController
	 */
	private $affected_users_controller;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		$this->affected_users_controller = new AffectedUsersController();
		$this->affected_users_controller->register_routes();
	}
}
