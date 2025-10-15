<?php
/**
 * Server-side rendering for Affected Users block.
 *
 * @package KnownIssues
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

namespace KnownIssues;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the current post ID.
$post_id = get_the_ID();

if ( ! $post_id ) {
	return '';
}

// Get block attributes.
$show_count   = $attributes['showAffectedCount'] ?? false;
$allow_signup = $attributes['allowSignup'] ?? true;
$button_text  = $attributes['buttonText'] ?? __( "I'm affected by this issue", 'known-issues' );
$unsub_text   = $attributes['unsubscribeButtonText'] ?? __( 'Unsubscribe', 'known-issues' );

// Get affected user count.
$count = get_affected_user_count( $post_id );

// Check if current user is affected.
$is_logged_in  = is_user_logged_in();
$is_affected   = false;
$user_id       = 0;
$comment_id    = 0;

if ( $is_logged_in ) {
	$user_id     = get_current_user_id();
	$is_affected = is_user_affected( $post_id, $user_id );

	// Get the comment ID if user is affected.
	if ( $is_affected ) {
		$comments = get_comments(
			[
				'post_id' => $post_id,
				'user_id' => $user_id,
				'type'    => 'ki_affected_user',
				'status'  => [ 'pending', 'approved' ],
				'number'  => 1,
			]
		);

		if ( ! empty( $comments ) ) {
			$comment_id = $comments[0]->comment_ID;
		}
	}
}

// Get block wrapper attributes.
$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => 'known-issues-affected-users',
	]
);
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="known-issues-affected-users__content">
		<?php if ( $show_count ) : ?>
			<p class="known-issues-affected-users__count">
				<?php
				/* translators: %d: number of affected users */
				echo esc_html( sprintf( _n( '%d user affected', '%d users affected', $count, 'known-issues' ), $count ) );
				?>
			</p>
		<?php endif; ?>

		<?php if ( $allow_signup ) : ?>
			<?php if ( ! $is_logged_in ) : ?>
				<p class="known-issues-affected-users__login-message">
					<?php esc_html_e( 'Please log in to report being affected by this issue.', 'known-issues' ); ?>
				</p>
			<?php elseif ( $is_affected ) : ?>
				<div class="known-issues-affected-users__subscribed">
					<p class="known-issues-affected-users__message">
						<?php esc_html_e( "You're subscribed to updates for this issue.", 'known-issues' ); ?>
					</p>
					<button
						type="button"
						class="known-issues-affected-users__unsubscribe-button"
						data-post-id="<?php echo esc_attr( $post_id ); ?>"
						data-comment-id="<?php echo esc_attr( $comment_id ); ?>"
					>
						<?php echo esc_html( $unsub_text ); ?>
					</button>
				</div>
			<?php else : ?>
				<button
					type="button"
					class="known-issues-affected-users__button"
					data-post-id="<?php echo esc_attr( $post_id ); ?>"
				>
					<?php echo esc_html( $button_text ); ?>
				</button>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
