<?php
/**
 * Queue manager for HelpScout notifications.
 *
 * @package KnownIssues
 */

namespace KnownIssues\HelpScout;

/**
 * Class QueueManager
 *
 * Manages notification queues for batch processing.
 */
class QueueManager {
	/**
	 * Signup queue option name.
	 *
	 * @var string
	 */
	const SIGNUP_QUEUE = '_ki_helpscout_queue_signup';

	/**
	 * Resolution queue option name.
	 *
	 * @var string
	 */
	const RESOLVED_QUEUE = '_ki_helpscout_queue_resolved';

	/**
	 * Failed queue option name.
	 *
	 * @var string
	 */
	const FAILED_QUEUE = '_ki_helpscout_failed_queue';

	/**
	 * Maximum retry attempts.
	 *
	 * @var int
	 */
	const MAX_RETRIES = 5;

	/**
	 * Get next batch of items from a queue.
	 *
	 * @param string $queue_type Queue type (signup or resolved).
	 * @param int    $batch_size Number of items to get.
	 * @return array Queue items.
	 */
	public static function get_next_batch( $queue_type, $batch_size = 10 ) {
		$queue_option = self::get_queue_option( $queue_type );
		$queue        = get_option( $queue_option, [] );

		if ( empty( $queue ) ) {
			return [];
		}

		// Filter items ready for processing (past next_retry time).
		$ready = array_filter(
			$queue,
			function( $item ) {
				return isset( $item['next_retry'] ) && $item['next_retry'] <= time();
			}
		);

		// Get batch.
		$batch = array_slice( $ready, 0, $batch_size, true );

		return $batch;
	}

	/**
	 * Mark an item as processed successfully.
	 *
	 * @param string $queue_type Queue type.
	 * @param int    $index      Item index in queue.
	 * @return void
	 */
	public static function mark_processed( $queue_type, $index ) {
		$queue_option = self::get_queue_option( $queue_type );
		$queue        = get_option( $queue_option, [] );

		if ( isset( $queue[ $index ] ) ) {
			unset( $queue[ $index ] );
			update_option( $queue_option, array_values( $queue ) );

			\KnownIssues\log( sprintf( 'Removed item %d from %s queue', $index, $queue_type ), 'debug' );
		}
	}

	/**
	 * Mark an item as failed and retry or move to failed queue.
	 *
	 * @param string $queue_type Queue type.
	 * @param int    $index      Item index in queue.
	 * @param string $error      Error message.
	 * @return void
	 */
	public static function mark_failed( $queue_type, $index, $error = '' ) {
		$queue_option = self::get_queue_option( $queue_type );
		$queue        = get_option( $queue_option, [] );

		if ( ! isset( $queue[ $index ] ) ) {
			return;
		}

		$item = $queue[ $index ];
		$item['retry_count'] = ( $item['retry_count'] ?? 0 ) + 1;
		$item['last_error']  = $error;

		// Check if max retries exceeded.
		if ( $item['retry_count'] > self::MAX_RETRIES ) {
			// Move to failed queue.
			self::add_to_failed_queue( $item, $queue_type );

			// Remove from main queue.
			unset( $queue[ $index ] );
			update_option( $queue_option, array_values( $queue ) );

			\KnownIssues\log( sprintf( 'Moved item to failed queue after %d retries', self::MAX_RETRIES ), 'warning' );
		} else {
			// Calculate next retry time with exponential backoff.
			$backoff_minutes = [ 5, 15, 30, 60, 120 ];
			$backoff_index   = min( $item['retry_count'] - 1, count( $backoff_minutes ) - 1 );
			$item['next_retry'] = time() + ( $backoff_minutes[ $backoff_index ] * 60 );

			// Update queue.
			$queue[ $index ] = $item;
			update_option( $queue_option, $queue );

			\KnownIssues\log( sprintf( 'Scheduled retry %d for item in %d minutes', $item['retry_count'], $backoff_minutes[ $backoff_index ] ), 'info' );
		}
	}

	/**
	 * Add an item to the failed queue.
	 *
	 * @param array  $item       Queue item.
	 * @param string $queue_type Original queue type.
	 * @return void
	 */
	private static function add_to_failed_queue( $item, $queue_type ) {
		$failed_queue = get_option( self::FAILED_QUEUE, [] );

		$item['original_queue'] = $queue_type;
		$item['failed_at']      = current_time( 'mysql' );

		$failed_queue[] = $item;

		update_option( self::FAILED_QUEUE, $failed_queue );
	}

	/**
	 * Get failed queue items.
	 *
	 * @return array Failed queue items.
	 */
	public static function get_failed_queue() {
		return get_option( self::FAILED_QUEUE, [] );
	}

	/**
	 * Retry a failed item.
	 *
	 * @param int $index Failed item index.
	 * @return bool True on success, false on failure.
	 */
	public static function retry_failed_item( $index ) {
		$failed_queue = get_option( self::FAILED_QUEUE, [] );

		if ( ! isset( $failed_queue[ $index ] ) ) {
			return false;
		}

		$item = $failed_queue[ $index ];
		$original_queue = $item['original_queue'] ?? 'signup';

		// Reset retry count and add back to original queue.
		$item['retry_count'] = 0;
		$item['next_retry']  = time();
		unset( $item['original_queue'], $item['failed_at'], $item['last_error'] );

		$queue_option = self::get_queue_option( $original_queue );
		$queue        = get_option( $queue_option, [] );
		$queue[]      = $item;

		update_option( $queue_option, $queue );

		// Remove from failed queue.
		unset( $failed_queue[ $index ] );
		update_option( self::FAILED_QUEUE, array_values( $failed_queue ) );

		\KnownIssues\log( sprintf( 'Retrying failed item, moved back to %s queue', $original_queue ), 'info' );

		return true;
	}

	/**
	 * Clear all queues.
	 *
	 * @return void
	 */
	public static function clear_all_queues() {
		delete_option( self::SIGNUP_QUEUE );
		delete_option( self::RESOLVED_QUEUE );
		delete_option( self::FAILED_QUEUE );
	}

	/**
	 * Get queue statistics.
	 *
	 * @return array Queue statistics.
	 */
	public static function get_stats() {
		$signup_queue   = get_option( self::SIGNUP_QUEUE, [] );
		$resolved_queue = get_option( self::RESOLVED_QUEUE, [] );
		$failed_queue   = get_option( self::FAILED_QUEUE, [] );

		return [
			'signup_pending'   => count( $signup_queue ),
			'resolved_pending' => count( $resolved_queue ),
			'failed'           => count( $failed_queue ),
			'total_pending'    => count( $signup_queue ) + count( $resolved_queue ),
		];
	}

	/**
	 * Get queue option name by type.
	 *
	 * @param string $queue_type Queue type.
	 * @return string Option name.
	 */
	private static function get_queue_option( $queue_type ) {
		return 'resolved' === $queue_type ? self::RESOLVED_QUEUE : self::SIGNUP_QUEUE;
	}
}
