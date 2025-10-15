<?php
/**
 * Webhook handler for Jira webhooks.
 *
 * @package KnownIssues
 */

namespace KnownIssues\Jira;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class WebhookHandler
 *
 * Handles incoming Jira webhooks.
 */
class WebhookHandler extends WP_REST_Controller {
	/**
	 * Register routes for the webhook handler.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'known-issues/v1',
			'/webhooks/jira',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_webhook' ],
					'permission_callback' => [ $this, 'permissions_check' ],
				],
			]
		);
	}

	/**
	 * Check permissions for webhook endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function permissions_check( $request ) {
		// Verify URL secret.
		$url_secret = $request->get_param( 'secret' );
		$expected_secret = defined( 'KNOWN_ISSUES_WEBHOOK_URL_SECRET' ) ? KNOWN_ISSUES_WEBHOOK_URL_SECRET : '';

		if ( ! SignatureVerifier::verify_url_secret( $url_secret, $expected_secret ) ) {
			\KnownIssues\log( 'Webhook URL secret verification failed', 'error' );
			return new WP_Error(
				'invalid_secret',
				__( 'Invalid webhook secret', 'known-issues' ),
				[ 'status' => 401 ]
			);
		}

		// Verify HMAC signature.
		$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
		$payload = $request->get_body();
		$hmac_secret = defined( 'KNOWN_ISSUES_WEBHOOK_SECRET' ) ? KNOWN_ISSUES_WEBHOOK_SECRET : '';

		if ( ! SignatureVerifier::verify( $payload, $signature, $hmac_secret ) ) {
			\KnownIssues\log( 'Webhook HMAC signature verification failed', 'error' );
			return new WP_Error(
				'invalid_signature',
				__( 'Invalid webhook signature', 'known-issues' ),
				[ 'status' => 401 ]
			);
		}

		return true;
	}

	/**
	 * Handle incoming webhook.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_webhook( $request ) {
		$payload = $request->get_json_params();

		if ( empty( $payload ) ) {
			\KnownIssues\log( 'Webhook received empty payload', 'warning' );
			return new WP_Error(
				'invalid_payload',
				__( 'Invalid webhook payload', 'known-issues' ),
				[ 'status' => 400 ]
			);
		}

		\KnownIssues\log( sprintf( 'Webhook received: %s', $payload['webhookEvent'] ?? 'unknown' ), 'info' );

		// Map the payload.
		$mapped = PayloadMapper::map( $payload );

		if ( empty( $mapped['external_id'] ) ) {
			\KnownIssues\log( 'Webhook payload missing issue key', 'warning' );
			return new WP_Error(
				'missing_issue_key',
				__( 'Webhook payload missing issue key', 'known-issues' ),
				[ 'status' => 400 ]
			);
		}

		// Check if post already exists.
		$existing_post = $this->find_post_by_external_id( $mapped['external_id'] );

		if ( $existing_post ) {
			// Update existing post.
			$post_id = $this->update_post( $existing_post->ID, $mapped, $payload );
		} else {
			// Create new post.
			$post_id = $this->create_post( $mapped, $payload );
		}

		if ( is_wp_error( $post_id ) ) {
			\KnownIssues\log( sprintf( 'Failed to process webhook: %s', $post_id->get_error_message() ), 'error' );
			return $post_id;
		}

		\KnownIssues\log( sprintf( 'Webhook processed successfully, post ID: %d', $post_id ), 'info' );

		return new WP_REST_Response(
			[
				'success' => true,
				'post_id' => $post_id,
				'action'  => $existing_post ? 'updated' : 'created',
			],
			200
		);
	}

	/**
	 * Find a post by external ID.
	 *
	 * @param string $external_id Jira issue key.
	 * @return \WP_Post|null Post object or null if not found.
	 */
	private function find_post_by_external_id( $external_id ) {
		$posts = get_posts(
			[
				'post_type'  => 'known-issues',
				'meta_key'   => '_ki_external_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $external_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'numberposts' => 1,
			]
		);

		return ! empty( $posts ) ? $posts[0] : null;
	}

	/**
	 * Create a new post from webhook data.
	 *
	 * @param array $mapped  Mapped post data.
	 * @param array $payload Original webhook payload.
	 * @return int|WP_Error Post ID or error.
	 */
	private function create_post( $mapped, $payload ) {
		$post_id = wp_insert_post(
			[
				'post_type'    => 'known-issues',
				'post_title'   => $mapped['title'],
				'post_content' => $mapped['content'],
				'post_status'  => $mapped['status'],
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Add post meta.
		$meta = PayloadMapper::get_post_meta( $payload );
		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		// Store the complete payload for history.
		$this->store_payload_history( $post_id, $payload );

		return $post_id;
	}

	/**
	 * Update an existing post from webhook data.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $mapped  Mapped post data.
	 * @param array $payload Original webhook payload.
	 * @return int|WP_Error Post ID or error.
	 */
	private function update_post( $post_id, $mapped, $payload ) {
		$old_status = get_post_status( $post_id );

		$result = wp_update_post(
			[
				'ID'           => $post_id,
				'post_title'   => $mapped['title'],
				'post_content' => $mapped['content'],
				'post_status'  => $mapped['status'],
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update post meta.
		$meta = PayloadMapper::get_post_meta( $payload );
		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		// Store the complete payload for history.
		$this->store_payload_history( $post_id, $payload );

		// Check if status changed to resolved.
		if ( $old_status !== $mapped['status'] && StatusMapper::is_resolved( $mapped['status'] ) ) {
			\KnownIssues\log( sprintf( 'Issue %d marked as resolved, queueing notifications', $post_id ), 'info' );
			$this->queue_resolution_notifications( $post_id );
		}

		return $post_id;
	}

	/**
	 * Store webhook payload in post meta for history.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $payload Webhook payload.
	 * @return void
	 */
	private function store_payload_history( $post_id, $payload ) {
		$history_entry = wp_json_encode(
			[
				'timestamp' => current_time( 'mysql' ),
				'event'     => $payload['webhookEvent'] ?? 'unknown',
				'payload'   => $payload,
			]
		);

		// Use add_post_meta to store multiple values (history).
		add_post_meta( $post_id, '_ki_jira_payload', $history_entry );
	}

	/**
	 * Queue resolution notifications for all affected users.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function queue_resolution_notifications( $post_id ) {
		// Get all affected users.
		$comments = get_comments(
			[
				'post_id' => $post_id,
				'type'    => 'ki_affected_user',
				'status'  => 'pending',
			]
		);

		if ( empty( $comments ) ) {
			return;
		}

		$queue = get_option( '_ki_helpscout_queue_resolved', [] );

		foreach ( $comments as $comment ) {
			$conversation_id = get_comment_meta( $comment->comment_ID, '_ki_helpscout_conversation_id', true );

			if ( empty( $conversation_id ) ) {
				continue;
			}

			$queue[] = [
				'comment_id'      => $comment->comment_ID,
				'post_id'         => $post_id,
				'conversation_id' => $conversation_id,
				'user_email'      => $comment->comment_author_email,
				'retry_count'     => 0,
				'next_retry'      => time(),
			];
		}

		update_option( '_ki_helpscout_queue_resolved', $queue );

		\KnownIssues\log( sprintf( 'Queued %d resolution notifications', count( $comments ) ), 'info' );
	}
}
