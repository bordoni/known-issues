# Changelog

All notable changes to the Known Issues plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-10-14

### Added
- Initial release of Known Issues plugin
- Custom post type `known-issues` with block editor support
- Custom post statuses: closed, archived, done
- Jira webhook integration with HMAC signature verification
- Affected Users block for user signup and subscription management
- REST API endpoints for affected users management
- Frontend JavaScript for signup/unsubscribe functionality
- Post and comment meta fields for Jira and HelpScout data
- Helper functions for affected user counts and Jira URLs
- Comprehensive security with two-layer webhook verification
- Status mapping between Jira and WordPress
- Payload history storage for webhook events
- Auto-queue resolution notifications when issues are resolved
- Testing infrastructure with PHPUnit
- Complete documentation in README.md

### Security
- HMAC signature verification for Jira webhooks
- URL secret parameter validation
- Timing-safe comparison to prevent timing attacks
- Nonce verification on all REST API requests
- Capability checks for admin functions
- Input sanitization and output escaping throughout

### Developer Experience
- Composer autoloading with PSR-4
- WordPress coding standards with PHPCS
- Modern build process with @wordpress/scripts
- wp-env configuration for local development
- PHPUnit testing setup
- Pup configuration for plugin packaging

## [Unreleased]

### Planned for Phase 6
- HelpScout OAuth integration
- Batch notification processor with WP-Cron
- Queue management for signup and resolution notifications
- WP-CLI commands for queue processing
- Retry logic with exponential backoff

### Planned for Phase 7
- Admin interface enhancements
- Custom admin columns with affected user counts
- Affected users modal for detailed view
- Dashboard widget with statistics
- Settings page for configuration
- Bulk actions for export and status changes

### Planned for Phase 8
- GDPR personal data exporter
- GDPR personal data eraser
- Privacy policy content suggestions
- Data retention options

### Planned for Phase 9-10
- Comprehensive unit and integration tests
- GitHub Actions CI/CD workflows
- Automated testing on push/PR
- Release workflow with plugin ZIP building
