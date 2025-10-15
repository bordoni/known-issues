<?php
/**
 * Main Plugin class.
 *
 * @package KnownIssues
 */

namespace KnownIssues;

/**
 * Class Plugin
 *
 * Main plugin singleton class.
 */
class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Post Type handler.
	 *
	 * @var PostType
	 */
	public $post_type;

	/**
	 * Post Status handler.
	 *
	 * @var PostStatus
	 */
	public $post_status;

	/**
	 * Meta Fields handler.
	 *
	 * @var MetaFields
	 */
	public $meta_fields;

	/**
	 * Blocks handler.
	 *
	 * @var Blocks
	 */
	public $blocks;

	/**
	 * REST API handler.
	 *
	 * @var RestApi\RestInit
	 */
	public $rest_api;

	/**
	 * Admin Columns handler.
	 *
	 * @var AdminColumns
	 */
	public $admin_columns;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
		$this->init_components();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_action( 'init', [ $this, 'register_assets' ] );
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	private function init_components() {
		$this->post_type   = new PostType();
		$this->post_status = new PostStatus();
		$this->meta_fields = new MetaFields();
		$this->blocks      = new Blocks();
		$this->rest_api    = new RestApi\RestInit();

		// Admin-only components.
		if ( is_admin() ) {
			$this->admin_columns = new AdminColumns();
		}

		// Initialize HelpScout batch processor.
		HelpScout\BatchProcessor::init();

		// Register WP-CLI commands.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'known-issues', new Cli\Commands() );
		}
	}

	/**
	 * Load plugin textdomain for translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'known-issues',
			false,
			dirname( KNOWN_ISSUES_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register plugin assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		// Register block assets will be done by block registration.
	}
}
