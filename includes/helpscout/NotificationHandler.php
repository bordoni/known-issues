<?php
/**
 * Notification handler for HelpScout.
 *
 * @package KnownIssues
 */

namespace KnownIssues\HelpScout;

/**
 * Class NotificationHandler
 *
 * Handles creating and sending HelpScout notifications.
 */
class NotificationHandler {
	/**
	 * Send signup notification to user.
	 *
	 * @param int $comment_id Comment ID.
	 * @param int $post_id    Post ID.
	 * @return bool True on success, false on failure.
	 */
	public static function send_signup_notification( $comment_id, $post_id ) {
		$comment = get_comment( $comment_id );
		$post    = get_post( $post_id );

		if ( ! $comment || ! $post ) {
			\KnownIssues\log( sprintf( 'Invalid comment or post for signup notification: %d/%d', $comment_id, $post_id ), 'error' );
			return false;
		}

		$mailbox_id = defined( 'KNOWN_ISSUES_HELPSCOUT_MAILBOX_ID' ) ? KNOWN_ISSUES_HELPSCOUT_MAILBOX_ID : '';

		if ( empty( $mailbox_id ) ) {
			\KnownIssues\log( 'HelpScout mailbox ID not configured', 'error' );
			return false;
		}

		// Create conversation data.
		$data = [
			'subject'   => sprintf(
				/* translators: %s: Issue title */
				__( "You're now tracking: %s", 'known-issues' ),
				$post->post_title
			),
			'mailboxId' => (int) $mailbox_id,
			'type'      => 'email',
			'status'    => 'active',
			'customer'  => [
				'email' => $comment->comment_author_email,
			],
			'threads'   => [
				[
					'type'     => 'customer',
					'customer' => [
						'email' => $comment->comment_author_email,
					],
					'text'     => self::get_signup_message( $post ),
				],
			],
		];

		// Create conversation.
		$response = ApiClient::create_conversation( $data );

		if ( ! $response ) {
			\KnownIssues\log( sprintf( 'Failed to create HelpScout conversation for comment %d', $comment_id ), 'error' );
			return false;
		}

		// Get conversation ID from response or header.
		$conversation_id = null;

		if ( ! empty( $response['id'] ) ) {
			$conversation_id = $response['id'];
		} elseif ( ! empty( $response['_embedded']['conversations'][0]['id'] ) ) {
			$conversation_id = $response['_embedded']['conversations'][0]['id'];
		}

		if ( $conversation_id ) {
			// Store conversation ID in comment meta.
			update_comment_meta( $comment_id, '_ki_helpscout_conversation_id', $conversation_id );
			update_comment_meta( $comment_id, '_ki_helpscout_status', 'notified' );

			\KnownIssues\log( sprintf( 'Created HelpScout conversation %s for comment %d', $conversation_id, $comment_id ), 'info' );

			return true;
		}

		\KnownIssues\log( sprintf( 'HelpScout conversation created but no ID returned for comment %d', $comment_id ), 'warning' );
		return false;
	}

	/**
	 * Send resolution notification to user.
	 *
	 * @param int    $comment_id      Comment ID.
	 * @param int    $post_id         Post ID.
	 * @param string $conversation_id HelpScout conversation ID.
	 * @return bool True on success, false on failure.
	 */
	public static function send_resolution_notification( $comment_id, $post_id, $conversation_id ) {
		$comment = get_comment( $comment_id );
		$post    = get_post( $post_id );

		if ( ! $comment || ! $post ) {
			\KnownIssues\log( sprintf( 'Invalid comment or post for resolution notification: %d/%d', $comment_id, $post_id ), 'error' );
			return false;
		}

		if ( empty( $conversation_id ) ) {
			\KnownIssues\log( sprintf( 'Missing conversation ID for comment %d', $comment_id ), 'error' );
			return false;
		}

		// Create thread data.
		$data = [
			'type'   => 'note',
			'text'   => self::get_resolution_message( $post ),
			'status' => 'closed',
		];

		// Add thread to conversation.
		$response = ApiClient::create_thread( $conversation_id, $data );

		if ( ! $response ) {
			\KnownIssues\log( sprintf( 'Failed to create HelpScout thread for conversation %s', $conversation_id ), 'error' );
			return false;
		}

		// Update comment status.
		wp_update_comment(
			[
				'comment_ID'       => $comment_id,
				'comment_approved' => 'approved',
			]
		);

		update_comment_meta( $comment_id, '_ki_helpscout_status', 'resolved_notification_sent' );

		\KnownIssues\log( sprintf( 'Sent resolution notification to conversation %s', $conversation_id ), 'info' );

		return true;
	}

	/**
	 * Get signup notification message.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string Message text.
	 */
	private static function get_signup_message( $post ) {
		$permalink = get_permalink( $post );

		$message = sprintf(
			/* translators: 1: Issue title, 2: Permalink */
			__(
				"This confirms you're now tracking the known issue: %1\$s\n\nYou'll receive an update when this issue is resolved.\n\nIssue Details: %2\$s",
				'known-issues'
			),
			$post->post_title,
			$permalink
		);

		/**
		 * Filter the signup notification message.
		 *
		 * @param string   $message Message text.
		 * @param \WP_Post $post    Post object.
		 */
		return apply_filters( 'known_issues_signup_message', $message, $post );
	}

	/**
	 * Get resolution notification message.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string Message text.
	 */
	private static function get_resolution_message( $post ) {
		$permalink = get_permalink( $post );
		$status    = get_post_status( $post );

		$status_labels = [
			'closed'   => __( 'Closed', 'known-issues' ),
			'archived' => __( 'Archived', 'known-issues' ),
			'done'     => __( 'Done', 'known-issues' ),
		];

		$status_label = $status_labels[ $status ] ?? ucfirst( $status );

		$message = sprintf(
			/* translators: 1: Issue title, 2: Status label, 3: Permalink */
			__(
				"Good news! The known issue you were tracking has been resolved.\n\nIssue: %1\$s\nStatus: %2\$s\n\nView details: %3\$s",
				'known-issues'
			),
			$post->post_title,
			$status_label,
			$permalink
		);

		/**
		 * Filter the resolution notification message.
		 *
		 * @param string   $message Message text.
		 * @param \WP_Post $post    Post object.
		 */
		return apply_filters( 'known_issues_resolution_message', $message, $post );
	}
}
