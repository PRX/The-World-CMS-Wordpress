<?php
$portal_url     = 'https://www.wpallimport.com/portal/';
$scheduling_url = 'https://www.wpallimport.com/portal/automatic-scheduling/';
?>

<div class="wpallexport-content-section wpae-set__panel">

    <?php wpae_settings_group(
        __('License keys', 'wp_all_export_plugin'),
        __('Keys are stored on this site and never shown again once saved.', 'wp_all_export_plugin')
    ); ?>

    <form method="post" action="" class="settings wpae-set__form">
        <?php wp_nonce_field('edit-license', '_wpnonce_edit-license') ?>
        <input type="hidden" name="is_license_submitted" value="1" />

        <?php
        $key_raw   = isset($post['license']) && is_scalar($post['license']) ? (string) $post['license'] : '';
        $key_value = '' === $key_raw ? '' : PMXE_Plugin::decode($key_raw);
        $has_key   = '' !== $key_value;
        $is_active = $has_key && ! empty($post['license_status']) && 'valid' === $post['license_status'];
        $key_hint  = $has_key ? wpae_settings_mask_key($key_value) : '';
        $field_id  = wpae_settings_field_id('license');
        $network   = $has_key ? null : wpae_settings_network_chip(\Wpae\App\License\Licensing::instance()->networkLicence(), \Wpae\App\Gate\Gate::instance());

        ob_start();
        ?>
        <span class="wpae-set-inputgroup">
            <input type="password" id="<?php echo esc_attr($field_id); ?>"
                name="license"
                class="wpae-set-input wpae-set-input--key"
                value=""
                autocomplete="off" spellcheck="false"
                placeholder="<?php echo $has_key ? esc_attr($key_hint) : esc_attr__('Paste your license key', 'wp_all_export_plugin'); ?>"
                aria-describedby="<?php echo esc_attr($field_id); ?>-keystate <?php echo esc_attr($field_id); ?>-help" />
            <span id="<?php echo esc_attr($field_id); ?>-keystate" class="screen-reader-text"><?php
                $tail = wpae_settings_key_tail($key_value);
                echo $has_key
                    ? ('' === $tail
                        ? esc_html__('A license key is saved. Type a new key to replace it.', 'wp_all_export_plugin')
                        : esc_html(sprintf(__('A license key ending %s is saved. Type a new key to replace it.', 'wp_all_export_plugin'), $tail)))
                    : esc_html__('No license key saved.', 'wp_all_export_plugin');
            ?></span>
        </span>
        <span class="wpae-set-status" id="<?php echo esc_attr($field_id); ?>-status" role="status" aria-live="polite"
            data-license-context="main" data-has-key="<?php echo $has_key ? '1' : '0'; ?>">
            <span class="screen-reader-text"><?php esc_html_e('WP All Export license status:', 'wp_all_export_plugin'); ?> </span>
            <?php // With no key the check never runs, so a pending chip would pulse for ever. ?>
            <span class="wpae-set-chip wpae-set-chip--<?php echo esc_attr($has_key ? 'pending' : ($network ? $network['state'] : 'unknown')); ?>" id="<?php echo esc_attr($field_id); ?>-chip"
                <?php echo $has_key ? 'data-wpae-license-chip' : ''; ?>><?php
                if ($key_value) {
                    esc_html_e('Checking…', 'wp_all_export_plugin');
                } else {
                    echo $network ? esc_html($network['label']) : esc_html__('Not activated', 'wp_all_export_plugin');
                }
            ?></span>
            <span class="wpae-set-license-help<?php echo $network && in_array($network['state'], array('active', 'unknown', 'warning'), true) ? ' is-neutral' : ''; ?>" data-license-help="main" data-wpae-license-help><?php
                echo $network ? wp_kses_post($network['help']) : '';
            ?></span>
        </span>
        <button type="submit" class="button button-primary wpae-set__license-submit"
            name="pmxe_license_activate" value="1"
            aria-label="<?php esc_attr_e('Activate WP All Export license', 'wp_all_export_plugin'); ?>"
            aria-describedby="<?php echo esc_attr($field_id); ?>-chip"
            data-wpae-requires="<?php echo esc_attr($field_id); ?>"
            <?php disabled(!$has_key || $is_active); ?>><?php esc_html_e('Activate', 'wp_all_export_plugin'); ?></button>
        <?php if ($has_key): ?>
            <button type="submit" class="button wpae-set-danger" name="pmxe_license_remove" value="1"
                aria-label="<?php esc_attr_e('Remove WP All Export license', 'wp_all_export_plugin'); ?>"
                data-wpae-confirm="<?php esc_attr_e('Remove this license key? No export will run, and plugin updates will stop, until a valid key is entered again. Your exports, settings and data are kept.', 'wp_all_export_plugin'); ?>"
                data-wpae-confirm-label="<?php esc_attr_e('Remove license', 'wp_all_export_plugin'); ?>"><?php esc_html_e('Remove', 'wp_all_export_plugin'); ?></button>
        <?php endif; ?>
        <?php
        $control = ob_get_clean();

        wpae_settings_row(array(
            'label'   => __('WP All Export', 'wp_all_export_plugin'),
            'for'     => $field_id,
            'control' => $control,
            'class'   => 'wpae-set-row--license',
            'help'    => sprintf(
                __('A valid license key is required to use WP All Export Pro and to access plugin updates. You can use your license key on an unlimited number of websites. Do not distribute your license key to 3rd parties. You can get your license key in the <a href="%s" target="_blank">customer portal</a>.', 'wp_all_export_plugin'),
                esc_url($portal_url)
            ),
        ));
        ?>
    </form>

    <?php /* Withheld while the core licence is refused: scheduling cannot open that gate. */ ?>
    <?php if (empty($wpae_gate_blocked)): ?>
    <form method="post" action="" class="settings wpae-set__form">
        <?php wp_nonce_field('edit-license', '_wpnonce_edit-scheduling-license') ?>
        <input type="hidden" name="is_scheduling_license_submitted" value="1" />
        <input type="hidden" name="scheduling_license_limit" value="<?php echo esc_attr(get_option('wpai_wpae_scheduling_license_site_limit', 0)); ?>">

        <?php
        $sched_raw    = isset($post['scheduling_license']) && is_scalar($post['scheduling_license']) ? (string) $post['scheduling_license'] : '';
        $sched_value  = '' === $sched_raw ? '' : PMXE_Plugin::decode($sched_raw);
        $sched_has    = '' !== $sched_value;
        $sched_status = isset($post['scheduling_license_status']) && is_scalar($post['scheduling_license_status']) ? (string) $post['scheduling_license_status'] : '';
        $sched_active = $sched_has && 'valid' === $sched_status;
        $sched_hint   = $sched_has ? wpae_settings_mask_key($sched_value) : '';
        $sched_id     = wpae_settings_field_id('scheduling_license');

        ob_start();
        ?>
        <span class="wpae-set-inputgroup">
            <input type="password" id="<?php echo esc_attr($sched_id); ?>" name="scheduling_license"
                class="wpae-set-input wpae-set-input--key"
                value=""
                autocomplete="off" spellcheck="false"
                placeholder="<?php echo $sched_has ? esc_attr($sched_hint) : esc_attr__('Paste your license key', 'wp_all_export_plugin'); ?>"
                aria-describedby="<?php echo esc_attr($sched_id); ?>-keystate <?php echo esc_attr($sched_id); ?>-help" />
            <span id="<?php echo esc_attr($sched_id); ?>-keystate" class="screen-reader-text"><?php
                $tail = wpae_settings_key_tail($sched_value);
                echo $sched_has
                    ? ('' === $tail
                        ? esc_html__('A license key is saved. Type a new key to replace it.', 'wp_all_export_plugin')
                        : esc_html(sprintf(__('A license key ending %s is saved. Type a new key to replace it.', 'wp_all_export_plugin'), $tail)))
                    : esc_html__('No license key saved.', 'wp_all_export_plugin');
            ?></span>
        </span>
        <?php
        // Rendered static: the wpae_settings_check_license endpoint checks the WP All
        // Export key only, so this chip is never resolved in the browser. The
        // remediation is the same copy the sibling's browser check writes.
        $sched_states = array(
            'expired'             => array('error', __('Expired', 'wp_all_export_plugin'), sprintf(__('This license has expired. Renew it in the <a href="%s" target="_blank">customer portal</a>.', 'wp_all_export_plugin'), esc_url($scheduling_url))),
            'invalid'             => array('error', __('Not recognized', 'wp_all_export_plugin'), sprintf(__('This key was not recognized. Check for a typo, or copy it again from the <a href="%s" target="_blank">customer portal</a>.', 'wp_all_export_plugin'), esc_url($scheduling_url))),
            'disabled'            => array('error', __('Disabled', 'wp_all_export_plugin'), sprintf(__('This license has been disabled. Contact support via the <a href="%s" target="_blank">customer portal</a>.', 'wp_all_export_plugin'), esc_url($scheduling_url))),
            'inactive'            => array('warning', __('Not activated', 'wp_all_export_plugin'), __('The key is valid but has not been activated on this site yet. Choose Activate to finish.', 'wp_all_export_plugin')),
            'site_inactive'       => array('warning', __('Not activated on this site', 'wp_all_export_plugin'), __('This key is valid but has not been activated for this domain. Choose Activate to finish.', 'wp_all_export_plugin')),
            'item_name_mismatch'  => array('error', __('Wrong product', 'wp_all_export_plugin'), __('This key belongs to a different product. Check that you pasted the Automatic Scheduling key.', 'wp_all_export_plugin')),
            'no_activations_left' => array('error', __('Site limit reached', 'wp_all_export_plugin'), sprintf(__('This license has reached its site limit. Deactivate a site you no longer use in the <a href="%s" target="_blank">customer portal</a>.', 'wp_all_export_plugin'), esc_url($scheduling_url))),
        );
        $sched_states['active sites limit reached'] = $sched_states['no_activations_left'];

        if (! $sched_has) {
            $sched_chip = array('unknown', __('Not activated', 'wp_all_export_plugin'), '');
        } elseif ($sched_active) {
            $sched_chip = array('active', __('Active', 'wp_all_export_plugin'), '');
        } elseif (isset($sched_states[$sched_status])) {
            $sched_chip = $sched_states[$sched_status];
        } elseif ('' === (string) $sched_status) {
            $sched_chip = array('warning', __('Not activated', 'wp_all_export_plugin'), __('This key has not been checked yet. Choose Activate to finish.', 'wp_all_export_plugin'));
        } else {
            $sched_chip = array('error', __('Not active', 'wp_all_export_plugin'), sprintf(__('The licensing server reported a problem with this key. Check it in the <a href="%s" target="_blank">customer portal</a>.', 'wp_all_export_plugin'), esc_url($scheduling_url)));
        }
        ?>
        <span class="wpae-set-status wpae-set-status--static" id="<?php echo esc_attr($sched_id); ?>-status" role="status">
            <span class="screen-reader-text"><?php esc_html_e('Automatic Scheduling license status:', 'wp_all_export_plugin'); ?> </span>
            <span class="wpae-set-chip wpae-set-chip--<?php echo esc_attr($sched_chip[0]); ?>" id="<?php echo esc_attr($sched_id); ?>-chip"><?php echo esc_html($sched_chip[1]); ?></span>
            <span class="wpae-set-license-help<?php echo 'warning' === $sched_chip[0] ? ' is-neutral' : ''; ?>" data-license-help="scheduling"><?php echo wp_kses_post($sched_chip[2]); ?></span>
        </span>
        <button type="submit" class="button button-primary wpae-set__license-submit"
            name="pmxe_scheduling_license_activate" value="1"
            aria-label="<?php esc_attr_e('Activate Automatic Scheduling license', 'wp_all_export_plugin'); ?>"
            aria-describedby="<?php echo esc_attr($sched_id); ?>-chip"
            data-wpae-requires="<?php echo esc_attr($sched_id); ?>"
            <?php disabled(!$sched_has || $sched_active); ?>><?php esc_html_e('Activate', 'wp_all_export_plugin'); ?></button>
        <?php if ($sched_has): ?>
            <button type="submit" class="button wpae-set-danger" name="pmxe_scheduling_license_remove" value="1"
                aria-label="<?php esc_attr_e('Remove Automatic Scheduling license', 'wp_all_export_plugin'); ?>"
                data-wpae-confirm="<?php esc_attr_e('Remove this license key? Automatic Scheduling will stop running your exports until a valid key is entered again.', 'wp_all_export_plugin'); ?>"
                data-wpae-confirm-label="<?php esc_attr_e('Remove license', 'wp_all_export_plugin'); ?>"><?php esc_html_e('Remove', 'wp_all_export_plugin'); ?></button>
        <?php endif; ?>
        <?php
        $control = ob_get_clean();

        wpae_settings_row(array(
            'label'   => __('Automatic Scheduling', 'wp_all_export_plugin'),
            'for'     => $sched_id,
            'control' => $control,
            'class'   => 'wpae-set-row--license',
            'help'    => sprintf(
                __('Required to run exports on a schedule without setting up your own cron jobs. <a href="%1$s" target="_blank">Get your key</a>, or <a href="%2$s" class="scheduling-subscribe-link">subscribe</a>.', 'wp_all_export_plugin'),
                esc_url($scheduling_url),
                '#'
            ),
        ));
        ?>
    </form>

        <?php /* Hidden until the subscribe link opens it; deciding by a licence check here costs a request per render. */ ?>
        <?php require_once(PMXE_Plugin::ROOT_DIR . '/src/Scheduling/views/SchedulingActiveSitesLimitUI.php'); ?>
    <?php endif; ?>
</div>
