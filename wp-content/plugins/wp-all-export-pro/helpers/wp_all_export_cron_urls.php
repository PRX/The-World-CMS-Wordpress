<?php

/**
 * The cron URLs for an export.
 *
 * One builder, because there were two: the scheduling dialog's template composed
 * these by hand and the settings screen offered none at all, so the only place a
 * customer could read a working URL was a modal on another page.
 *
 * Every export can be driven by cron. The endpoint refuses on run state alone,
 * never on what the export contains, so there is no eligibility argument here.
 *
 * @param  string  $cron_job_key  The secret key from the settings screen.
 * @param  int     $export_id
 * @return array<string,string>   Action name => URL.
 */
function wp_all_export_cron_urls($cron_job_key, $export_id) {
	$base = site_url('/wp-load.php') . '?export_key=' . rawurlencode(is_scalar($cron_job_key) ? (string) $cron_job_key : '')
		. '&export_id=' . rawurlencode((string) $export_id);

	return array(
		// Both, always: the trigger starts a run and the processing URL advances
		// it, so a cron job set up with only the first never finishes an export.
		'trigger'    => $base . '&action=trigger',
		'processing' => $base . '&action=processing',
	);
}
