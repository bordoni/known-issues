<?php
/**
 * HelpScout API client.
 *
 * @package KnownIssues
 */

namespace KnownIssues\HelpScout;

/**
 * Class ApiClient
 *
 * Wrapper for HelpScout Mailbox API 2.0.
 */
class ApiClient {
	/**
	 * API base URL.
	 *
	 * @var string
	 */
	const API_URL = 'https://api.helpscout.net/v2';

	/**
	 * Create a conversation.
	 *
	 * @param array $data Conversation data.
	 * @return array|false Response data or false on failure.
	 */
	public static function create_conversation( $data ) {
		return self::request( 'POST', '/conversations', $data );
	}

	/**
	 * Create a thread (reply) in a conversation.
	 *
	 * @param string $conversation_id Conversation ID.
	 * @param array  $data            Thread data.
	 * @return array|false Response data or false on failure.
	 */
	public static function create_thread( $conversation_id, $data ) {
		return self::request( 'POST', "/conversations/{$conversation_id}/threads", $data );
	}

	/**
	 * Get a conversation.
	 *
	 * @param string $conversation_id Conversation ID.
	 * @return array|false Response data or false on failure.
	 */
	public static function get_conversation( $conversation_id ) {
		return self::request( 'GET', "/conversations/{$conversation_id}" );
	}

	/**
	 * Update a conversation.
	 *
	 * @param string $conversation_id Conversation ID.
	 * @param array  $data            Conversation data.
	 * @return array|false Response data or false on failure.
	 */
	public static function update_conversation( $conversation_id, $data ) {
		return self::request( 'PATCH', "/conversations/{$conversation_id}", $data );
	}

	/**
	 * Make an API request.
	 *
	 * @param string $method   HTTP method (GET, POST, PATCH, etc.).
	 * @param string $endpoint API endpoint (e.g., '/conversations').
	 * @param array  $data     Request data.
	 * @return array|false Response data or false on failure.
	 */
	private static function request( $method, $endpoint, $data = [] ) {
		$token = OAuthHandler::get_access_token();

		if ( ! $token ) {
			\KnownIssues\log( 'Failed to get HelpScout access token', 'error' );
			return false;
		}

		$url = self::API_URL . $endpoint;

		$args = [
			'method'  => $method,
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			'timeout' => 30,
		];

		if ( ! empty( $data ) && in_array( $method, [ 'POST', 'PATCH', 'PUT' ], true ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		\KnownIssues\log( sprintf( 'HelpScout API request: %s %s', $method, $endpoint ), 'debug' );

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			\KnownIssues\log( sprintf( 'HelpScout API error: %s', $response->get_error_message() ), 'error' );
			return false;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		// Log the response status.
		\KnownIssues\log( sprintf( 'HelpScout API response: %d', $status ), 'debug' );

		// Handle rate limiting.
		if ( 429 === $status ) {
			$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
			\KnownIssues\log( sprintf( 'HelpScout API rate limited, retry after: %s seconds', $retry_after ), 'warning' );
			return false;
		}

		// Handle errors.
		if ( $status >= 400 ) {
			\KnownIssues\log( sprintf( 'HelpScout API error %d: %s', $status, $body ), 'error' );
			return false;
		}

		// Success - parse response.
		if ( ! empty( $body ) ) {
			$parsed = json_decode( $body, true );
			return $parsed ?: false;
		}

		// No content response (e.g., 204).
		return [];
	}

	/**
	 * Test API connection.
	 *
	 * @return bool True if connection successful, false otherwise.
	 */
	public static function test_connection() {
		$token = OAuthHandler::get_access_token();

		if ( ! $token ) {
			return false;
		}

		// Try to get mailboxes (simple test endpoint).
		$response = self::request( 'GET', '/mailboxes' );

		return false !== $response;
	}
}
