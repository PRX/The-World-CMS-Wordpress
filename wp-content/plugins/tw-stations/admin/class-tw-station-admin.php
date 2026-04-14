<?php
/**
 * Admin page for importing stations from a CSV file.
 *
 * @package tw_stations
 */

// No direct access allowed.
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Registers and renders the station import admin page.
 */
class TW_Station_Admin {

	const NONCE_ACTION        = 'tw_station_import';
	const NONCE_FIELD         = 'tw_station_import_nonce';
	const DELETE_NONCE_ACTION = 'tw_station_delete_all';
	const DELETE_NONCE_FIELD  = 'tw_station_delete_all_nonce';
	const PAGE_SLUG           = 'tw-station-import';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}

	/**
	 * Add submenu under Stations.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_submenu_page(
			'edit.php?post_type=station',
			__( 'Import Stations', 'tw-stations' ),
			__( 'Import Stations', 'tw-stations' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render the import page, handling form submission if present.
	 *
	 * @return void
	 */
	public static function render_page() {
		$results        = null;
		$error          = null;
		$delete_results = null;
		$delete_error   = null;

		if ( isset( $_POST[ self::NONCE_FIELD ] ) ) {
			if ( ! check_admin_referer( self::NONCE_ACTION, self::NONCE_FIELD ) ) {
				$error = 'Security check failed. Please try again.';
			} elseif ( empty( $_FILES['station_csv']['tmp_name'] ) || UPLOAD_ERR_OK !== $_FILES['station_csv']['error'] ) {
				$error = 'No file uploaded or upload error occurred.';
			} else {
				$tmp_path = $_FILES['station_csv']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$ext      = strtolower( pathinfo( sanitize_file_name( $_FILES['station_csv']['name'] ), PATHINFO_EXTENSION ) );

				if ( 'csv' !== $ext ) {
					$error = 'Please upload a .csv file.';
				} else {
					$update    = ! empty( $_POST['update_existing'] );
					$processor = new TW_Station_Import_Processor();
					$results   = $processor->process_file( $tmp_path, $update );
				}
			}
		} elseif ( isset( $_POST[ self::DELETE_NONCE_FIELD ] ) ) {
			if ( ! check_admin_referer( self::DELETE_NONCE_ACTION, self::DELETE_NONCE_FIELD ) ) {
				$delete_error = 'Security check failed. Please try again.';
			} elseif ( empty( $_POST['confirm_delete_all'] ) ) {
				$delete_error = 'You must check the confirmation box to delete all stations.';
			} else {
				$delete_results = self::delete_all_stations();
			}
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import Stations', 'tw-stations' ); ?></h1>
			<p><?php esc_html_e( 'Upload a CSV file to import stations. Existing stations are matched by call letters or post slug.', 'tw-stations' ); ?></p>

			<?php if ( $error ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
			<?php endif; ?>

			<?php if ( $delete_error ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $delete_error ); ?></p></div>
			<?php endif; ?>

			<?php if ( null !== $delete_results ) : ?>
				<div class="notice notice-success">
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of deleted stations */
								__( 'Deleted %d station(s).', 'tw-stations' ),
								$delete_results
							)
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( $results ) : ?>
				<?php self::render_results( $results ); ?>
			<?php endif; ?>

			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="station_csv"><?php esc_html_e( 'CSV File', 'tw-stations' ); ?></label>
						</th>
						<td>
							<input type="file" id="station_csv" name="station_csv" accept=".csv" required>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Options', 'tw-stations' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="update_existing" value="1">
								<?php esc_html_e( 'Update existing stations (default: skip)', 'tw-stations' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Import', 'tw-stations' ) ); ?>
			</form>

			<hr style="margin: 2em 0;">

			<h2 style="color:#d63638"><?php esc_html_e( 'Danger Zone', 'tw-stations' ); ?></h2>
			<p><?php esc_html_e( 'Permanently delete all station posts. This cannot be undone.', 'tw-stations' ); ?></p>

			<form method="post">
				<?php wp_nonce_field( self::DELETE_NONCE_ACTION, self::DELETE_NONCE_FIELD ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Confirm', 'tw-stations' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="confirm_delete_all" value="1">
								<?php esc_html_e( 'I understand this will permanently delete all stations and cannot be undone.', 'tw-stations' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Delete All Stations', 'tw-stations' ), 'delete', 'submit', true, array( 'style' => 'background:#d63638;border-color:#d63638;color:#fff;' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Permanently delete all station posts using bulk SQL queries.
	 *
	 * Bypasses wp_delete_post() to avoid N×10 individual queries.
	 *
	 * @return int Number of posts deleted.
	 */
	private static function delete_all_stations() {
		global $wpdb;

		$ids = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'station'"
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$ids_in = implode( ',', array_map( 'intval', $ids ) );

		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($ids_in)" );         // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ($ids_in)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN ($ids_in)" );                  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		clean_post_cache( $ids );

		return count( $ids );
	}

	/**
	 * Render the results table after an import.
	 *
	 * @param array $results Results from TW_Station_Import_Processor::process_file().
	 * @return void
	 */
	private static function render_results( $results ) {
		$created = $results['created'];
		$updated = $results['updated'];
		$skipped = $results['skipped'];
		$errors  = $results['errors'];
		?>
		<div class="notice notice-success">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: created count 2: updated count 3: skipped count 4: error count */
						__( 'Import complete — Created: %1$d, Updated: %2$d, Skipped: %3$d, Errors: %4$d', 'tw-stations' ),
						count( $created ),
						count( $updated ),
						count( $skipped ),
						count( $errors )
					)
				);
				?>
			</p>
		</div>

		<?php if ( $errors ) : ?>
			<h3><?php esc_html_e( 'Errors', 'tw-stations' ); ?></h3>
			<ul style="color:#d63638">
				<?php foreach ( $errors as $item ) : ?>
					<li>
						<?php if ( $item['title'] ) : ?>
							<strong><?php echo esc_html( $item['title'] ); ?>:</strong>
						<?php endif; ?>
						<?php echo esc_html( $item['message'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $created ) : ?>
			<details>
				<summary><?php echo esc_html( sprintf( __( 'Created (%d)', 'tw-stations' ), count( $created ) ) ); ?></summary>
				<ul>
					<?php foreach ( $created as $item ) : ?>
						<li>
							<a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>">
								<?php echo esc_html( $item['title'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endif; ?>

		<?php if ( $updated ) : ?>
			<details>
				<summary><?php echo esc_html( sprintf( __( 'Updated (%d)', 'tw-stations' ), count( $updated ) ) ); ?></summary>
				<ul>
					<?php foreach ( $updated as $item ) : ?>
						<li>
							<a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>">
								<?php echo esc_html( $item['title'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endif; ?>

		<?php if ( $skipped ) : ?>
			<details>
				<summary><?php echo esc_html( sprintf( __( 'Skipped (%d)', 'tw-stations' ), count( $skipped ) ) ); ?></summary>
				<ul>
					<?php foreach ( $skipped as $item ) : ?>
						<li>
							<a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>">
								<?php echo esc_html( $item['title'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endif; ?>
		<?php
	}
}
