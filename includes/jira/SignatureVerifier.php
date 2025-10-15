<?php
/**
 * HMAC Signature verification for Jira webhooks.
 *
 * @package KnownIssues
 */

namespace KnownIssues\Jira;

/**
 * Class SignatureVerifier
 *
 * Verifies HMAC signatures from Jira webhooks.
 */
class SignatureVerifier {
	/**
	 * Verify the HMAC signature from a Jira webhook.
	 *
	 * @param string $payload   Raw request payload.
	 * @param string $signature Signature from X-Hub-Signature header.
	 * @param string $secret    Shared secret for HMAC.
	 * @return bool True if signature is valid, false otherwise.
	 */
	public static function verify( $payload, $signature, $secret ) {
		if ( empty( $signature ) || empty( $secret ) ) {
			return false;
		}

		// Extract method and signature from header.
		// Format: "sha256=abc123..."
		$parts = explode( '=', $signature, 2 );

		if ( count( $parts ) !== 2 ) {
			return false;
		}

		list( $method, $provided_signature ) = $parts;

		// Calculate expected signature.
		$expected_signature = hash_hmac( $method, $payload, $secret );

		// Use timing-safe comparison to prevent timing attacks.
		return hash_equals( $expected_signature, $provided_signature );
	}

	/**
	 * Verify the URL secret parameter.
	 *
	 * @param string $provided_secret  Secret from query parameter.
	 * @param string $expected_secret  Expected secret from configuration.
	 * @return bool True if secrets match, false otherwise.
	 */
	public static function verify_url_secret( $provided_secret, $expected_secret ) {
		if ( empty( $provided_secret ) || empty( $expected_secret ) ) {
			return false;
		}

		return hash_equals( $expected_secret, $provided_secret );
	}
}
