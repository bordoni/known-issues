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
}
