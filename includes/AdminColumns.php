<?php
/**
 * Admin columns customization.
 *
 * @package KnownIssues
 */

namespace KnownIssues;

/**
 * Class AdminColumns
 *
 * Customizes admin list table columns for known issues.
 */
class AdminColumns {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'manage_known-issues_posts_columns', [ $this, 'add_columns' ] );
		add_action( 'manage_known-issues_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
		add_filter( 'manage_edit-known-issues_sortable_columns', [ $this, 'sortable_columns' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Add custom columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_columns( $columns ) {
		// Remove date column temporarily.
		$date = $columns['date'];
		unset( $columns['date'] );

		// Add custom columns.
		$columns['jira_issue']      = __( 'Jira Issue', 'known-issues' );
		$columns['affected_users']  = __( 'Affected Users', 'known-issues' );
		$columns['post_status']     = __( 'Status', 'known-issues' );

		// Re-add date column at the end.
		$columns['date'] = $date;

		return $columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'jira_issue':
				$this->render_jira_column( $post_id );
				break;

			case 'affected_users':
				$this->render_affected_users_column( $post_id );
				break;

			case 'post_status':
				$this->render_status_column( $post_id );
				break;
		}
	}

	/**
	 * Render Jira issue column.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_jira_column( $post_id ) {
		$issue_key = get_jira_issue_key( $post_id );

		if ( ! $issue_key ) {
			echo '<span class="dashicons dashicons-minus" style="color:#999;"></span>';
			return;
		}

		$issue_url = get_jira_issue_url( $post_id );

		if ( $issue_url ) {
			printf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s <span class="dashicons dashicons-external" style="font-size:12px;"></span></a>',
				esc_url( $issue_url ),
				esc_html( $issue_key )
			);
		} else {
			echo esc_html( $issue_key );
		}
	}

	/**
	 * Render affected users column.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_affected_users_column( $post_id ) {
		$count = get_affected_user_count( $post_id );

		if ( 0 === $count ) {
			echo '<span style="color:#999;">0</span>';
			return;
		}

		printf(
			'<button type="button" class="button-link ki-affected-count" data-post-id="%d" title="%s">
				<span class="dashicons dashicons-groups"></span> %d
			</button>',
			esc_attr( $post_id ),
			esc_attr__( 'Click to view affected users', 'known-issues' ),
			(int) $count
		);
	}

	/**
	 * Render status column.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_status_column( $post_id ) {
		$status = get_post_status( $post_id );

		$status_labels = [
			'publish'  => [ 'label' => __( 'Open', 'known-issues' ), 'color' => '#2271b1' ],
			'draft'    => [ 'label' => __( 'Draft', 'known-issues' ), 'color' => '#999' ],
			'closed'   => [ 'label' => __( 'Closed', 'known-issues' ), 'color' => '#50575e' ],
			'archived' => [ 'label' => __( 'Archived', 'known-issues' ), 'color' => '#999' ],
			'done'     => [ 'label' => __( 'Done', 'known-issues' ), 'color' => '#00a32a' ],
		];

		$info = $status_labels[ $status ] ?? [
			'label' => ucfirst( $status ),
			'color' => '#2271b1',
		];

		printf(
			'<span class="ki-status-badge" style="display:inline-block;padding:3px 8px;background:%s;color:#fff;border-radius:3px;font-size:11px;font-weight:600;">%s</span>',
			esc_attr( $info['color'] ),
			esc_html( $info['label'] )
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @param array $columns Existing sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_columns( $columns ) {
		$columns['affected_users'] = 'affected_users';
		$columns['post_status']    = 'post_status';

		return $columns;
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( 'edit.php' !== $hook || ! isset( $_GET['post_type'] ) || 'known-issues' !== $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		wp_enqueue_style(
			'known-issues-admin',
			KNOWN_ISSUES_URL . 'assets/css/admin.css',
			[],
			KNOWN_ISSUES_VERSION
		);

		wp_enqueue_script(
			'known-issues-admin',
			KNOWN_ISSUES_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			KNOWN_ISSUES_VERSION,
			true
		);

		wp_localize_script(
			'known-issues-admin',
			'kiAdmin',
			[
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'restUrl' => rest_url( 'known-issues/v1' ),
			]
		);
	}
}
