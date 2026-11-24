<?php
/**
 * Admin helper
 *
 * @package WordPress
 */

/**
 * Add admin option page.
 *
 * @return void
 */
function test_json_diff_option_menu() {

	add_menu_page(
		'Admin Test',
		'Admin Test',
		'manage_options',
		'pmh-admin-test',
		'pmh_admin_page',
		'dashicons-admin-generic',
		30
	);

	add_submenu_page(
		'pmh-admin-test',
		'JSON Diff',
		'JSON Diff',
		'manage_options',
		'pmh-admin-test-json-diff',
		'pmh_admin_page_json_diff',
		30
	);

	add_submenu_page(
		'pmh-admin-test',
		'Terms checker',
		'Terms checker',
		'manage_options',
		'pmh-admin-terms-checker',
		'pmh_admin_terms_checker',
		30
	);

	add_submenu_page(
		'pmh-admin-test',
		'Media Fix',
		'Media Fix',
		'manage_options',
		'pmh-media-fix',
		'pmh_media_fix',
		30
	);

	add_submenu_page(
		'pmh-admin-test',
		'ACF Fix',
		'ACF Fix',
		'manage_options',
		'pmh-acf-fix',
		'pmh_acf_fix',
		30
	);

	add_submenu_page(
		'pmh-admin-test',
		'Selective Fix',
		'Selective Fix',
		'manage_options',
		'pmh-selective-fix',
		'pmh_selective_fix',
		30
	);

	add_submenu_page(
		'pmh-admin-test',
		'Cron',
		'Cron',
		'manage_options',
		'pmh-cron',
		'pmh_cron',
		30
	);
}
add_action( 'admin_menu', 'test_json_diff_option_menu' );

/**
 * Add admin option page.
 *
 * @return void
 */
function pmh_admin_page() {

	echo wp_sprintf( '<h2>%s</h2>', __( 'Admin Test' ) );

	require_once PMH_ADMIN_DIR . '/parts/main.php';
}


/**
 * Add admin option page.
 *
 * @return void
 */
function pmh_admin_page_json_diff() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Don\'t have permission to view the page' );
	}

	$options           = get_option( 'json_diff' );
	$json_diff_results = get_transient( 'json_diff_results_transient' );
	$json_url_1        = null;
	$json_url_2        = null;

	if ( $options != '' ) {
		$json_url_1 = $options['json_url_1'];
		$json_url_2 = $options['json_url_2'];
	}

	echo wp_sprintf( '<h2>%s</h2>', __( 'JSON Diff' ) );

	require_once PMH_ADMIN_DIR . '/parts/json-diff.php';

}

/**
 * Add admin option page.
 *
 * @return void
 */
function pmh_admin_terms_checker() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Don\'t have permission to view the page' );
	}

	if ( isset( $_POST['checker_form_submitted'] ) ) {

		$hidden_field          = esc_html( $_POST['checker_form_submitted'] );
		$checker_form_taxonomy = '';
		$duplicated_term       = array();

		if ( $hidden_field == 'Y' ) {
			$checker_form_taxonomy = $_POST['checker_form_taxonomy'];

			$terms = get_terms(
				array(
					'taxonomy'   => $checker_form_taxonomy,
					'parent'     => 0,
					'hide_empty' => false,
				)
			);

			$hashmap_term = array();
			foreach ( $terms as $term ) {
				$hash = $term->name;
				if ( ! array_key_exists( $hash, $hashmap_term ) ) {
					$hashmap_term[ $hash ] = array();
				}
				$hashmap_term[ $hash ][] = $term;
			}
			foreach ( $hashmap_term as $entry ) {
				if ( count( $entry ) > 1 ) {
					foreach ( $entry as $term ) {
						array_push( $duplicated_term, $term );
					}
				}
			}
		}
	}
	$args       = array(
		'object_type' => array(
			'post',
		),
	);
	$taxonomies = get_taxonomies( $args, 'objects' );

	echo wp_sprintf( '<h2>%s</h2>', __( 'Terms checker' ) );

	require_once PMH_ADMIN_DIR . '/parts/terms-checker.php';
}

function pmh_media_fix() {

	echo wp_sprintf( '<h2>%s</h2>', __( 'Media Fix' ) );

	require_once PMH_ADMIN_DIR . '/parts/media-fix.php';
}

function pmh_acf_fix() {

	echo wp_sprintf( '<h2>%s</h2>', __( 'ACF Fix' ) );
	echo wp_sprintf( '<p>%s</p>', __( 'Fix the ACF meta keys to return information in the API' ) );

	require_once PMH_ADMIN_DIR . '/parts/acf-fix.php';
}

function pmh_selective_fix() {

	echo wp_sprintf( '<h2>%s</h2>', __( 'Selective Fix' ) );
	echo wp_sprintf( '<p>%s</p>', __( 'Import node selectively by ID' ) );

	require_once PMH_ADMIN_DIR . '/parts/selective-fix.php';
}

/**
 * Add admin option page.
 *
 * @return void
 */
function pmh_cron() {

	echo wp_sprintf( '<h2>%s</h2>', __( 'Cron' ) );

	require_once PMH_ADMIN_DIR . '/parts/cron.php';
}

add_action( 'admin_enqueue_scripts', 'pmh_admin_enqueue' );
function pmh_admin_enqueue() {

	$allowed_pages = array(
		'pmh-admin-test',
		'pmh-admin-test-json-diff',
		'pmh-admin-terms-checker',
		'pmh-media-fix',
		'pmh-acf-fix',
	);

	if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $allowed_pages ) ) {

		wp_enqueue_style( 'admin-style', plugin_dir_url( __FILE__ ) . '/css/admin-styling-css.css', array(), '' );
		wp_enqueue_script( 'admin-script', plugin_dir_url( __FILE__ ) . '/js/admin-styling-js.js', array( 'jquery' ) );
		wp_enqueue_script( 'ajax-script', plugin_dir_url( __FILE__ ) . '/js/json-diff-js.js', array( 'jquery' ) );
		wp_enqueue_script( 'post-worker', plugin_dir_url( __FILE__ ) . '/js/post-worker.js', array( 'jquery' ) );
		wp_enqueue_script( 'media-fix', plugin_dir_url( __FILE__ ) . '/js/media-fix.js', array( 'jquery' ) );
		wp_enqueue_script( 'acf-fix', plugin_dir_url( __FILE__ ) . '/js/acf-fix.js', array( 'jquery' ) );

		wp_localize_script( 'ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		wp_localize_script( 'media-fix', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

		$fields = array_merge( pri_get_post_meta_keys_and_values(), pri_get_term_meta_keys_and_values() );
		wp_localize_script( 'acf-fix', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'pri_fields' => $fields ) );
	}
}

function pmh_admin_json_diff_process() {

	$url_1    = (string) $_POST['url_1'];
	$url_2    = (string) $_POST['url_2'];
	$row      = (int) $_POST['row'];
	$next_row = $row + 1;

	$json1 = wp_remote_get( $url_1 );
	$obj1  = json_decode( wp_remote_retrieve_body( $json1 ) );

	$json2 = wp_remote_get( $url_2 );
	$obj2  = json_decode( wp_remote_retrieve_body( $json2 ) );

	$diff = pmh_get_json_diff( $obj1, $obj2 );

	$pmh_get_json_diff = test_json_diff( $url_1, $url_2 );
	$count             = $pmh_get_json_diff->getDiffCnt();
	$count_print       = ( 0 == $count ) ? '<div class="dashicons-before dashicons-yes"></div>' : '<div class="dashicons-before dashicons-no-alt"></div>';
	$patch             = ( 0 != $count ) ? '<div class="faq-drawer">
	<input class="faq-drawer__trigger" id="faq-drawer'.$next_row.'" type="checkbox" /><label class="faq-drawer__title" for="faq-drawer'.$next_row.'">See diff detail('.$count.')</label>
	<div class="faq-drawer__content-wrapper">
	  <div class="faq-drawer__content"><pre>'.var_export( $pmh_get_json_diff->getPatch()->jsonSerialize(), true ).'</pre></div>
      </div>
    </div>' : '<div class="dashicons-before dashicons-minus"></div>';


	if ( ! $obj1 || ! $obj2 ) {

		$patch = '';

		if ( ! $obj1 ) {
			$patch .= wp_sprintf( '<p>%s</p>', __( 'First URL is not responding.' ) );
		}

		if ( ! $obj2 ) {
			$patch .= wp_sprintf( '<p>%s</p>', __( 'Second URL is not responding.' ) );
		}
	}

	$json_diff_result = array(
		'line'        => $next_row,
		'url_1'       => $url_1,
		'url_2'       => $url_2,
		'count_print' => $count_print,
		'patch'       => $patch,
	);

	$json_diff_results = get_transient( 'json_diff_results_transient' );
	$transient_time    = 12 * HOUR_IN_SECONDS;

	if ( ! $json_diff_results ) {

		$json_diff_results = array();
	}

	$json_diff_results[] = $json_diff_result;

	set_transient( 'json_diff_results_transient', $json_diff_results, $transient_time );

	$html_response =
	"<tr>
		<td>{$json_diff_result['line']}</td>
		<td><a href='{$url_1}' target='_blank'>{$url_1}</a></td>
		<td><a href='{$url_2}' target='_blank'>{$url_2}</a></td>
		<td>{$json_diff_result['count_print']}</td>
		<td>{$json_diff_result['patch']}</td>
	</tr>";

	$response = array(
		'html'     => $html_response,
		'next_row' => $next_row,
	);

	wp_send_json( $response );
}
add_action( 'wp_ajax_json_diff_process', 'pmh_admin_json_diff_process' );

function pmh_admin_json_diff_save_rows() {

	$url_1 = (string) $_POST['url_1'];
	$url_2 = (string) $_POST['url_2'];

	$opt = array(
		'json_url_1' => $url_1,
		'json_url_2' => $url_2,
	);

	update_option( 'json_diff', $opt );

	wp_die();
}
add_action( 'wp_ajax_json_diff_save_rows', 'pmh_admin_json_diff_save_rows' );

function pmh_admin_json_diff_clear() {

	delete_transient( 'json_diff_results_transient' );

	wp_die();
}
add_action( 'wp_ajax_json_diff_clear', 'pmh_admin_json_diff_clear' );

/**
 * Get object names.
 *
 * @return void
 */
function pmh_post_worker_select_object_type() {

	$object_names = array();
	$object_type  = sanitize_text_field( $_POST['objectType'] );

	switch ( $object_type ) {

		case 'taxonomy':
			$_object_names = get_taxonomies( array(), 'names' );

			if ( $_object_names ) {
				foreach ( $_object_names as $_object_name => $name ) {
					$object_names[] = $_object_name;
				}
			}
			break;

		case 'post-type':
			$_object_names = get_post_types( array(), 'names' );

			if ( $_object_names ) {
				foreach ( $_object_names as $_object_name => $name ) {
					$object_names[] = $_object_name;
				}
			}
			break;
	}

	$response = array(
		'object_names' => $object_names,
	);

	wp_send_json( $response );
}
add_action( 'wp_ajax_pmh_post_worker_select_object_type', 'pmh_post_worker_select_object_type' );

/**
 * Get object names.
 *
 * @return void
 */
function pmh_post_worker_get_sample() {

	$samples = array();
	$total   = 0;

	$object_type   = sanitize_text_field( $_POST['objectType'] );
	$object_name   = sanitize_text_field( $_POST['objectName'] );
	$paged_process = (int) sanitize_text_field( $_POST['pagedProcess'] );

	switch ( $object_type ) {

		case 'taxonomy':
			$total = wp_count_terms(
				$object_name,
				array(
					'hide_empty'=> false,
				)
			);

			$offset   = ( $paged_process - 1 ) * 50;
			$args     = array(
				'hide_empty' => false,
				'number'     => 50,
				'offset'     => $offset,
			);
			$_samples = get_terms( $object_name, $args );

			if ( $_samples ) {
				foreach ( $_samples as $_sample ) {

					switch ( $_sample->taxonomy ) {
						case 'program':
							$term_meta_key = '_fgd2wp_old_program_id';
							$post_prefix = 'node';
							break;
						case 'contributor':
							$term_meta_key = '_fgd2wp_old_person_id';
							$post_prefix = 'node';
							break;

						default:
							$term_meta_key = '_fgd2wp_old_taxonomy_id';
							$post_prefix = 'taxonomy/term';
							break;
					}

					$samples[] = array(
						'old_url'   => wp_sprintf( '%s/%s', $post_prefix, get_term_meta( $_sample->term_id, $term_meta_key, true ) ),
						'id'        => $_sample->term_id,
						'type'      => $object_name,
						'activated' => 1,
					);
				}
			}
			break;

		case 'post-type':
			$_total = wp_count_posts( $object_name );
			$total  = (int) $_total->publish + (int) $_total->draft;

			$args     = array(
				'post_type'      => $object_name,
				'posts_per_page' => 50,
				'paged'          => $paged_process,
			);
			$_samples = get_posts( $args );

			if ( $_samples ) {
				switch ( $object_name ) {
					case 'segment':
						$post_meta_key = 'fid';
						$post_prefix = 'file';
						break;

					default:
						$post_meta_key = 'nid';
						$post_prefix = 'node';
						break;
				}
				foreach ( $_samples as $_sample ) {

					$samples[] = array(
						'old_url'   => wp_sprintf( '%s/%s', $post_prefix, get_post_meta( $_sample->ID, $post_meta_key, true ) ),
						'id'        => $_sample->ID,
						'type'      => $object_name,
						'activated' => 1,
					);
				}
			}
			break;
	}

	$response = array(
		'samples' => $samples,
		'total'   => $total,
	);

	wp_send_json( $response );
}
add_action( 'wp_ajax_pmh_post_worker_get_sample', 'pmh_post_worker_get_sample' );


/**
 * Get object names.
 *
 * @param array $args taxonomy, category, pagedProcess
 *
 * @return mixed
 */
function pmh_post_worker_run_process( $args ) {

	global $wpdb;

	// If doing ajax.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

		$object_type   = sanitize_text_field( $_POST['objectType'] );
		$object_name   = sanitize_text_field( $_POST['objectName'] );
		$paged_process = (int) sanitize_text_field( $_POST['pagedProcess'] );

	} else {

		$object_type   = $args['object_type'];
		$object_name   = $args['object_name'];
		$paged_process = $args['paged_process'];
	}

	$next_paged_process = $paged_process + 1;

	$paged = $paged_process;

	// Log.
	$log = "\n";

	switch ( $object_type ) {

		case 'taxonomy':
			$offset = ( $paged - 1 ) * 50;
			$args   = array(
				'hide_empty' => false,
				'number'     => 50,
				'offset'     => $offset,
			);
			$terms  = get_terms( $object_name, $args );

			if ( $terms ) {
				foreach ( $terms as $term ) {

					$term_id = $term->term_id;
					switch ( $term->taxonomy ) {
						case 'program':
							$term_meta_key = '_fgd2wp_old_program_id';
							$post_prefix = 'node';
							break;
						case 'contributor':
							$term_meta_key = '_fgd2wp_old_person_id';
							$post_prefix = 'node';
							break;

						default:
							$term_meta_key = '_fgd2wp_old_taxonomy_id';
							$post_prefix = 'taxonomy/term';
							break;
					}

					$nid     = get_term_meta( $term_id, $term_meta_key, true );
					$old_url = "$post_prefix/$nid";
					$result  = $wpdb->get_results( "SELECT * FROM wp_fg_redirect WHERE old_url = '$old_url'" );

					if ( ! $result ) {
						$insert = $wpdb->insert(
							'wp_fg_redirect',
							array(
								'old_url'   => wp_sprintf( '%s/%s', $post_prefix, get_term_meta( $term_id, $term_meta_key, true ) ),
								'id'        => $term_id,
								'type'      => $object_name,
								'activated' => 1,
							),
							array(
								'%s',
								'%d',
								'%s',
								'%d',
							),
						);

						$insert_log = $insert ? "Success - $term_id (pg-$paged)" : "Failed - $term_id (pg-$paged)";
					} else {

						$insert_log = "Duplicate - $term_id (pg-$paged)";
					}

					$log .= "\n$insert_log";
				}
			} else {
				$next_paged_process = false;
			}
			break;

		case 'post-type':

			$args     = array(
				'post_status'    => 'all',
				'post_type'      => $object_name,
				'posts_per_page' => 500,
				'paged'          => $paged,
				'fields'         => 'ids',
			);
			$posts = get_posts( $args );

			if ( $posts ) {
				foreach ( $posts as $post ) {

					$post_id = $post;

					$result = $wpdb->get_results( "SELECT * FROM wp_fg_redirect WHERE id = $post_id LIMIT 1" );

					if ( ! $result ) {
						switch ( $object_name ) {
							case 'segment':
								$post_meta_key = 'fid';
								$post_prefix = 'file';
								break;

							default:
								$post_meta_key = 'nid';
								$post_prefix = 'node';
								break;
						}
						$nid = get_post_meta( $post_id, $post_meta_key, true );

						if ( $nid ) {

							$insert = $wpdb->insert(
								'wp_fg_redirect',
								array(
									'old_url'   => wp_sprintf( '%s/%s', $post_prefix, $nid ),
									'id'        => $post_id,
									'type'      => $object_name,
									'activated' => 1,
								),
								array(
									'%s',
									'%d',
									'%s',
									'%d',
								),
							);

							$insert_log = $insert ? "Success - $post_id (pg-$paged)" : "Failed - $post_id (pg-$paged)";

						} else {

							$insert_log = "No NID - $post_id (pg-$paged)";
						}
					} else {

						$insert_log = "Duplicate - $post_id (pg-$paged)";
					}

					$log .= "\n$insert_log";
				}
			} else {
				$next_paged_process = false;
			}
			break;
	}

	$response = array(
		'log'                => $log,
		'next_paged_process' => $next_paged_process,
	);

	// Is doing ajax.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

		wp_send_json( $response );

	} else {

		return $response;
	}
}
add_action( 'wp_ajax_pmh_post_worker_run_process', 'pmh_post_worker_run_process' );

/**
 * Get media ids.
 *
 * @param integer $i_paged
 * @param integer $i_perpage
 * @param array $a_ids
 * @return array
 */
function f_pmh_get_media_ids( int $i_paged, int $i_perpage, $a_ids = array() ) {

	// Query media.
	$a_args = array(
        'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'post_status'    => 'inherit',
		'post_parent'    => null, // any parent
		'fields'         => 'ids',
		'meta_query'  => array(
			array(
				'key'     => s_pmh_get_fixed_flag_key(),
				'compare' => 'NOT EXISTS',
			),
		),
		'no_found_rows' => true,
		'orderby'        => 'ID',
        'order'          => 'ASC',
	);
	if ( $a_ids ) {

		$a_args['post__in'] = $a_ids;

	} else {

		// MEMO: I commented this because the pagination won't be necessary since we are checking if the result has elements to process and then it runs again until the result is emtpy.
		// $a_args['paged']          = $i_paged;
		$a_args['posts_per_page'] = $i_perpage;
	}
	$WP_Query_posts = new WP_Query( $a_args );
	$a_posts = $WP_Query_posts->get_posts();

	return $a_posts;
}

/**
 * Get posts ids.
 *
 * @param integer $i_paged
 * @param integer $i_perpage
 * @param array $a_ids
 * @return array
 */
function f_pmh_get_posts_ids( int $i_paged, int $i_perpage, $a_ids = array() ) {

	// Query media.
	$a_args = array(
        'post_type'      => 'post',
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'ASC',
	);

	if ( $a_ids ) {

		$a_args['post__in'] = $a_ids;

	} else {
		// MEMO: I commented this because the pagination won't be necessary since we are checking if the result has elements to process and then it runs again until the result is emtpy.
		// $a_args['paged']          = $i_paged;
		$a_args['posts_per_page'] = $i_perpage;

		/**
		 * Search by meta query.
		 */
		$a_args['no_found_rows']  = true;
		$a_args['meta_query']     = array(
			array(
				'key'     => s_pmh_get_fixed_flag_key(),
				'compare' => 'NOT EXISTS',
			),
		);
	}
	$WP_Query_posts = new WP_Query( $a_args );
	$a_posts = $WP_Query_posts->get_posts();
	return $a_posts;
}

/**
 * Get Drupal metadata based on WP Media IDs.
 *
 * @param [type] $a_wp_media_ids
 * @return array
 */
function f_pmh_get_drupal_file_metadata( $a_wp_media_ids ) {

	$a_drupal_metas = array();

	if ( $a_wp_media_ids ) {

		global $fgd2wpp;

		// Simulate running importer.
		ob_start();
		$fgd2wpp->importer();
		ob_get_clean();

		$a_wp_drupal_ids = array();
		$a_drupal_fids   = array();

		foreach ( $a_wp_media_ids as $i_media_id ) {

			$i_fid = (int) get_post_meta( $i_media_id, 'fid', true );

			$a_drupal_fids[]                = $i_fid;
			$a_wp_drupal_ids[ $i_media_id ] = $i_fid;
		}

		/* Debug
		echo "<pre>";
		var_dump( 'drupal_db connection.' );
		var_dump( $fgd2wpp->drupal_connect() );
		echo "</pre>";
		exit;
		*/

		if ( $a_drupal_fids && $fgd2wpp->drupal_connect() ) {

			$s_fids = implode( ',', array_filter( $a_drupal_fids ) );

			// Get file metadata.
			$s_sql_query = "
				SELECT
					fmd.fid AS fid,
					fmd.name AS name,
					fmd.value AS value

				FROM
					file_metadata AS fmd

				WHERE
					fmd.fid in ({$s_fids})
			";

			$a_rows = $fgd2wpp->drupal_query( $s_sql_query );

			/* Debug
			echo "<pre>";
			var_dump( $a_rows );
			echo "</pre>";
			exit;
			*/

			if ( count( $a_rows ) > 0 ) {

				foreach( $a_wp_drupal_ids as $i_media_id => $i_drupal_id ) {

					$a_drupal_meta = array();

					foreach ( $a_rows as $a_row ) {

						if ( $a_row['fid'] == $i_drupal_id ) {

							$s_row_col_name  = $a_row['name'];
							$s_row_col_value = $a_row['value'];

							if (
								'width' === $s_row_col_name
								||
								'height' === $s_row_col_name
							) {
								$s_row_col_value = str_replace( 'i:', '', $s_row_col_value );
								$s_row_col_value = str_replace( ';', '', $s_row_col_value );
								$s_row_col_value = intval( $s_row_col_value );
							}

							$a_drupal_meta[ $s_row_col_name ] = $s_row_col_value;
						}
					}

					$a_drupal_meta['fid'] = $i_drupal_id;

					$a_drupal_metas[ $i_media_id ] = $a_drupal_meta;
				}
			}
			/* Debug
			echo "<pre>";
			var_dump( $results_2 );
			echo "</pre>";
			exit;
			*/
		}
	}

	return $a_drupal_metas;
}

/**
 * Get Drupal file metadata by fid.
 *
 * @param int $i_fid
 *
 * @return array
 */
function f_pmh_get_drupal_image_sizes( int $fid ) {

	global $fgd2wpp;

	$sql_query = "
	SELECT
		fmd.fid AS fid,
		fmd.name AS name,
		fmd.value AS value

	FROM
		file_metadata AS fmd

	WHERE
		fmd.fid = $fid
	";

	$a_rows = $fgd2wpp->drupal_query( $sql_query );

	$a_drupal_meta = array();

	if ( $a_rows ) {

		foreach ( $a_rows as $a_row ) {

			$s_row_col_name  = $a_row['name'];
			$s_row_col_value = $a_row['value'];

			if (
				'width' === $s_row_col_name
				||
				'height' === $s_row_col_name
			) {
				$s_row_col_value = str_replace( 'i:', '', $s_row_col_value );
				$s_row_col_value = str_replace( ';', '', $s_row_col_value );
				$s_row_col_value = intval( $s_row_col_value );
			}

			$a_drupal_meta[ $s_row_col_name ] = $s_row_col_value;

		}
	}

	return $a_drupal_meta;
}

/**
 * Respond to ajax request.
 *
 * @return void
 */
function f_ajax_pmh_media_fix_sample() {

	$b_show_post_metas = false;

	$i_paged_process   = (int) sanitize_text_field( $_POST['i_paged'] );
	$i_perpage_process = (int) sanitize_text_field( $_POST['i_perpage'] );
	$s_per_ids         = sanitize_text_field( $_POST['s_per_ids'] );

	$i_next_paged_process = $s_per_ids ? false : $i_paged_process + 1;
	$a_per_ids            = $s_per_ids ? explode( " ", $s_per_ids ) : false;

	// Start Log.
	$s_log = "MEDIA SAMPLE\n\n";

	// Query media.
	$a_media_ids = f_pmh_get_media_ids( $i_paged_process, $i_perpage_process, $a_per_ids );

	if ( $a_media_ids ) {

		/* Debug
		echo "<pre>";
		var_dump( f_pmh_get_drupal_file_metadata( $a_media_ids ) );
		echo "</pre>";
		exit;
		 */

		$a_drupal_media_metadatas = f_pmh_get_drupal_file_metadata( $a_media_ids );

		$a_image_attribute_keys = array(
			'source',
			'width',
			'height',
			'resized',
		);

		$a_meta_to_check = array(
			'fid',
			'image_title',
			'original_uri',
			'hide_image',
			'_wp_attachment_image_alt',
			'_media_credit',
		);

		foreach ( $a_media_ids as $i_media_id ) {

			$a_image_attributes = wp_get_attachment_image_src( $i_media_id, 'full' );

			$s_log .= "\n" . $i_media_id . "\n- - - - - - - - -\n";

			if ( $a_image_attributes ) {

				// Show media attributes.
				foreach ( $a_image_attributes as $i => $s_image_attribute ) {

					$s_drupal_info = 'n/a';

					if (
						'width' === $a_image_attribute_keys[$i]
						||
						'height' === $a_image_attribute_keys[$i]
					) {

						if (
							isset( $a_drupal_media_metadatas[ $i_media_id ][ $a_image_attribute_keys[$i] ] )
							&&
							$a_drupal_media_metadatas[ $i_media_id ][ $a_image_attribute_keys[$i] ]
						) {

							$s_drupal_info = $a_drupal_media_metadatas[ $i_media_id ][ $a_image_attribute_keys[$i] ];
						}
					}

					$s_log .= $a_image_attribute_keys[$i] . ":\n" . $s_image_attribute . "\n($s_drupal_info)" . "\n\n";
				}
			} else {

				$s_log .= 'NO ATTRIBUTE FOUND';
			}

			if ( $b_show_post_metas ) {

				// Show media metas.
				foreach ( $a_meta_to_check as $s_meta_to_check ) {

					$m_meta_value = get_post_meta( $i_media_id, $s_meta_to_check, true );

					ob_start();
					var_dump( $m_meta_value );
					$s_meta_value = ob_get_clean();

					$s_log .= $s_meta_to_check . ":\n" . $s_meta_value . "\n";
				}
			}

			$s_log .= "\n- - - - - - - - -\n\n";
		}
	}

	// End Log.
	$s_log .= "\n= = = = = = = = = = = = \n";

	$a_response = array(
		'log'                => $s_log,
		'next_paged_process' => $i_next_paged_process,
	);

	wp_send_json( $a_response );
}
add_action( 'wp_ajax_media_fix_sample', 'f_ajax_pmh_media_fix_sample' );

/**
 * Respond to ajax request.
 *
 * @return void
 */
function f_ajax_pmh_media_fix_run() {

	$i_paged_process   = isset( $_POST['i_paged'] ) ? (int) sanitize_text_field( $_POST['i_paged'] ) : 1;
	$i_perpage_process = isset( $_POST['i_perpage'] ) ? (int) sanitize_text_field( $_POST['i_perpage'] ) : 50;
	$s_per_ids         = isset( $_POST['s_per_ids'] ) ? sanitize_text_field( $_POST['s_per_ids'] ) : '';

	$i_next_paged_process = $s_per_ids ? false : $i_paged_process + 1;
	$a_per_ids            = $s_per_ids ? explode( " ", $s_per_ids ) : false;

	// Start Log.
	$s_log = "MEDIA FIX RUNNING - PAGED {$i_paged_process}\n\n";

	// Query media.
	$a_media_ids = f_pmh_get_media_ids( $i_paged_process, $i_perpage_process, $a_per_ids );
	$i_media_ids = count( $a_media_ids );

	if ( $a_media_ids ) {

		$a_drupal_media_metadatas = f_pmh_get_drupal_file_metadata( $a_media_ids );

		foreach ( $a_media_ids as $i_media_id ) {

			$s_log .= "\n" . $i_media_id . "\n- - - - - - - - -\n";

			$a_attachment_metadata = wp_get_attachment_metadata( $i_media_id );

			$a_keys_to_change = array( 'width', 'height' );

			$b_updated = false;

			foreach ( $a_keys_to_change as $s_key_to_change ) {

				if (
					isset( $a_drupal_media_metadatas[ $i_media_id ][ $s_key_to_change ] )
					&&
					$a_drupal_media_metadatas[ $i_media_id ][ $s_key_to_change ]
					&&
					$a_drupal_media_metadatas[ $i_media_id ][ $s_key_to_change ] != $a_attachment_metadata[ $s_key_to_change ]
				) {

					$b_updated = true;

					$s_log .= $s_key_to_change . ":\n" . $a_attachment_metadata[ $s_key_to_change ];
					$s_log .= " => " . $a_drupal_media_metadatas[ $i_media_id ][ $s_key_to_change ] . "\n\n";

					// File metadata.
					$a_attachment_metadata[ $s_key_to_change ] = $a_drupal_media_metadatas[ $i_media_id ][ $s_key_to_change ];

					// File 'full' size metadata.
					if ( isset( $a_attachment_metadata['sizes']['full'][ $s_key_to_change ] ) ) {

						$a_attachment_metadata['sizes']['full'][ $s_key_to_change ] = $a_drupal_media_metadatas[ $i_media_id ][ $s_key_to_change ];
					}
				}
			}

			if ( $b_updated ) {

				$m_updated = wp_update_attachment_metadata( $i_media_id, $a_attachment_metadata );

				$s_log .= $m_updated ? "updated" : "failed to update";
				$s_log .= "\n";

			} else {

				$s_log .= "no update\n";
			}

			f_pmh_flag_object_corrected( $i_media_id, 'post' );

			$s_log .= "\n- - - - - - - - -\n\n";
		}
	} else {

		$s_log .= "No Media to process.\n\n";

		$i_next_paged_process = false;
	}

	// End Log.
	$s_log .= "\n= = = = = = = = = = = = \n";

	// End Log.
	$a_response = array(
		'log'                => $s_log,
		'next_paged_process' => $i_next_paged_process,
		'count_processed'    => $i_media_ids,
	);

	wp_send_json( $a_response );
}
add_action( 'wp_ajax_media_fix_run', 'f_ajax_pmh_media_fix_run' );

/**
 * Respond to ajax request.
 *
 * @return void
 */
function pri_ajax_acf_fix_run() {

	$i_paged_process   = (int) sanitize_text_field( $_POST['i_paged'] );
	$i_perpage_process = (int) sanitize_text_field( $_POST['i_perpage'] );
	$s_per_ids         = sanitize_text_field( $_POST['s_per_ids'] );
	$s_post_type       = sanitize_text_field( $_POST['s_post_type'] );
	$s_field           = sanitize_text_field( $_POST['s_field'] );
	$b_is_term         = in_array( $s_post_type, array( 'category', 'contributor', 'program' ) ) ? true : false;

	if ( ! $s_post_type || ! $s_field ) {
		return array(
			'log'                => 'No post type or field provided',
			'next_paged_process' => false,
		);
	}

	switch ( $s_post_type ) {
		case 'images':
		case 'audio':
			$s_query_type = 'attachment';
			break;
		default:
			$s_query_type = $s_post_type;
			break;
	}

	$i_next_paged_process = $s_per_ids ? false : $i_paged_process + 1;

	// Start Log.
	$s_log = "ACF FIX RUNNING - {$s_query_type} - {$s_field} - PAGED {$i_paged_process}\n\n";

	// Query acf.
	// 1. Get media elements without the "key_to_check" meta key
	if ( $b_is_term ) {

		$i_term_paged_process = ( $i_paged_process - 1 ) * 10;

		$a_query_args = array(
			'taxonomy'    => $s_query_type,
			'fields'      => 'ids',
			'number'      => $i_perpage_process,
			'offset'      => $i_term_paged_process,
			'hide_empty'  => false,
			'meta_query'  => array(
				array(
					'key'     => $s_field,
					'compare' => 'NOT EXISTS',
				),
			),
		);

	} else {

		$a_query_args = array(
			'post_type'      => $s_query_type,
			'fields'         => 'ids',
			'posts_per_page' => $i_perpage_process,
			// 'paged'          => $i_paged_process, // Commented this as 1 because we removed the found rows functionality.
			'post_status'    => $s_query_type === 'attachment' ? 'inherit' : array( 'publish', 'draft', 'private' ),
			'meta_query'     => array(
				array(
					'key'     => $s_field,
					'compare' => 'NOT EXISTS',
				),
			),
			'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
            'no_found_rows' => true,
            'order' => 'ASC',
            'orderby' => 'ID',
		);
	}

	if( $s_per_ids ) {

		if ( $b_is_term ) {

			$a_query_args['include'] = explode( ',', $s_per_ids );

		} else {

			$a_query_args['post__in'] = explode( ',', $s_per_ids );
		}
	}

	if( in_array( $s_post_type, array( 'images', 'audio' ) ) && ! $b_is_term ) {
		$a_query_args['post_mime_type'] = $s_post_type === 'images' ? 'image' : 'audio';
	}

	if ( $b_is_term ) {

		$wp_query_acf_items = new WP_Term_Query( $a_query_args );

		$a_query_term_ids = $wp_query_acf_items->get_terms();

		// 2. process each media element
		if ( $a_query_term_ids ) {

			foreach ( $a_query_term_ids as $i_acf_term_id ) {

				$s_log .= "\n" . $i_acf_term_id . " - " . $s_query_type . "\n- - - - - - - - -\n";

				$s_field_value = pri_get_term_meta_keys_and_values( $s_post_type, $s_field );

				if ( $s_field_value ) {

					$m_add_term_meta = add_term_meta( $i_acf_term_id, $s_field, $s_field_value, true );

					if ( $m_add_term_meta ) {

						$s_log .= "\n" . $i_acf_term_id . " - " . $s_field . " - " . $s_field_value . " - field value inserted\n";

					} elseif ( is_wp_error( $m_add_term_meta ) ) {

						$s_log .= "\n" . $i_acf_term_id . " - " . $s_field . " - " . $s_field_value . " - ambiguous between taxonomies\n";

					} else {

						$s_log .= "\n" . $i_acf_term_id . " - " . $s_field . " - " . $s_field_value . " - failed to insert value\n";
					}
				} else {

					$s_log .= "\n" . $i_acf_term_id . " - " . $s_query_type . " - no field value\n";
				}
			}
		} else {
			$i_next_paged_process = false;
		}
	} else {

		$wp_query_acf_items = new WP_Query( $a_query_args );

		// 2. process each media element
		if ( $wp_query_acf_items->have_posts() ) {
			if ( $wp_query_acf_items->have_posts() ) {
				foreach ( $wp_query_acf_items->posts as $i_acf_post_id ) {
					// use a foreach loop to iterate through the post ids
					$s_mime_type = get_post_mime_type( $i_acf_post_id );

					$s_log .= "\n" . $i_acf_post_id . " - " . $s_mime_type . "\n- - - - - - - - -\n";

					$s_field_value = pri_get_post_meta_keys_and_values( $s_post_type, $s_field );

					if ( $s_field_value ) {
						update_post_meta( $i_acf_post_id, $s_field, $s_field_value, true );
						$s_log .= "\n" . $i_acf_post_id . " - " . $s_field . " - " . $s_field_value . " - field value inserted\n";
					} else {
						$s_log .= "\n" . $i_acf_post_id . " - " . $s_mime_type . " - no field value\n";
					}
				}
			}
		} else {
			$i_next_paged_process = false;
		}
	}

	// Reset the global post data after the custom query
	wp_reset_postdata();

	// End Log.
	$s_log .= "\n= = = = = = = = = = = = \n";

	// End Log.
	$a_response = array(
		'log'                => $s_log,
		'next_paged_process' => $i_next_paged_process,
	);

	wp_send_json( $a_response );
}
add_action( 'wp_ajax_acf_fix_run', 'pri_ajax_acf_fix_run' );


/**
 * Respond to ajax request.
 *
 * @return void
 */
function f_ajax_pmh_posts_fix_run() {

	$i_paged_process   = isset( $_POST['i_paged'] ) ? (int) sanitize_text_field( $_POST['i_paged'] ) : 1;
	$i_perpage_process = isset( $_POST['i_perpage'] ) ? (int) sanitize_text_field( $_POST['i_perpage'] ) : 50;
	$s_per_ids         = isset( $_POST['s_per_ids'] ) ? sanitize_text_field( $_POST['s_per_ids'] ) : '';

	$i_next_paged_process = $s_per_ids ? false : $i_paged_process + 1;
	$a_per_ids            = $s_per_ids ? explode( " ", $s_per_ids ) : false;

	// Start Log.
	$s_log = "POSTS FIX RUNNING - PAGED {$i_paged_process}\n\n";

	$a_posts_ids = f_pmh_get_posts_ids( $i_paged_process, $i_perpage_process, $a_per_ids );
	$i_posts_ids = count( $a_posts_ids );
	$a_media_ids = f_pmh_get_media_ids( 1, 1 );
	$i_media_ids = count( $a_media_ids );

	if ( $a_posts_ids && ! $a_media_ids ) {

		foreach ( $a_posts_ids as $i_post_id ) {

			$s_log .= "\n" . $i_post_id . "\n- - - - - - - - -\n";

			$m_updated = f_pmh_process_posts_content( $i_post_id );

			if ( $m_updated ) {

				$s_log .= 'updated';

			} else {

				$s_log .= 'no update';
			}

			$s_log .= "\n- - - - - - - - -\n\n";
		}
	} else {

		$s_log .= "No Posts to process. ";
		if ( $a_media_ids ) {
			$s_log .= "There are still unfixed images. ";
		}
		$s_log .= "\n\n";

		$i_next_paged_process = false;
	}

	// End Log.
	$s_log .= "\n= = = = = = = = = = = = \n";

	// End Log.
	$a_response = array(
		'log'                => $s_log,
		'next_paged_process' => $i_next_paged_process,
		'count_processed'    => $i_posts_ids,
	);

	wp_send_json( $a_response );
}
add_action( 'wp_ajax_posts_fix_run', 'f_ajax_pmh_posts_fix_run' );

/**
 * Check for existing file
 *
 * @param [string] $url
 * @return bool
 */
function f_pmh_remote_file_exist( $url ) {

    $response = wp_remote_head($url);

    if (is_wp_error($response)) {
        return false;  // The HTTP request failed for some reason
    }

    $status_code = wp_remote_retrieve_response_code($response);

    // If the HTTP response code is 200, it's likely the image exists
	$allowed_codes = array(
		200,
		301,
		302,
	);

    return in_array($status_code, $allowed_codes);
}

/**
 * Escape URL and Get image size array.
 *
 * @param string $s_image_url Image URL.
 *
 * @return array|bool
 */
function f_pmh_get_clean_url( $s_image_url ) {

	// Escape the URL.
	$s_target_url    = esc_url( $s_image_url );
	$s_file_name     = basename( $s_target_url );
	$s_valid_url     = filter_var( $s_target_url, FILTER_VALIDATE_URL );
	$s_clean_url	= $s_valid_url ? $s_target_url : str_replace( $s_file_name, urlencode( $s_file_name ), $s_image_url );

	// Get image size normally.
	$b_image_exist = f_pmh_remote_file_exist( $s_clean_url );

	if ( false === $b_image_exist ) {

		$s_file_name = basename( $s_image_url );

		// Get image size with urlencode.
		$s_clean_url = str_replace( $s_file_name, urlencode( $s_file_name ), $s_image_url );

		$b_image_exist = f_pmh_remote_file_exist( $s_clean_url );

		if ( false === $b_image_exist ) {

			// Get image size with rawurlencode.
			$s_clean_url = str_replace( $s_file_name,rawurlencode( $s_file_name ), $s_image_url );
		}
	}

	return $s_clean_url;
}

/**
 * Process posts ids.
 *
 * @param int $i_post_id
 * @return void
 */
function f_pmh_process_posts_content( int $i_post_id ) {

	// Get saved data.
	$o_post      = get_post( $i_post_id );
	$s_content   = $o_post->post_content;
	$i_drupal_id = (int) get_post_meta( $i_post_id, 'nid', true );

	$s_new_content = f_pmh_get_updated_posts_content( $s_content, $i_post_id, $i_drupal_id );

	$m_updated = false;

	if ( $s_new_content ) {

		/**
		 * Post updater.
		 */
		$a_update_post_args = array(
			'ID'           => $i_post_id,
			'post_content' => $s_new_content,
		);

		$m_updated = wp_update_post( $a_update_post_args );
	}

	return $m_updated;
}
add_action( 'fgd2wp_post_import_post', 'f_pmh_process_posts_content', 99, 1 );

/**
 * Process posts ids.
 *
 * @param int $i_post_id
 * @return void
 */
function f_pmh_get_updated_posts_content( string $s_content, int $i_post_id, int $i_drupal_id ) {

	$s_new_content = '';
	$m_updated = false;

	$a_matches    = array();
	$a_switches_1 = array();
	$a_switches_2 = array();

	$a_drupal_content_media_data = f_pmh_get_drupal_content_media_data( $i_drupal_id );

	// preg_match_all( '#<(img|a)(.*?)(src|href)="(.*?)"(.*?)>#s', $s_content, $a_matches, PREG_SET_ORDER );
	preg_match_all( "/<img(.*?)class=('|\")(.*?)('|\")(.*?)src=('|\")(.*?)('|\")(.*?)>/i", $s_content, $a_matches, PREG_SET_ORDER );

	if ( $a_matches ) {

		foreach ( $a_matches as $a_match ) {

			$s_img_tag   = $a_match[0];
			$s_img_class = $a_match[3];
			$s_src       = $a_match[7];

			// Escape and get the source URL.
			$s_clean_url = f_pmh_get_clean_url( $s_src );
			$s_file_name = basename( $s_clean_url );

			// Get basename extension.
			$s_file_ext = pathinfo( $s_file_name, PATHINFO_EXTENSION );

			// If mp3 file.
			if ( $s_file_ext === 'mp3' ) {

				$a_switches_1[] = $s_img_tag;
				$a_switches_2[] = "[audio src=\"{$s_clean_url}\"]";
			}

			// Image file.
			else {

				$i_img_id         = (int) str_replace( ' size-full wp-image-', '', $s_img_class );
				$i_drupal_file_id = $i_img_id ? (int) get_post_meta( $i_img_id, 'fid', true ) : false;

				if ( $i_drupal_file_id && isset( $a_drupal_content_media_data[ $i_drupal_file_id ] ) ) {

					$a_switches_1[] = $s_img_tag;
					$a_switches_2[] = f_pmh_img_replacement_html( $i_img_id, $a_drupal_content_media_data[ $i_drupal_file_id ] );
				}
			}
		}

		if ( $a_switches_1 ) {

			$s_new_content = str_replace( $a_switches_1, $a_switches_2, $s_content );
		}
	}

	/**
	 * @TODO: enable this when the content is ready.
	 */
	f_pmh_flag_object_corrected( $i_post_id, 'post' );

	// Check if new content has value.
	if ( $s_new_content ) {

		// New content blocks.
		$a_new_content_blocks = '';

		// Parse blocks.
		$a_post_blocks_array = parse_blocks( $s_new_content );

		// Loop each block and force_balance_tags if blockName is null.
		foreach ( $a_post_blocks_array as $a_block ) {

			// If blockName is null.
			if ( is_null( $a_block['blockName'] ) ) {

				$balanced_tags = force_balance_tags( $a_block['innerHTML'] );

				// Force balance tags.
				$a_new_content_blocks .= serialize_block( array(
					'blockName'    => null,
					'attrs'        => array(),
					'innerContent' => array( $balanced_tags ),
				) );
			} else {

				$a_new_content_blocks .= serialize_block( $a_block );
			}
		}

		// Clean empty p tags.
		$pattern = "/<p[^>]*><\\/p[^>]*>/";
		$s_new_content = preg_replace( $pattern, '', $a_new_content_blocks );
	}

	return $s_new_content;
}

/**
 * Get Drupal node content.
 *
 * @param int $i_drupal_id
 * @return array
 */
function f_pmh_get_drupal_content_media_data( int $i_drupal_id ) {

	global $fgd2wpp;

	// Simulate running importer.
	ob_start();
	$fgd2wpp->importer();
	ob_get_clean();

	$a_drupal_media_data = array();

	if ( $fgd2wpp->drupal_connect() ) {

		// Get post content.
		$s_sql_query = "
			SELECT
				field_body_value AS content

			FROM
				field_revision_field_body

			WHERE
				entity_id = ({$i_drupal_id})

			ORDER BY
				revision_id
			DESC

			LIMIT 1
		";

		$a_rows = $fgd2wpp->drupal_query( $s_sql_query );

		if ( count( $a_rows ) > 0 && $a_rows[0]['content'] ) {

			// Drupal content.
			$s_content = $a_rows[0]['content'];

			$a_matches = array();

			preg_match_all( '/\[\[(\{.*?"type":"media".*?\})\]\]/', $s_content, $a_matches, PREG_SET_ORDER );

			if ( $a_matches ) {

				foreach ( $a_matches as $a_match ) {

					$a_match_data = json_decode( $a_match[1], true );

					$a_drupal_media_data[ $a_match_data['fid'] ] = $a_match_data;
				}

			}
		}
	}

	/* Debug
	echo "<pre>";
	var_dump( $a_drupal_media_data );
	echo "</pre>";
	exit;
	 */

	return $a_drupal_media_data;
}

/**
 * Replace image html with block markup.
 *
 * @param integer $i_img_id
 * @param array $a_drupal_media_data
 *
 * @return string
 */
function f_pmh_img_replacement_html( int $i_img_id, $a_drupal_media_data ) {

	// Image size.
	$s_image_size = 'full';

	// Drupal class.
	$s_drupal_class = isset( $a_drupal_media_data['attributes']['class'] ) ? $a_drupal_media_data['attributes']['class'] : '';
	$a_drupal_class = explode( ' ', $s_drupal_class );

	// Block attributes.
	$a_block_attributes = array(
		'id'              => $i_img_id,
		'sizeSlug'        => $s_image_size,
		'linkDestination' => 'none',
	);

	// Default figure class.
	$a_figure_class = array(
		'wp-block-image',
		'size-full',
	);

	// Detect Drupal class and add align class.
	if ( in_array( 'file-full-width', $a_drupal_class, true ) ) {
		$a_block_attributes['align'] = 'wide';
		$a_figure_class[]            = 'alignwide';
	} elseif ( in_array( 'file-browser-width', $a_drupal_class, true ) ) {
		$a_block_attributes['align'] = 'full';
		$a_figure_class[]            = 'alignfull';
	} elseif ( in_array( 'media-wysiwyg-align-left', $a_drupal_class, true ) || in_array( 'media-image_on_left', $a_drupal_class, true ) ) {
		$a_block_attributes['align'] = 'left';
		$a_figure_class[]            = 'alignleft';
	} elseif ( in_array( 'media-wysiwyg-align-right', $a_drupal_class, true ) || in_array( 'media-image_on_right', $a_drupal_class, true ) ) {
		$a_block_attributes['align'] = 'right';
		$a_figure_class[]            = 'alignright';
	}

	// Figure class.
	$s_figure_class = implode( ' ', $a_figure_class );

	// Image url.
	$s_attachment_url = wp_get_attachment_image_url( $i_img_id, $s_image_size );

	// Get alt text.
	$s_attachment_alt = get_post_meta( $i_img_id, '_wp_attachment_image_alt', true );

	// Image class.
	$s_img_class = wp_sprintf( 'wp-image-%s', $i_img_id );

	// Get post excerpt as caption.
	$s_attachment_caption = get_the_excerpt( $i_img_id );

	// Caption element.
	$s_caption_element = $s_attachment_caption ? wp_sprintf(
		'<figcaption class="wp-element-caption">%s</figcaption>',
		$s_attachment_caption
	) : '';

	// Block args.
	$image_block = array(
		'blockName'    => 'core/image',
		'attrs'        => $a_block_attributes,
		'innerContent' => array(
			wp_sprintf(
				'<figure class="%s">
					<img src="%s" alt="%s" class="%s"/>
					%s
				</figure>',
				$s_figure_class,
				$s_attachment_url,
				$s_attachment_alt,
				$s_img_class,
				$s_caption_element
			),
		),
	);

	// Content
	$content = serialize_block( $image_block );

	// Serialize block.
	return $content;
}

/**
 * Flag key for corrected object.
 *
 * @return string
 */
function s_pmh_get_fixed_flag_key() {

	return apply_filters( 'tw_object_correct_flag_key', 'tw_obj_correct' );
}

/**
 * Flag WordPress object as processed.
 *
 * @param integer $i_object_id
 * @param string $s_object_type (post/term)
 * @return void
 */
function f_pmh_flag_object_corrected( int $i_object_id, string $s_object_type ) {

	$a_object_types = array( 'post', 'term' );
	$s_flag_key     = s_pmh_get_fixed_flag_key();
	$b_flag_value   = true;

	if ( in_array( $s_object_type, $a_object_types ) ) {

		if ( 'post' === $s_object_type ) {

			update_post_meta( $i_object_id, $s_flag_key, $b_flag_value );
		}

		if ( 'term' === $s_object_type ) {

			update_term_meta( $i_object_id, $s_flag_key, $b_flag_value );
		}
	}
}

/**
 * Cron name.
 *
 * @return void
 */
function s_pmh_get_cron_name() {

	$s_cron_name = 'pmh_cron';

	return $s_cron_name;
}

/**
 * Handle form submission.
 *
 * @return void
 */
function f_pmh_save_settings_cron() {

	$_a_enabled_crons = isset( $_GET['pmh-cron'] ) ? $_GET['pmh-cron'] : array();
	$a_enabled_crons  = array();
	$a_allowed_values = array( 'media', 'posts' );

	foreach( $_a_enabled_crons as $i => $_s_enabled_cron ) {

		if ( in_array( $_s_enabled_cron, $a_allowed_values ) ) {

			$a_enabled_crons[] = $a_allowed_values[ array_search( $_s_enabled_cron, $a_allowed_values ) ];
		}
	}

	update_option( 'pmh_enabled_crons', $a_enabled_crons );

	$i_timestamp = wp_next_scheduled( s_pmh_get_cron_name() );

	if ( $a_enabled_crons && ! $i_timestamp ) {

		wp_schedule_event( time(), 'hourly', s_pmh_get_cron_name() );

	} elseif ( ! $a_enabled_crons && $i_timestamp ) {

		wp_unschedule_event( $i_timestamp, s_pmh_get_cron_name() );
	}

	wp_redirect( admin_url( '/admin.php?page=pmh-cron' ) );

	exit;
}
add_action( 'admin_post_pmh-save-settings-cron', 'f_pmh_save_settings_cron' );

/**
 * Run PMH cron.
 *
 * @return void
 */
function f_pmh_cron_run() {

	$a_pmh_enabled_crons = (array) get_option( 'pmh_enabled_crons', array() );

	if ( in_array( 'media', $a_pmh_enabled_crons ) ) {

		f_ajax_pmh_media_fix_run();
	}

	if ( in_array( 'posts', $a_pmh_enabled_crons ) ) {

		f_ajax_pmh_posts_fix_run();
	}
}
add_action( s_pmh_get_cron_name(), 'f_pmh_cron_run' );

/**
 * Performance helper.
 * Use when post edit not loading.
 *
 * @return void
 */
function pmh_disable_post_type_support() {
	remove_post_type_support( 'post', 'custom-fields' ); // DINKUM: Remove in production.
	remove_post_type_support( 'episode', 'custom-fields' ); // DINKUM: Remove in production.
}
add_action( 'admin_init', 'pmh_disable_post_type_support' );
