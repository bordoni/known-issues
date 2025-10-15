# Known Issues WordPress Plugin

Track and manage known issues synced from Jira, with automated HelpScout notifications for affected users.

## Features

- **Jira Webhook Integration** - Automatically sync issues from Jira with HMAC signature verification
- **User Sign-up System** - Let users track issues they're affected by
- **HelpScout Notifications** - Send automated emails when signing up and when issues are resolved
- **Custom Post Type** - Manage known issues with WordPress block editor
- **Custom Post Statuses** - Track issue lifecycle (open, closed, archived, done)
- **GDPR Compliant** - Full data export and erasure support
- **Batch Processing** - Queue-based notification system with WP-Cron and WP-CLI support

## Requirements

- WordPress 6.7+
- PHP 7.4+
- Jira account (for webhook integration)
- HelpScout account (for email notifications)

## Installation

1. Clone this repository or download as ZIP
2. Install dependencies:
   ```bash
   composer install --no-dev
   npm install
   npm run build
   ```
3. Activate the plugin in WordPress
4. Configure environment variables in `wp-config.php`
5. Set up Jira webhook and HelpScout OAuth

## Configuration

### Environment Variables

Add these constants to your `wp-config.php`:

```php
// Jira Webhook Security
define( 'KNOWN_ISSUES_WEBHOOK_SECRET', 'your-hmac-secret-here' );
define( 'KNOWN_ISSUES_WEBHOOK_URL_SECRET', 'your-url-secret-here' );

// HelpScout API (Phase 6 - Coming Soon)
define( 'KNOWN_ISSUES_HELPSCOUT_APP_ID', 'your-app-id' );
define( 'KNOWN_ISSUES_HELPSCOUT_APP_SECRET', 'your-app-secret' );
define( 'KNOWN_ISSUES_HELPSCOUT_MAILBOX_ID', 'your-mailbox-id' );

// Optional: Enable debug logging
define( 'KNOWN_ISSUES_DEBUG', true );
```

### Jira Webhook Setup

1. Go to your Jira project settings → Webhooks
2. Create a new webhook with:
   - **URL**: `https://yoursite.com/wp-json/known-issues/v1/webhooks/jira?secret=YOUR_URL_SECRET`
   - **Events**: Issue Created, Issue Updated, Issue Deleted
   - **Header**: `X-Hub-Signature: sha256={your-hmac-secret}`
3. Generate secure random strings for both secrets:
   ```bash
   openssl rand -hex 32
   ```

## REST API Endpoints

### Jira Webhook
- **POST** `/wp-json/known-issues/v1/webhooks/jira?secret={SECRET}`
  - Receives Jira webhook events
  - Requires HMAC signature verification
  - Creates/updates known issues

### Affected Users
- **POST** `/wp-json/known-issues/v1/affected-users`
  - Sign up current user as affected
  - Requires authentication
- **DELETE** `/wp-json/known-issues/v1/affected-users/{comment_id}`
  - Unsubscribe from issue updates
  - Requires authentication
- **GET** `/wp-json/known-issues/v1/affected-users/list/{post_id}`
  - Get list of affected users (admin only)

## Block Usage

The plugin includes an "Affected Users" block that can be added to any known issue post:

1. Edit a known issue post
2. Add the "Affected Users" block
3. Configure settings:
   - Toggle "Show affected user count"
   - Toggle "Allow users to sign up"
   - Customize button text
4. Publish

The block displays differently based on user state:
- **Logged out**: Shows login message
- **Logged in (not affected)**: Shows signup button
- **Logged in (affected)**: Shows subscription status and unsubscribe button

## Development

### Build Process

```bash
# Development build with watch mode
npm run start

# Production build
npm run build

# Run tests (coming soon)
npm run test:js
composer test

# Code quality
npm run lint:js
composer phpcs
```

### Project Structure

```
known-issues/
├── includes/           # PHP classes
│   ├── jira/          # Jira webhook integration
│   ├── helpscout/     # HelpScout integration (Phase 6)
│   ├── rest-api/      # REST API controllers
│   └── privacy/       # GDPR compliance (Phase 8)
├── src/               # Block source files
│   └── affected-users/
├── build/             # Compiled assets
├── assets/            # Additional assets
│   ├── css/
│   └── js/
└── tests/             # Unit and integration tests
```

## Security

### Webhook Security
- **Two-layer verification**: URL secret + HMAC signature
- **Timing-safe comparison**: Prevents timing attacks
- **HTTPS enforcement**: Recommended for production

### Data Protection
- All user input is sanitized
- All output is escaped
- Nonce verification on all forms
- Capability checks on admin functions

## Roadmap

- [x] Phase 1-2: Core foundation
- [x] Phase 3: Jira webhook integration
- [x] Phase 4: Custom block development
- [x] Phase 5: REST API for affected users
- [ ] Phase 6: HelpScout integration
- [ ] Phase 7: Admin interface enhancements
- [ ] Phase 8: Privacy & GDPR compliance
- [ ] Phase 9: Testing & documentation
- [ ] Phase 10: GitHub Actions & CI/CD

## Support

For issues and feature requests, please use the [GitHub issue tracker](https://github.com/bordoni/known-issues/issues).

## License

GPL v2 or later

## Credits

Developed by [Gustavo Bordoni](https://github.com/bordoni)
