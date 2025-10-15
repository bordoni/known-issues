<?php
/**
 * REST API Controller for Affected Users.
 *
 * @package KnownIssues
 */

namespace KnownIssues\RestApi;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class AffectedUsersController
 *
 * Handles REST API endpoints for affected users.
 */
class AffectedUsersController extends WP_REST_Controller {
	/**
	 * Register routes for this controller.
	 *
	 * @return void
	 */
	public function register_routes() {
		// Sign up as affected user.
		register_rest_route(
			RestInit::NAMESPACE,
			'/affected-users',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'signup' ],
					'permission_callback' => [ $this, 'signup_permissions_check' ],
					'args'                => $this->get_signup_params(),
				],
			]
		);

		// Unsubscribe from issue.
		register_rest_route(
			RestInit::NAMESPACE,
			'/affected-users/(?P<comment_id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'unsubscribe' ],
					'permission_callback' => [ $this, 'unsubscribe_permissions_check' ],
					'args'                => [
						'comment_id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// Get affected users list (admin only).
		register_rest_route(
			RestInit::NAMESPACE,
			'/affected-users/list/(?P<post_id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_list' ],
					'permission_callback' => [ $this, 'list_permissions_check' ],
					'args'                => [
						'post_id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);
	}

	/**
	 * Get signup endpoint parameters.
	 *
	 * @return array
	 */
	protected function get_signup_params() {
		return [
			'post_id' => [
				'required'          => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => function( $param ) {
					return is_numeric( $param ) && $param > 0;
				},
			],
		];
	}

	/**
	 * Check permissions for signup.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function signup_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to sign up as affected.', 'known-issues' ),
				[ 'status' => 401 ]
			);
		}

		return true;
	}

	/**
	 * Sign up user as affected.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function signup( $request ) {
		$post_id = $request->get_param( 'post_id' );
		$user_id = get_current_user_id();

		// Verify post exists and is a known issue.
		$post = get_post( $post_id );
		if ( ! $post || 'known-issues' !== $post->post_type ) {
			return new WP_Error(
				'invalid_post',
				__( 'Invalid known issue.', 'known-issues' ),
				[ 'status' => 404 ]
			);
		}

		// Check if user already signed up.
		$existing = get_comments(
			[
				'post_id' => $post_id,
				'user_id' => $user_id,
				'type'    => 'ki_affected_user',
				'status'  => [ 'pending', 'approved' ],
				'number'  => 1,
			]
		);

		if ( ! empty( $existing ) ) {
			return new WP_Error(
				'already_signed_up',
				__( 'You are already signed up for this issue.', 'known-issues' ),
				[ 'status' => 400 ]
			);
		}

		// Create affected user comment.
		$user = wp_get_current_user();

		$comment_id = wp_insert_comment(
			[
				'comment_post_ID'      => $post_id,
				'comment_author'       => $user->display_name,
				'comment_author_email' => $user->user_email,
				'comment_content'      => '',
				'comment_type'         => 'ki_affected_user',
				'comment_approved'     => 'pending',
				'user_id'              => $user_id,
			]
		);

		if ( ! $comment_id || is_wp_error( $comment_id ) ) {
			return new WP_Error(
				'signup_failed',
				__( 'Failed to sign up for issue.', 'known-issues' ),
				[ 'status' => 500 ]
			);
		}

		// Add comment meta.
		add_comment_meta( $comment_id, '_ki_signup_date', current_time( 'mysql' ) );
		add_comment_meta( $comment_id, '_ki_helpscout_status', 'pending_notification' );

		// Queue HelpScout notification.
		$this->queue_signup_notification( $comment_id, $post_id );

		// Get updated count.
		$count = \KnownIssues\get_affected_user_count( $post_id );

		return new WP_REST_Response(
			[
				'success'    => true,
				'comment_id' => $comment_id,
				'count'      => $count,
				'message'    => __( 'You have been signed up to receive updates about this issue.', 'known-issues' ),
			],
			201
		);
	}

	/**
	 * Check permissions for unsubscribe.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function unsubscribe_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to unsubscribe.', 'known-issues' ),
				[ 'status' => 401 ]
			);
		}

		$comment_id = $request->get_param( 'comment_id' );
		$comment    = get_comment( $comment_id );

		if ( ! $comment || 'ki_affected_user' !== $comment->comment_type ) {
			return new WP_Error(
				'invalid_comment',
				__( 'Invalid subscription.', 'known-issues' ),
				[ 'status' => 404 ]
			);
		}

		// Check if user owns this comment or is admin.
		if ( (int) $comment->user_id !== get_current_user_id() && ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to unsubscribe this user.', 'known-issues' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Unsubscribe user from issue.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function unsubscribe( $request ) {
		$comment_id = $request->get_param( 'comment_id' );
		$comment    = get_comment( $comment_id );

		if ( ! $comment ) {
			return new WP_Error(
				'invalid_comment',
				__( 'Invalid subscription.', 'known-issues' ),
				[ 'status' => 404 ]
			);
		}

		$post_id = $comment->comment_post_ID;

		// Remove from HelpScout queue.
		$this->remove_from_queue( $comment_id );

		// Delete the comment.
		$deleted = wp_delete_comment( $comment_id, true );

		if ( ! $deleted ) {
			return new WP_Error(
				'unsubscribe_failed',
				__( 'Failed to unsubscribe.', 'known-issues' ),
				[ 'status' => 500 ]
			);
		}

		// Get updated count.
		$count = \KnownIssues\get_affected_user_count( $post_id );

		return new WP_REST_Response(
			[
				'success' => true,
				'count'   => $count,
				'message' => __( 'You have been unsubscribed from this issue.', 'known-issues' ),
			]
		);
	}

	/**
	 * Check permissions for list endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function list_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view this list.', 'known-issues' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Get list of affected users.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_list( $request ) {
		$post_id = $request->get_param( 'post_id' );

		$comments = get_comments(
			[
				'post_id' => $post_id,
				'type'    => 'ki_affected_user',
				'status'  => [ 'pending', 'approved' ],
			]
		);

		$users = array_map(
			function( $comment ) {
				return [
					'id'                   => $comment->comment_ID,
					'user_id'              => $comment->user_id,
					'name'                 => $comment->comment_author,
					'email'                => $comment->comment_author_email,
					'signup_date'          => get_comment_meta( $comment->comment_ID, '_ki_signup_date', true ),
					'helpscout_status'     => get_comment_meta( $comment->comment_ID, '_ki_helpscout_status', true ),
					'conversation_id'      => get_comment_meta( $comment->comment_ID, '_ki_helpscout_conversation_id', true ),
				];
			},
			$comments
		);

		return new WP_REST_Response(
			[
				'users' => $users,
				'total' => count( $users ),
			]
		);
	}

	/**
	 * Queue a signup notification for HelpScout.
	 *
	 * @param int $comment_id Comment ID.
	 * @param int $post_id    Post ID.
	 * @return void
	 */
	private function queue_signup_notification( $comment_id, $post_id ) {
		$queue = get_option( '_ki_helpscout_queue_signup', [] );

		$comment = get_comment( $comment_id );

		$queue[] = [
			'comment_id'  => $comment_id,
			'post_id'     => $post_id,
			'user_email'  => $comment->comment_author_email,
			'retry_count' => 0,
			'next_retry'  => time(),
		];

		update_option( '_ki_helpscout_queue_signup', $queue );
	}

	/**
	 * Remove comment from HelpScout queues.
	 *
	 * @param int $comment_id Comment ID.
	 * @return void
	 */
	private function remove_from_queue( $comment_id ) {
		// Remove from signup queue.
		$signup_queue = get_option( '_ki_helpscout_queue_signup', [] );
		$signup_queue = array_filter(
			$signup_queue,
			function( $item ) use ( $comment_id ) {
				return (int) $item['comment_id'] !== (int) $comment_id;
			}
		);
		update_option( '_ki_helpscout_queue_signup', array_values( $signup_queue ) );

		// Remove from resolved queue.
		$resolved_queue = get_option( '_ki_helpscout_queue_resolved', [] );
		$resolved_queue = array_filter(
			$resolved_queue,
			function( $item ) use ( $comment_id ) {
				return (int) $item['comment_id'] !== (int) $comment_id;
			}
		);
		update_option( '_ki_helpscout_queue_resolved', array_values( $resolved_queue ) );
	}
}
