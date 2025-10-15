<?php
/**
 * Batch processor for HelpScout notifications.
 *
 * @package KnownIssues
 */

namespace KnownIssues\HelpScout;

/**
 * Class BatchProcessor
 *
 * Processes notification queues in batches.
 */
class BatchProcessor {
	/**
	 * Default batch size.
	 *
	 * @var int
	 */
	const DEFAULT_BATCH_SIZE = 10;

	/**
	 * Initialize the batch processor.
	 *
	 * @return void
	 */
	public static function init() {
		// Register WP-Cron hook.
		add_action( 'ki_process_helpscout_queue', [ __CLASS__, 'process_queues' ] );

		// Schedule cron event if not already scheduled.
		if ( ! wp_next_scheduled( 'ki_process_helpscout_queue' ) ) {
			wp_schedule_event( time(), 'ki_five_minutes', 'ki_process_helpscout_queue' );
		}

		// Register custom cron interval.
		add_filter( 'cron_schedules', [ __CLASS__, 'add_cron_interval' ] );
	}

	/**
	 * Add custom cron interval.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array Modified schedules.
	 */
	public static function add_cron_interval( $schedules ) {
		$schedules['ki_five_minutes'] = [
			'interval' => 300,
			'display'  => __( 'Every 5 Minutes', 'known-issues' ),
		];

		return $schedules;
	}

	/**
	 * Process both signup and resolution queues.
	 *
	 * @param int $batch_size Batch size.
	 * @return array Processing results.
	 */
	public static function process_queues( $batch_size = null ) {
		if ( null === $batch_size ) {
			$batch_size = self::DEFAULT_BATCH_SIZE;
		}

		\KnownIssues\log( 'Starting HelpScout queue processing', 'info' );

		$results = [
			'signup'   => self::process_signup_queue( $batch_size ),
			'resolved' => self::process_resolved_queue( $batch_size ),
		];

		\KnownIssues\log( sprintf( 'Queue processing complete: %d signup, %d resolved', $results['signup']['processed'], $results['resolved']['processed'] ), 'info' );

		return $results;
	}

	/**
	 * Process signup queue.
	 *
	 * @param int $batch_size Batch size.
	 * @return array Processing results.
	 */
	public static function process_signup_queue( $batch_size = null ) {
		if ( null === $batch_size ) {
			$batch_size = self::DEFAULT_BATCH_SIZE;
		}

		$batch = QueueManager::get_next_batch( 'signup', $batch_size );

		if ( empty( $batch ) ) {
			return [
				'processed' => 0,
				'success'   => 0,
				'failed'    => 0,
			];
		}

		$success = 0;
		$failed  = 0;

		foreach ( $batch as $index => $item ) {
			$comment_id = $item['comment_id'] ?? 0;
			$post_id    = $item['post_id'] ?? 0;

			if ( ! $comment_id || ! $post_id ) {
				QueueManager::mark_failed( 'signup', $index, 'Missing comment or post ID' );
				$failed++;
				continue;
			}

			// Send notification.
			$result = NotificationHandler::send_signup_notification( $comment_id, $post_id );

			if ( $result ) {
				QueueManager::mark_processed( 'signup', $index );
				$success++;
			} else {
				QueueManager::mark_failed( 'signup', $index, 'Notification send failed' );
				$failed++;
			}

			// Small delay to respect rate limits.
			usleep( 100000 ); // 0.1 second.
		}

		return [
			'processed' => count( $batch ),
			'success'   => $success,
			'failed'    => $failed,
		];
	}

	/**
	 * Process resolved queue.
	 *
	 * @param int $batch_size Batch size.
	 * @return array Processing results.
	 */
	public static function process_resolved_queue( $batch_size = null ) {
		if ( null === $batch_size ) {
			$batch_size = self::DEFAULT_BATCH_SIZE;
		}

		$batch = QueueManager::get_next_batch( 'resolved', $batch_size );

		if ( empty( $batch ) ) {
			return [
				'processed' => 0,
				'success'   => 0,
				'failed'    => 0,
			];
		}

		$success = 0;
		$failed  = 0;

		foreach ( $batch as $index => $item ) {
			$comment_id      = $item['comment_id'] ?? 0;
			$post_id         = $item['post_id'] ?? 0;
			$conversation_id = $item['conversation_id'] ?? '';

			if ( ! $comment_id || ! $post_id || ! $conversation_id ) {
				QueueManager::mark_failed( 'resolved', $index, 'Missing required data' );
				$failed++;
				continue;
			}

			// Send notification.
			$result = NotificationHandler::send_resolution_notification( $comment_id, $post_id, $conversation_id );

			if ( $result ) {
				QueueManager::mark_processed( 'resolved', $index );
				$success++;
			} else {
				QueueManager::mark_failed( 'resolved', $index, 'Notification send failed' );
				$failed++;
			}

			// Small delay to respect rate limits.
			usleep( 100000 ); // 0.1 second.
		}

		return [
			'processed' => count( $batch ),
			'success'   => $success,
			'failed'    => $failed,
		];
	}

	/**
	 * Unregister the cron event.
	 *
	 * @return void
	 */
	public static function unregister() {
		$timestamp = wp_next_scheduled( 'ki_process_helpscout_queue' );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'ki_process_helpscout_queue' );
		}
	}
}
