<?php
/**
 * Bootstrap file for Known Issues plugin.
 *
 * @package KnownIssues
 */

namespace KnownIssues;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Autoload classes using Composer.
if ( file_exists( KNOWN_ISSUES_DIR . 'vendor/autoload.php' ) ) {
	require_once KNOWN_ISSUES_DIR . 'vendor/autoload.php';
}
