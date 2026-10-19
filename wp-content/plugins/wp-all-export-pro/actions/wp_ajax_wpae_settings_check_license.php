<?php

function pmxe_wp_ajax_wpae_settings_check_license() {

	if ( ! check_ajax_referer( 'wp_all_export_secure', 'security', false ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check', 'wp_all_export_plugin' ) ) );
	}

	if ( ! current_user_can( PMXE_Plugin::$capabilities ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security check', 'wp_all_export_plugin' ) ) );
	}

	$options   = PMXE_Plugin::getInstance()->getOption();
	$licensing = \Wpae\App\License\Licensing::instance();

	$key = empty( $options['license'] ) ? '' : PMXE_Plugin::decode( $options['license'] );

	$stored = isset( $options['license_status'] ) && is_scalar( $options['license_status'] )
		? (string) $options['license_status']
		: '';

	if ( ! $key ) {
		$status = '';
	} elseif ( '' === $stored || \Wpae\App\Gate\Gate::instance()->blocks() || $licensing->state()->isDueForRecheck() || $licensing->shouldForceRecheck() ) {
		// recheck(), not check(): only recheck() opens the grace window.
		$result = $licensing->recheck( false );
		$status = $result['status'] === null ? false : $result['status']->license();
	} else {
		$status = $stored;
	}

	$status = wpae_settings_chip_literal(
		$status,
		isset( $result ) ? $result['status'] : $licensing->state()->answerFor( $key )
	);

	if ( ! $key ) {
		$network = wpae_settings_network_chip( \Wpae\App\License\Licensing::instance()->networkLicence(), \Wpae\App\Gate\Gate::instance() );

		wp_send_json_success( wpae_settings_with_notice( null !== $network ? $network : array(
			'state' => 'empty',
			'label' => esc_html__( 'Not activated', 'wp_all_export_plugin' ),
			'help'  => '',
		) ) );
	}

	// After the check, not before: the check rewrites the state the gate reads.
	$blocked = \Wpae\App\Gate\Gate::instance()->blocks();

	if ( false === $status || '' === $status || null === $status ) {
		wp_send_json_success( wpae_settings_with_notice( wpae_settings_unreachable_state(
			$blocked,
			\Wpae\App\Gate\Gate::instance()->state() === \Wpae\App\Gate\Gate::STATE_VALID
		) ) );
	}

	// $status is decoded remote JSON, and a non-scalar would fatal on the cast.
	$state = wpae_settings_license_state(
		is_scalar( $status ) ? (string) $status : '',
		$blocked,
		\Wpae\App\Gate\Gate::instance()->state(),
		\Wpae\App\Gate\Gate::instance()->graceUntil()
	);

	$state['blocked'] = $blocked;

	wp_send_json_success( wpae_settings_with_notice( $state ) );
}

/**
 * A sub-site with no key of its own runs on the licence stored on the network's
 * main site, so its chip names that licence rather than "Not activated".
 *
 * @param  array<string,mixed>|null  $main  the main site's licence row, from Licensing::networkLicence()
 * @param  \Wpae\App\Gate\Gate  $gate
 * @return array<string,mixed>|null  null when no network licence covers this site
 */
function wpae_settings_network_chip( $main, $gate ) {
	if ( ! is_array( $main ) || empty( $main['has_key'] ) ) {
		return null;
	}

	$blocked = $gate->blocks();
	$status  = is_scalar( $main['status'] ) ? (string) $main['status'] : '';

	$chip = '' === $status
		? wpae_settings_unreachable_state( $blocked, $gate->state() === \Wpae\App\Gate\Gate::STATE_VALID )
		: wpae_settings_license_state( $status, $blocked, $gate->state(), $gate->graceUntil() );

	$chip['help']    = wpae_settings_chip_help( esc_html__( 'This site uses the license saved on the main site of this network.', 'wp_all_export_plugin' ), $chip['help'] );
	$chip['blocked'] = $blocked;

	return $chip;
}

/**
 * The server answers a disabled licence `license: invalid, status: disabled`, and
 * the legacy literal alone would tell the customer to check for a typo.
 *
 * @param  mixed  $literal
 * @param  \Wpae\App\License\LicenseStatus|null  $answer  the answer for the stored key
 * @return mixed
 */
function wpae_settings_chip_literal( $literal, $answer ) {
	if ( 'invalid' === $literal
		&& $answer instanceof \Wpae\App\License\LicenseStatus
		&& \Wpae\App\License\LicenseStatus::DISABLED === $answer->status() ) {
		return 'disabled';
	}

	return $literal;
}

function wpae_settings_unreachable_state( $blocked = false, $stateIsValid = true ) {
	if ( $blocked ) {
		return array(
			'state'   => 'error',
			'label'   => esc_html__( 'Not active on this site', 'wp_all_export_plugin' ),
			'help'    => esc_html__( "Couldn't reach the licensing server, so this key couldn't be re-checked. This site stays unlicensed until it can be.", 'wp_all_export_plugin' ),
			'blocked' => true,
		);
	}

	// Keyed on the state, not the block: `blocked` is false for the whole grace window.
	if ( ! $stateIsValid ) {
		return array(
			'state'   => 'warning',
			'label'   => esc_html__( 'Status unavailable', 'wp_all_export_plugin' ),
			'help'    => esc_html__( "Couldn't reach the licensing server, so this key couldn't be re-checked. Your license is unchanged by that; see the notice above for where it stands.", 'wp_all_export_plugin' ),
			'blocked' => false,
		);
	}

	return array(
		'state'   => 'unknown',
		'label'   => esc_html__( 'Status unavailable', 'wp_all_export_plugin' ),
		'help'    => esc_html__( "Couldn't reach the licensing server. Your license is unaffected. Retry when you have a connection.", 'wp_all_export_plugin' ),
		'blocked' => false,
	);
}

/**
 * @param  string  $status  the legacy EDD literal
 * @param  string  $gateState  the gate's own state, which the literal alone cannot name
 */
function wpae_settings_license_state( $status, $blocked = false, $gateState = '', $graceUntil = 0 ) {
	$portal = 'https://www.wpallimport.com/portal/';

	// The gate rules the plugin's own licence and nothing else, so a Scheduling
	// or add-on key must never be told that exports are about to stop.
	$consequence = ( new \Wpae\App\Gate\Messages() )->chipConsequence( $gateState, $graceUntil );

	$map = array(
		'valid'               => array( 'active', esc_html__( 'Active', 'wp_all_export_plugin' ), '' ),
		'expired'             => array( 'error', esc_html__( 'Expired', 'wp_all_export_plugin' ), sprintf( __( 'This license has expired. Renew it in the <a href="%s" target="_blank">customer portal</a>.', 'wp_all_export_plugin' ), esc_url( $portal ) ) ),
		'invalid'             => array( 'error', esc_html__( 'Not recognized', 'wp_all_export_plugin' ), sprintf( __( 'This key was not recognized. Check for a typo, or copy it again from the <a href="%s" target="_blank">customer portal</a>.', 'wp_all_export_plugin' ), esc_url( $portal ) ) ),
		'disabled'            => array( 'error', esc_html__( 'Disabled', 'wp_all_export_plugin' ), sprintf( __( 'This license has been disabled. Contact support via the <a href="%s" target="_blank">customer portal</a>.', 'wp_all_export_plugin' ), esc_url( $portal ) ) ),
		'inactive'            => array( 'warning', esc_html__( 'Not activated', 'wp_all_export_plugin' ), esc_html__( 'The key is valid but has not been activated on this site yet. Choose Activate to finish.', 'wp_all_export_plugin' ) ),
		'site_inactive'       => array( 'warning', esc_html__( 'Not activated on this site', 'wp_all_export_plugin' ), esc_html__( 'This key is valid but has not been activated for this domain. Choose Activate to finish.', 'wp_all_export_plugin' ) ),
		'item_name_mismatch'  => array( 'error', esc_html__( 'Wrong product', 'wp_all_export_plugin' ), esc_html__( 'This key belongs to a different product. Check that you pasted the WP All Export key.', 'wp_all_export_plugin' ) ),
		'no_activations_left' => array( 'error', esc_html__( 'Site limit reached', 'wp_all_export_plugin' ), sprintf( __( 'This license has reached its site limit. Deactivate a site you no longer use in the <a href="%s" target="_blank">customer portal</a>.', 'wp_all_export_plugin' ), esc_url( $portal ) ) ),
	);

	$map['active sites limit reached'] = $map['no_activations_left'];

	// The frozen EDD literal can say "valid" while the gate refuses the site.
	if ( $blocked && isset( $map[ $status ] ) && 'active' === $map[ $status ][0] ) {
		return array(
			'state' => 'error',
			'label' => esc_html__( 'Not active on this site', 'wp_all_export_plugin' ),
			'help'  => wpae_settings_chip_help( $consequence, sprintf( __( 'The key itself checks out, but this site is not licensed to run WP All Export. Check the license in the <a href="%s" target="_blank">customer portal</a>.', 'wp_all_export_plugin' ), esc_url( $portal ) ) ),
		);
	}

	if ( isset( $map[ $status ] ) ) {
		return array(
			'state' => $map[ $status ][0],
			'label' => $map[ $status ][1],
			'help'  => wpae_settings_chip_help( $consequence, $map[ $status ][2] ),
		);
	}

	return array(
		'state' => 'error',
		'label' => esc_html__( 'Not active', 'wp_all_export_plugin' ),
		'help'  => wpae_settings_chip_help( $consequence, sprintf( __( 'The licensing server reported a problem with this key. Check it in the <a href="%s" target="_blank">customer portal</a>.', 'wp_all_export_plugin' ), esc_url( $portal ) ) ),
	);
}

/**
 * The consequence leads: the chip's label already names the diagnosis.
 *
 * @return string
 */
function wpae_settings_chip_help( $consequence, $help ) {
	return trim( $consequence . ' ' . $help );
}

function wpae_settings_with_notice( $payload ) {
	$gate    = \Wpae\App\Gate\Gate::instance();
	$state   = $gate->state();
	$blocked = $gate->blocks();
	$shown   = $state !== \Wpae\App\Gate\Gate::STATE_VALID;

	$payload['notice'] = array(
		'shown'   => $shown,
		'blocked' => $blocked,
		'html'    => $shown
			? wp_kses_post( $gate->messages()->settingsNotice( $state, $gate->stateReason(), $gate->graceUntil(), $blocked ) )
			: '',
	);

	return $payload;
}
