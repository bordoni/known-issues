<?php
/**
 * Status mapper for Jira statuses to WordPress post statuses.
 *
 * @package KnownIssues
 */

namespace KnownIssues\Jira;

/**
 * Class StatusMapper
 *
 * Maps Jira issue statuses to WordPress post statuses.
 */
class StatusMapper {
	/**
	 * Map a Jira status to a WordPress post status.
	 *
	 * @param string $jira_status Jira status name.
	 * @return string WordPress post status.
	 */
	public static function map( $jira_status ) {
		$status_map = [
			'To Do'       => 'draft',
			'In Progress' => 'publish',
			'Done'        => 'done',
			'Closed'      => 'closed',
			'Archived'    => 'archived',
			'Open'        => 'publish',
			'Resolved'    => 'done',
			'Reopened'    => 'publish',
		];

		/**
		 * Filter the status mapping.
		 *
		 * @param array $status_map Map of Jira statuses to WordPress statuses.
		 */
		$status_map = apply_filters( 'known_issues_status_map', $status_map );

		// Return mapped status or default to publish.
		return $status_map[ $jira_status ] ?? 'publish';
	}

	/**
	 * Check if a status is considered "resolved".
	 *
	 * @param string $status WordPress post status.
	 * @return bool True if status is resolved, false otherwise.
	 */
	public static function is_resolved( $status ) {
		$resolved_statuses = [ 'closed', 'archived', 'done' ];

		/**
		 * Filter the resolved statuses.
		 *
		 * @param array $resolved_statuses List of statuses considered resolved.
		 */
		$resolved_statuses = apply_filters( 'known_issues_resolved_statuses', $resolved_statuses );

		return in_array( $status, $resolved_statuses, true );
	}
}
