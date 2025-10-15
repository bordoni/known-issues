<?php
/**
 * Block registration and management.
 *
 * @package KnownIssues
 */

namespace KnownIssues;

/**
 * Class Blocks
 *
 * Handles registration of custom blocks.
 */
class Blocks {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
	}

	/**
	 * Register all custom blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {
		// Register the Affected Users block.
		register_block_type( KNOWN_ISSUES_DIR . 'build/affected-users' );
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// Localize script data for the block editor.
		wp_localize_script(
			'known-issues-affected-users-editor-script',
			'knownIssuesData',
			[
				'nonce'   => wp_create_nonce( 'known_issues_nonce' ),
				'restUrl' => rest_url( 'known-issues/v1' ),
			]
		);
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		// Only enqueue on known-issues post type.
		if ( ! is_singular( 'known-issues' ) ) {
			return;
		}

		wp_enqueue_script(
			'known-issues-frontend',
			KNOWN_ISSUES_URL . 'assets/js/frontend.js',
			[],
			KNOWN_ISSUES_VERSION,
			true
		);

		wp_localize_script(
			'known-issues-frontend',
			'knownIssuesData',
			[
				'nonce'               => wp_create_nonce( 'wp_rest' ),
				'restUrl'             => rest_url( 'known-issues/v1' ),
				'signingUp'           => __( 'Signing up...', 'known-issues' ),
				'unsubscribing'       => __( 'Unsubscribing...', 'known-issues' ),
				'confirmUnsubscribe'  => __( 'Are you sure you want to unsubscribe from this issue?', 'known-issues' ),
			]
		);
	}
}
