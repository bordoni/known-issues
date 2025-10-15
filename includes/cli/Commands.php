<?php
/**
 * WP-CLI commands for Known Issues plugin.
 *
 * @package KnownIssues
 */

namespace KnownIssues\Cli;

use KnownIssues\HelpScout\BatchProcessor;
use KnownIssues\HelpScout\QueueManager;
use WP_CLI;

/**
 * Class Commands
 *
 * WP-CLI commands for managing known issues and queues.
 */
class Commands {
	/**
	 * Process HelpScout notification queues.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<number>]
	 * : Number of items to process per batch.
	 * ---
	 * default: 10
	 * ---
	 *
	 * [--dry-run]
	 * : Show what would be processed without actually processing.
	 *
	 * [--queue-type=<type>]
	 * : Process only a specific queue (signup or resolved).
	 * ---
	 * default: all
	 * options:
	 *   - all
	 *   - signup
	 *   - resolved
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp known-issues process-queue
	 *     wp known-issues process-queue --batch-size=20
	 *     wp known-issues process-queue --queue-type=signup --dry-run
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function process_queue( $args, $assoc_args ) {
		$batch_size = (int) ( $assoc_args['batch-size'] ?? 10 );
		$dry_run    = isset( $assoc_args['dry-run'] );
		$queue_type = $assoc_args['queue-type'] ?? 'all';

		if ( $dry_run ) {
			WP_CLI::log( 'DRY RUN MODE - No items will be processed' );
		}

		WP_CLI::log( sprintf( 'Processing queue(s): %s (batch size: %d)', $queue_type, $batch_size ) );

		if ( $dry_run ) {
			$this->show_queue_preview( $queue_type, $batch_size );
			return;
		}

		// Process queues.
		if ( 'all' === $queue_type ) {
			$results = BatchProcessor::process_queues( $batch_size );

			WP_CLI::success(
				sprintf(
					'Processed %d signup items (%d success, %d failed) and %d resolved items (%d success, %d failed)',
					$results['signup']['processed'],
					$results['signup']['success'],
					$results['signup']['failed'],
					$results['resolved']['processed'],
					$results['resolved']['success'],
					$results['resolved']['failed']
				)
			);
		} elseif ( 'signup' === $queue_type ) {
			$results = BatchProcessor::process_signup_queue( $batch_size );

			WP_CLI::success(
				sprintf(
					'Processed %d signup items (%d success, %d failed)',
					$results['processed'],
					$results['success'],
					$results['failed']
				)
			);
		} elseif ( 'resolved' === $queue_type ) {
			$results = BatchProcessor::process_resolved_queue( $batch_size );

			WP_CLI::success(
				sprintf(
					'Processed %d resolved items (%d success, %d failed)',
					$results['processed'],
					$results['success'],
					$results['failed']
				)
			);
		}
	}

	/**
	 * Show queue statistics.
	 *
	 * ## EXAMPLES
	 *
	 *     wp known-issues queue-stats
	 *
	 * @return void
	 */
	public function queue_stats() {
		$stats = QueueManager::get_stats();

		WP_CLI::log( 'HelpScout Queue Statistics' );
		WP_CLI::log( '==========================' );
		WP_CLI::log( sprintf( 'Signup Queue:   %d items pending', $stats['signup_pending'] ) );
		WP_CLI::log( sprintf( 'Resolved Queue: %d items pending', $stats['resolved_pending'] ) );
		WP_CLI::log( sprintf( 'Failed Queue:   %d items', $stats['failed'] ) );
		WP_CLI::log( sprintf( 'Total Pending:  %d items', $stats['total_pending'] ) );
	}

	/**
	 * Retry failed queue items.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Retry all failed items.
	 *
	 * [<index>]
	 * : Index of specific failed item to retry.
	 *
	 * ## EXAMPLES
	 *
	 *     wp known-issues retry-failed --all
	 *     wp known-issues retry-failed 0
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function retry_failed( $args, $assoc_args ) {
		$retry_all = isset( $assoc_args['all'] );

		if ( $retry_all ) {
			$failed = QueueManager::get_failed_queue();

			if ( empty( $failed ) ) {
				WP_CLI::warning( 'No failed items to retry' );
				return;
			}

			$count = 0;
			foreach ( array_keys( $failed ) as $index ) {
				if ( QueueManager::retry_failed_item( $index ) ) {
					$count++;
				}
			}

			WP_CLI::success( sprintf( 'Retrying %d failed items', $count ) );
		} elseif ( isset( $args[0] ) ) {
			$index = (int) $args[0];

			if ( QueueManager::retry_failed_item( $index ) ) {
				WP_CLI::success( sprintf( 'Retrying failed item at index %d', $index ) );
			} else {
				WP_CLI::error( sprintf( 'Failed item at index %d not found', $index ) );
			}
		} else {
			WP_CLI::error( 'Please specify --all or an item index' );
		}
	}

	/**
	 * Clear all queues.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Confirm clearing without prompting.
	 *
	 * ## EXAMPLES
	 *
	 *     wp known-issues clear-queues --yes
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function clear_queues( $args, $assoc_args ) {
		WP_CLI::confirm( 'Are you sure you want to clear all queues?', $assoc_args );

		QueueManager::clear_all_queues();

		WP_CLI::success( 'All queues cleared' );
	}

	/**
	 * Show preview of queue items.
	 *
	 * @param string $queue_type Queue type.
	 * @param int    $batch_size Batch size.
	 * @return void
	 */
	private function show_queue_preview( $queue_type, $batch_size ) {
		if ( 'all' === $queue_type || 'signup' === $queue_type ) {
			$batch = QueueManager::get_next_batch( 'signup', $batch_size );
			WP_CLI::log( sprintf( 'Signup Queue: %d items ready', count( $batch ) ) );
		}

		if ( 'all' === $queue_type || 'resolved' === $queue_type ) {
			$batch = QueueManager::get_next_batch( 'resolved', $batch_size );
			WP_CLI::log( sprintf( 'Resolved Queue: %d items ready', count( $batch ) ) );
		}
	}
}
