<?php
/**
 * Custom Post Status registration.
 *
 * @package KnownIssues
 */

namespace KnownIssues;

/**
 * Class PostStatus
 *
 * Registers custom post statuses for known issues.
 */
class PostStatus {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_statuses' ] );
		add_action( 'admin_footer-post.php', [ $this, 'add_statuses_to_dropdown' ] );
		add_action( 'admin_footer-post-new.php', [ $this, 'add_statuses_to_dropdown' ] );
	}

	/**
	 * Register custom post statuses.
	 *
	 * @return void
	 */
	public function register_statuses() {
		// Closed status.
		register_post_status(
			'closed',
			[
				'label'                     => _x( 'Closed', 'post status', 'known-issues' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'Closed <span class="count">(%s)</span>',
					'Closed <span class="count">(%s)</span>',
					'known-issues'
				),
			]
		);

		// Archived status.
		register_post_status(
			'archived',
			[
				'label'                     => _x( 'Archived', 'post status', 'known-issues' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'Archived <span class="count">(%s)</span>',
					'Archived <span class="count">(%s)</span>',
					'known-issues'
				),
			]
		);

		// Done status.
		register_post_status(
			'done',
			[
				'label'                     => _x( 'Done', 'post status', 'known-issues' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'Done <span class="count">(%s)</span>',
					'Done <span class="count">(%s)</span>',
					'known-issues'
				),
			]
		);
	}

	/**
	 * Add custom statuses to the post status dropdown in the editor.
	 *
	 * @return void
	 */
	public function add_statuses_to_dropdown() {
		global $post;

		if ( ! $post || PostType::POST_TYPE !== $post->post_type ) {
			return;
		}

		$statuses = [
			'closed'   => __( 'Closed', 'known-issues' ),
			'archived' => __( 'Archived', 'known-issues' ),
			'done'     => __( 'Done', 'known-issues' ),
		];

		?>
		<script>
		(function($) {
			$(document).ready(function() {
				// Add custom statuses to the status dropdown
				const statuses = <?php echo wp_json_encode( $statuses ); ?>;
				const currentStatus = '<?php echo esc_js( $post->post_status ); ?>';

				// Find the post status select element
				const $statusSelect = $('#post_status');

				if ($statusSelect.length) {
					// Add custom statuses to the dropdown
					Object.keys(statuses).forEach(function(status) {
						const selected = currentStatus === status ? ' selected="selected"' : '';
						$statusSelect.append(
							'<option value="' + status + '"' + selected + '>' +
							statuses[status] +
							'</option>'
						);
					});
				}

				// Update the status display text
				if (statuses[currentStatus]) {
					$('#post-status-display').text(statuses[currentStatus]);
				}
			});
		})(jQuery);
		</script>
		<?php
	}
}
