<?php
/**
 * Core station import logic, shared by the CLI command and admin UI.
 *
 * @package tw_stations
 */

// No direct access allowed.
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Processes a station CSV and creates/updates station posts.
 */
class TW_Station_Import_Processor {

	/**
	 * ACF field key for the schedule repeater (update_field handles prefixing correctly for repeaters).
	 */
	const FIELD_SCHEDULE = 'field_689ba27787ae5';

	/**
	 * Meta keys and their ACF field key references.
	 * Using update_post_meta directly ensures the group prefix is applied correctly.
	 * (update_field on a sub-field key saves without the parent group prefix.)
	 */
	const META_FIELDS = array(
		'station_info_call_letters' => 'field_69810ae7bd0f4',
		'station_info_frequency'    => 'field_687ea33a3f5b0',
		'station_info_modulator'    => 'field_687ea40f3f5b1',
		'station_info_website'      => 'field_69810fc4b700e',
		'location_city'             => 'field_689a4ce0b6e60',
		'location_state_province'   => 'field_689a4d22b6e62',
		'location_country'          => 'field_689a4d0ab6e61',
	);

	/**
	 * Results collected during import.
	 *
	 * @var array
	 */
	private $results = array(
		'created' => array(),
		'updated' => array(),
		'skipped' => array(),
		'errors'  => array(),
	);

	/**
	 * Process a CSV file path or resource.
	 *
	 * @param string $file_path Absolute path to the CSV file.
	 * @param bool   $update    Whether to update existing stations.
	 * @return array Results array with keys: created, updated, skipped, errors.
	 */
	public function process_file( $file_path, $update = false ) {
		$this->results = array(
			'created' => array(),
			'updated' => array(),
			'skipped' => array(),
			'errors'  => array(),
		);

		$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			$this->results['errors'][] = array(
				'title'   => '',
				'message' => "Could not open file: $file_path",
			);
			return $this->results;
		}

		$header = fgetcsv( $handle );
		if ( $header ) {
			$header = array_map( 'trim', $header );
		}
		if ( ! $header ) {
			$this->results['errors'][] = array(
				'title'   => '',
				'message' => 'CSV file appears to be empty.',
			);
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return $this->results;
		}

		$row_num = 1;
		$row     = fgetcsv( $handle );
		while ( false !== $row ) {
			++$row_num;

			if ( count( $row ) !== count( $header ) ) {
				$this->results['errors'][] = array(
					'title'   => '',
					'message' => "Row $row_num has wrong column count.",
				);
			} else {
				$data = array_combine( $header, $row );
				$this->process_row( $data, $update );
			}

			$row = fgetcsv( $handle );
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $this->results;
	}

	/**
	 * Process a single data row.
	 *
	 * @param array $data   Associative row data.
	 * @param bool  $update Whether to update existing stations.
	 * @return void
	 */
	private function process_row( $data, $update ) {
		$title        = trim( $data['title'] );
		$call_letters = trim( $data['call_letters'] );

		if ( ! $title ) {
			$this->results['errors'][] = array(
				'title'   => '',
				'message' => 'Empty title, row skipped.',
			);
			return;
		}

		$existing_id = $this->find_existing_station( $title, $call_letters );

		if ( $existing_id ) {
			if ( ! $update ) {
				$this->results['skipped'][] = array(
					'title' => $title,
					'id'    => $existing_id,
				);
				return;
			}

			$this->save_station_fields( $existing_id, $data );
			$this->results['updated'][] = array(
				'title' => $title,
				'id'    => $existing_id,
			);
			return;
		}

		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_type'   => 'station',
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$this->results['errors'][] = array(
				'title'   => $title,
				'message' => $post_id->get_error_message(),
			);
			return;
		}

		$this->save_station_fields( $post_id, $data );
		$this->results['created'][] = array(
			'title' => $title,
			'id'    => $post_id,
		);
	}

	/**
	 * Find an existing station by call letters or title slug.
	 *
	 * @param string $title        Post title.
	 * @param string $call_letters Station call letters.
	 * @return int|null Post ID if found, null otherwise.
	 */
	private function find_existing_station( $title, $call_letters ) {
		if ( $call_letters ) {
			$posts = get_posts(
				array(
					'post_type'      => 'station',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'relation' => 'OR',
						array(
							'key'   => 'frequency_info_call_letters',
							'value' => $call_letters,
						),
						array(
							'key'   => 'station_info_call_letters',
							'value' => $call_letters,
						),
					),
				)
			);

			if ( $posts ) {
				return $posts[0];
			}
		}

		$post = get_page_by_path( sanitize_title( $title ), OBJECT, 'station' );
		if ( $post ) {
			return $post->ID;
		}

		return null;
	}

	/**
	 * Save all ACF fields for a station post.
	 *
	 * Station info and location fields are written via update_post_meta() with
	 * explicit meta key names (e.g. station_info_frequency, location_city) plus
	 * their ACF field-key reference rows (_station_info_frequency, etc.).
	 *
	 * ACF constructs sub-field meta keys as {group_name}_{sub_field_name}, so we
	 * write directly with update_post_meta() using those prefixed keys to ensure
	 * ACF can read them back correctly.
	 *
	 * The schedule repeater is still updated via update_field() because ACF handles
	 * repeater index prefixing correctly at the repeater level.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Associative row data from CSV.
	 * @return void
	 */
	private function save_station_fields( $post_id, $data ) {
		$call_letters = trim( $data['call_letters'] );
		$frequency    = trim( $data['frequency_info_frequency'] );
		$modulator    = trim( $data['frequency_info_modulator'] );
		$website      = trim( $data['website'] ?? '' );
		$city_name    = trim( $data['location_city'] );
		$state_name   = trim( $data['location_state_province'] );
		$country_name = trim( $data['location_country'] );

		// Simple scalar fields — write meta key + ACF field reference directly.
		$scalar_values = array(
			'station_info_call_letters' => $call_letters,
			'station_info_frequency'    => $frequency,
			'station_info_modulator'    => $modulator,
			'station_info_website'      => $website,
		);

		foreach ( $scalar_values as $meta_key => $value ) {
			if ( $value ) {
				update_post_meta( $post_id, $meta_key, $value );
				update_post_meta( $post_id, '_' . $meta_key, self::META_FIELDS[ $meta_key ] );
			}
		}

		// Taxonomy fields — resolve term IDs then write directly.
		if ( $city_name ) {
			$city_id = $this->get_or_create_term( $city_name, 'city' );
			if ( $city_id ) {
				update_post_meta( $post_id, 'location_city', $city_id );
				update_post_meta( $post_id, '_location_city', self::META_FIELDS['location_city'] );
			}
		}

		if ( $state_name ) {
			$state_id = $this->get_or_create_term( $state_name, 'province_or_state' );
			if ( $state_id ) {
				update_post_meta( $post_id, 'location_state_province', $state_id );
				update_post_meta( $post_id, '_location_state_province', self::META_FIELDS['location_state_province'] );
			}
		}

		if ( $country_name ) {
			$country_id = $this->get_or_create_term( $country_name, 'country' );
			if ( $country_id ) {
				update_post_meta( $post_id, 'location_country', $country_id );
				update_post_meta( $post_id, '_location_country', self::META_FIELDS['location_country'] );
			}
		}

		// Wipe any existing schedule rows before writing fresh ones.
		// The first import may have written bare meta keys (schedule_0_start_time_utc, etc.)
		// that update_field() doesn't clean up, causing duplicates on re-import.
		$existing_count = (int) get_post_meta( $post_id, 'schedule', true );
		for ( $i = 0; $i < $existing_count; $i++ ) {
			delete_post_meta( $post_id, "schedule_{$i}_start_time_utc" );
			delete_post_meta( $post_id, "_schedule_{$i}_start_time_utc" );
		}
		delete_post_meta( $post_id, 'schedule' );
		delete_post_meta( $post_id, '_schedule' );

		// Schedule repeater — update_field() handles index prefixing correctly here.
		$schedule = array();
		foreach ( array( 'schedule_0_start_time_utc', 'schedule_1_start_time_utc' ) as $col ) {
			$time_str = trim( $data[ $col ] ?? '' );
			if ( $time_str ) {
				$converted = $this->convert_time( $time_str );
				if ( $converted ) {
					$schedule[] = array( 'start_time_utc' => $converted );
				}
			}
		}

		if ( ! empty( $schedule ) ) {
			update_field( self::FIELD_SCHEDULE, $schedule, $post_id );
		}
	}

	/**
	 * Get an existing taxonomy term by name or create it.
	 *
	 * @param string $name     Term name.
	 * @param string $taxonomy Taxonomy slug.
	 * @return int|null Term ID or null on failure.
	 */
	private function get_or_create_term( $name, $taxonomy ) {
		$term = get_term_by( 'name', $name, $taxonomy );
		if ( $term ) {
			return $term->term_id;
		}

		$result = wp_insert_term( $name, $taxonomy );
		if ( is_wp_error( $result ) ) {
			return null;
		}

		return $result['term_id'];
	}

	/**
	 * Convert a 12-hour time string to H:i:s format.
	 *
	 * @param string $time_str e.g. "4:00:00 AM".
	 * @return string|null e.g. "04:00:00", or null on failure.
	 */
	private function convert_time( $time_str ) {
		$timestamp = strtotime( $time_str );
		if ( false === $timestamp ) {
			return null;
		}
		return gmdate( 'H:i:s', $timestamp );
	}
}
