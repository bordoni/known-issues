<?php
/**
 * Helper functions for Known Issues plugin.
 *
 * @package KnownIssues
 */

namespace KnownIssues;

/**
 * Get the affected user count for a known issue.
 *
 * @param int $post_id Post ID.
 * @return int Number of affected users.
 */
function get_affected_user_count( $post_id ) {
	$count = get_comments(
		[
			'post_id' => $post_id,
			'type'    => 'ki_affected_user',
			'status'  => [ 'pending', 'approved' ],
			'count'   => true,
		]
	);

	return (int) $count;
}

/**
 * Check if a user is affected by a known issue.
 *
 * @param int $post_id Post ID.
 * @param int $user_id User ID. Default is current user.
 * @return bool True if user is affected, false otherwise.
 */
function is_user_affected( $post_id, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( ! $user_id ) {
		return false;
	}

	$comments = get_comments(
		[
			'post_id' => $post_id,
			'user_id' => $user_id,
			'type'    => 'ki_affected_user',
			'status'  => [ 'pending', 'approved' ],
			'number'  => 1,
		]
	);

	return ! empty( $comments );
}

/**
 * Get the Jira issue key for a known issue.
 *
 * @param int $post_id Post ID.
 * @return string|false Jira issue key or false if not found.
 */
function get_jira_issue_key( $post_id ) {
	return get_post_meta( $post_id, '_ki_external_id', true ) ?: false;
}

/**
 * Get the Jira issue URL for a known issue.
 *
 * @param int    $post_id Post ID.
 * @param string $base_url Jira base URL. Default is from settings.
 * @return string|false Jira issue URL or false if not found.
 */
function get_jira_issue_url( $post_id, $base_url = '' ) {
	$issue_key = get_jira_issue_key( $post_id );

	if ( ! $issue_key ) {
		return false;
	}

	if ( ! $base_url ) {
		$base_url = 'https://jira.atlassian.com'; // Default, should come from settings.
	}

	return trailingslashit( $base_url ) . 'browse/' . $issue_key;
}

/**
 * Log a message for debugging.
 *
 * @param string $message Log message.
 * @param string $level Log level (error, warning, info, debug).
 * @return void
 */
function log( $message, $level = 'info' ) {
	if ( ! defined( 'KNOWN_ISSUES_DEBUG' ) || ! KNOWN_ISSUES_DEBUG ) {
		return;
	}

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( sprintf( '[Known Issues] [%s] %s', strtoupper( $level ), $message ) );
}
