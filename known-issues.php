<?php
/**
 * Plugin Name: Known Issues
 * Plugin URI: https://github.com/bordoni/known-issues
 * Description: Track and manage known issues synced from Jira, with HelpScout integration for automated user notifications.
 * Version: 1.0.0
 * Author: Gustavo Bordoni
 * Author URI: https://github.com/bordoni
 * Text Domain: known-issues
 * Domain Path: /languages
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package KnownIssues
 */

namespace KnownIssues;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'KNOWN_ISSUES_VERSION', '1.0.0' );
define( 'KNOWN_ISSUES_FILE', __FILE__ );
define( 'KNOWN_ISSUES_DIR', plugin_dir_path( __FILE__ ) );
define( 'KNOWN_ISSUES_URL', plugin_dir_url( __FILE__ ) );
define( 'KNOWN_ISSUES_BASENAME', plugin_basename( __FILE__ ) );

// Require Composer autoloader.
if ( file_exists( KNOWN_ISSUES_DIR . 'vendor/autoload.php' ) ) {
	require_once KNOWN_ISSUES_DIR . 'vendor/autoload.php';
}

// Bootstrap the plugin.
require_once KNOWN_ISSUES_DIR . 'includes/bootstrap.php';

/**
 * Initialize the plugin.
 *
 * @return void
 */
function init() {
	Plugin::instance();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );

/**
 * Activation hook.
 *
 * @return void
 */
function activate() {
	// Flush rewrite rules on activation.
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );

/**
 * Deactivation hook.
 *
 * @return void
 */
function deactivate() {
	// Flush rewrite rules on deactivation.
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate' );
