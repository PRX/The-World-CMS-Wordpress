<?php
/**
 * WP-CLI command to import stations from a CSV file.
 *
 * Usage:
 *   wp tw-station import /path/to/stations.csv
 *   wp tw-station import /path/to/stations.csv --update
 *   wp tw-station import /path/to/stations.csv --dry-run
 *
 * @package tw_stations
 */

// No direct access allowed.
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Manages station import commands.
 */
class TW_Station_Importer {

	/**
	 * Import stations from a CSV file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Absolute or relative path to the CSV file.
	 *
	 * [--update]
	 * : Update existing stations instead of skipping them.
	 *
	 * [--dry-run]
	 * : Preview what would be created or updated without making changes.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tw-station import /path/to/the-world-stations-list.csv
	 *     wp tw-station import /path/to/the-world-stations-list.csv --update
	 *     wp tw-station import /path/to/the-world-stations-list.csv --dry-run
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments (flags).
	 * @return void
	 */
	public function import( $args, $assoc_args ) {
		$file    = $args[0];
		$update  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'update', false );
		$dry_run = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		if ( ! file_exists( $file ) ) {
			WP_CLI::error( "File not found: $file" );
		}

		if ( $dry_run ) {
			WP_CLI::log( 'Dry run mode — no changes will be saved.' );
			$this->run_dry_run( $file, $update );
			return;
		}

		$processor = new TW_Station_Import_Processor();
		$results   = $processor->process_file( $file, $update );

		foreach ( $results['created'] as $item ) {
			WP_CLI::log( "Created: {$item['title']} (ID: {$item['id']})" );
		}
		foreach ( $results['updated'] as $item ) {
			WP_CLI::log( "Updated: {$item['title']} (ID: {$item['id']})" );
		}
		foreach ( $results['skipped'] as $item ) {
			WP_CLI::log( "Skipped: {$item['title']} (ID: {$item['id']})" );
		}
		foreach ( $results['errors'] as $item ) {
			WP_CLI::warning( "Error: {$item['title']} — {$item['message']}" );
		}

		WP_CLI::success(
			sprintf(
				'Done. Created: %d, Updated: %d, Skipped: %d, Errors: %d',
				count( $results['created'] ),
				count( $results['updated'] ),
				count( $results['skipped'] ),
				count( $results['errors'] )
			)
		);
	}

	/**
	 * Simulate the import without saving anything.
	 *
	 * @param string $file   Path to CSV file.
	 * @param bool   $update Whether update mode is on.
	 * @return void
	 */
	private function run_dry_run( $file, $update ) {
		$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$header = fgetcsv( $handle );

		$would_create = 0;
		$would_update = 0;
		$would_skip   = 0;

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( count( $row ) !== count( $header ) ) {
				continue;
			}
			$data  = array_combine( $header, $row );
			$title = trim( $data['title'] );

			$existing = $this->find_existing_station( $title );

			if ( $existing ) {
				if ( $update ) {
					WP_CLI::log( "[DRY RUN] Would update: $title (ID: $existing)" );
					$would_update++;
				} else {
					WP_CLI::log( "[DRY RUN] Would skip: $title (ID: $existing)" );
					$would_skip++;
				}
			} else {
				WP_CLI::log( "[DRY RUN] Would create: $title" );
				$would_create++;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		WP_CLI::success(
			sprintf(
				'Dry run complete. Would create: %d, Would update: %d, Would skip: %d',
				$would_create,
				$would_update,
				$would_skip
			)
		);
	}

	/**
	 * Find an existing station by title slug.
	 *
	 * @param string $title Post title.
	 * @return int|null Post ID or null.
	 */
	private function find_existing_station( $title ) {
		$post = get_page_by_path( sanitize_title( $title ), OBJECT, 'station' );
		return $post ? $post->ID : null;
	}
}

WP_CLI::add_command( 'tw-station', 'TW_Station_Importer' );
