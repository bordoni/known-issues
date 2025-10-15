<?php
/**
 * Meta Fields registration.
 *
 * @package KnownIssues
 */

namespace KnownIssues;

/**
 * Class MetaFields
 *
 * Registers post meta and comment meta fields.
 */
class MetaFields {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_meta' ] );
		add_action( 'init', [ $this, 'register_comment_meta' ] );
	}

	/**
	 * Register post meta fields.
	 *
	 * @return void
	 */
	public function register_post_meta() {
		// Jira issue key (external ID).
		register_post_meta(
			PostType::POST_TYPE,
			'_ki_external_id',
			[
				'type'              => 'string',
				'description'       => __( 'Jira issue key', 'known-issues' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			]
		);

		// Jira payload (multi-value for history).
		register_post_meta(
			PostType::POST_TYPE,
			'_ki_jira_payload',
			[
				'type'              => 'string',
				'description'       => __( 'Jira webhook payload (JSON)', 'known-issues' ),
				'single'            => false,
				'show_in_rest'      => false,
				'sanitize_callback' => [ $this, 'sanitize_json' ],
			]
		);

		// Jira event type.
		register_post_meta(
			PostType::POST_TYPE,
			'_ki_jira_event_type',
			[
				'type'              => 'string',
				'description'       => __( 'Jira webhook event type', 'known-issues' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			]
		);

		// Jira project key.
		register_post_meta(
			PostType::POST_TYPE,
			'_ki_jira_project',
			[
				'type'              => 'string',
				'description'       => __( 'Jira project key', 'known-issues' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			]
		);

		// Jira issue type.
		register_post_meta(
			PostType::POST_TYPE,
			'_ki_jira_issue_type',
			[
				'type'              => 'string',
				'description'       => __( 'Jira issue type', 'known-issues' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			]
		);

		// Jira priority.
		register_post_meta(
			PostType::POST_TYPE,
			'_ki_jira_priority',
			[
				'type'              => 'string',
				'description'       => __( 'Jira issue priority', 'known-issues' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			]
		);
	}

	/**
	 * Register comment meta fields.
	 *
	 * @return void
	 */
	public function register_comment_meta() {
		// HelpScout conversation ID.
		register_meta(
			'comment',
			'_ki_helpscout_conversation_id',
			[
				'type'              => 'string',
				'description'       => __( 'HelpScout conversation ID', 'known-issues' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
			]
		);

		// HelpScout notification status.
		register_meta(
			'comment',
			'_ki_helpscout_status',
			[
				'type'              => 'string',
				'description'       => __( 'HelpScout notification status', 'known-issues' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
			]
		);

		// Signup date.
		register_meta(
			'comment',
			'_ki_signup_date',
			[
				'type'              => 'string',
				'description'       => __( 'Date user signed up as affected', 'known-issues' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
			]
		);
	}

	/**
	 * Sanitize JSON data.
	 *
	 * @param string $value JSON string to sanitize.
	 * @return string Sanitized JSON string.
	 */
	public function sanitize_json( $value ) {
		// Decode to validate JSON.
		$decoded = json_decode( $value, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return '';
		}

		// Re-encode to ensure proper formatting.
		return wp_json_encode( $decoded );
	}
}
