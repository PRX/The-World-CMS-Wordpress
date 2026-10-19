<?php
$manage_url = admin_url('admin.php?page=pmxe-admin-manage');
?>
<form method="post" action="<?php echo esc_url($tab_url[$current_tab]); ?>" class="settings wpae-set__form">
    <?php wp_nonce_field('edit-settings', '_wpnonce_edit-settings') ?>
    <input type="hidden" name="is_settings_submitted" value="1" />
    <input type="hidden" name="settings_tab" value="cron" />
    <input type="hidden" name="regenerate_cron_job_key" value="0" data-wpae-regenerate-flag />

    <div class="wpallexport-content-section wpae-set__panel">

        <?php wpae_settings_group(__('Access', 'wp_all_export_plugin')); ?>

        <?php
        $key_id = wpae_settings_field_id('cron_job_key');
        ob_start();
        ?>
        <span class="wpae-set-inputgroup">
            <input type="text" id="<?php echo esc_attr($key_id); ?>" name="cron_job_key"
                class="wpae-set-input wpae-set-input--key" value="<?php echo esc_attr($post['cron_job_key']); ?>"
                spellcheck="false" autocomplete="off" pattern="[A-Za-z0-9._\-]+" maxlength="64"
                aria-describedby="<?php echo esc_attr($key_id); ?>-help" />
            <button type="button" class="wpae-set-iconbtn wpae-set-copy" data-target="<?php echo esc_attr($key_id); ?>"><span data-copy-label><?php esc_html_e('Copy', 'wp_all_export_plugin'); ?></span><span class="screen-reader-text"> <?php esc_html_e('secret key', 'wp_all_export_plugin'); ?></span></button>
            <button type="button" class="wpae-set-iconbtn wpae-set-iconbtn--danger" data-wpae-regenerate
                aria-label="<?php esc_attr_e('Regenerate secret key', 'wp_all_export_plugin'); ?>"><?php esc_html_e('Regenerate', 'wp_all_export_plugin'); ?></button>
        </span>
        <?php
        $control = ob_get_clean();

        wpae_settings_row(array(
            'label'   => __('Secret key', 'wp_all_export_plugin'),
            'for'     => $key_id,
            'control' => $control,
            'help'    => sprintf(
                /* translators: %s: link to the Manage Exports screen. */
                wp_kses_post(__('Authenticates the cron URLs. Each export has its own pair. Open its scheduling dialog on <a href="%s">Manage Exports</a> to copy them. Changing this key invalidates every cron job you have already set up.', 'wp_all_export_plugin')),
                esc_url($manage_url)
            ),
        ));
        ?>

        <?php require(PMXE_Plugin::ROOT_DIR . '/views/admin/settings/_save_actions.php'); ?>
    </div>
</form>
