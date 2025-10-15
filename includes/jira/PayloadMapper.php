<?php
/**
 * Payload mapper for Jira webhook data to WordPress post data.
 *
 * @package KnownIssues
 */

namespace KnownIssues\Jira;

/**
 * Class PayloadMapper
 *
 * Maps Jira webhook payloads to WordPress post data.
 */
class PayloadMapper {
	/**
	 * Map Jira payload to WordPress post data.
	 *
	 * @param array $payload Jira webhook payload.
	 * @return array WordPress post data.
	 */
	public static function map( $payload ) {
		$issue = $payload['issue'] ?? [];
		$fields = $issue['fields'] ?? [];

		$mapped = [
			'external_id'   => $issue['key'] ?? '',
			'title'         => $fields['summary'] ?? '',
			'content'       => self::convert_description( $fields['description'] ?? '' ),
			'status'        => StatusMapper::map( $fields['status']['name'] ?? 'To Do' ),
			'event_type'    => $payload['webhookEvent'] ?? '',
			'project'       => $fields['project']['key'] ?? '',
			'issue_type'    => $fields['issuetype']['name'] ?? '',
			'priority'      => $fields['priority']['name'] ?? '',
		];

		/**
		 * Filter the mapped payload data.
		 *
		 * @param array $mapped  Mapped data.
		 * @param array $payload Original Jira payload.
		 */
		return apply_filters( 'known_issues_mapped_payload', $mapped, $payload );
	}

	/**
	 * Convert Jira description to WordPress content.
	 *
	 * @param string $description Jira description text.
	 * @return string WordPress post content.
	 */
	private static function convert_description( $description ) {
		if ( empty( $description ) ) {
			return '';
		}

		// Basic conversion - could be enhanced with more sophisticated markdown conversion.
		$content = $description;

		// Convert common Jira markdown to HTML.
		$content = str_replace( '\\n', "\n", $content );
		$content = wpautop( $content );

		/**
		 * Filter the converted description.
		 *
		 * @param string $content     Converted content.
		 * @param string $description Original description.
		 */
		return apply_filters( 'known_issues_converted_description', $content, $description );
	}

	/**
	 * Get post meta from payload.
	 *
	 * @param array $payload Jira webhook payload.
	 * @return array Post meta key-value pairs.
	 */
	public static function get_post_meta( $payload ) {
		$mapped = self::map( $payload );

		return [
			'_ki_external_id'     => $mapped['external_id'],
			'_ki_jira_event_type' => $mapped['event_type'],
			'_ki_jira_project'    => $mapped['project'],
			'_ki_jira_issue_type' => $mapped['issue_type'],
			'_ki_jira_priority'   => $mapped['priority'],
		];
	}
}
