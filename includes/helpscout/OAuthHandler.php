<?php
/**
 * OAuth handler for HelpScout API.
 *
 * @package KnownIssues
 */

namespace KnownIssues\HelpScout;

/**
 * Class OAuthHandler
 *
 * Manages OAuth 2.0 authentication for HelpScout API.
 */
class OAuthHandler {
	/**
	 * OAuth token URL.
	 *
	 * @var string
	 */
	const TOKEN_URL = 'https://api.helpscout.net/v2/oauth2/token';

	/**
	 * Get a valid access token.
	 *
	 * @return string|false Access token or false on failure.
	 */
	public static function get_access_token() {
		// Check if we have a valid token.
		$token = get_option( 'known_issues_helpscout_token' );
		$expiry = get_option( 'known_issues_helpscout_token_expiry' );

		if ( $token && $expiry && time() < $expiry ) {
			return $token;
		}

		// Token expired or doesn't exist, get a new one.
		return self::refresh_token();
	}

	/**
	 * Refresh the access token.
	 *
	 * @return string|false New access token or false on failure.
	 */
	public static function refresh_token() {
		$app_id = defined( 'KNOWN_ISSUES_HELPSCOUT_APP_ID' ) ? KNOWN_ISSUES_HELPSCOUT_APP_ID : '';
		$app_secret = defined( 'KNOWN_ISSUES_HELPSCOUT_APP_SECRET' ) ? KNOWN_ISSUES_HELPSCOUT_APP_SECRET : '';

		if ( empty( $app_id ) || empty( $app_secret ) ) {
			\KnownIssues\log( 'HelpScout credentials not configured', 'error' );
			return false;
		}

		$response = wp_remote_post(
			self::TOKEN_URL,
			[
				'body' => [
					'grant_type'    => 'client_credentials',
					'client_id'     => $app_id,
					'client_secret' => $app_secret,
				],
				'timeout' => 30,
			]
		);

		if ( is_wp_error( $response ) ) {
			\KnownIssues\log( sprintf( 'HelpScout OAuth error: %s', $response->get_error_message() ), 'error' );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) ) {
			\KnownIssues\log( 'HelpScout OAuth response missing access_token', 'error' );
			return false;
		}

		// Store the token.
		$token = $body['access_token'];
		$expires_in = $body['expires_in'] ?? 7200; // Default to 2 hours.
		$expiry = time() + $expires_in - 300; // Subtract 5 minutes for buffer.

		update_option( 'known_issues_helpscout_token', $token );
		update_option( 'known_issues_helpscout_token_expiry', $expiry );

		\KnownIssues\log( 'HelpScout access token refreshed', 'info' );

		return $token;
	}

	/**
	 * Clear stored tokens.
	 *
	 * @return void
	 */
	public static function clear_tokens() {
		delete_option( 'known_issues_helpscout_token' );
		delete_option( 'known_issues_helpscout_token_expiry' );
	}
}
