<?php
$wp_uploads = wp_upload_dir();
$uploads_basedir = $wp_uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_EXPORT_UPLOADS_BASE_DIRECTORY;
?>
<form method="post" action="<?php echo esc_url($tab_url[$current_tab]); ?>" class="settings wpae-set__form">
    <?php wp_nonce_field('edit-settings', '_wpnonce_edit-settings') ?>
    <input type="hidden" name="is_settings_submitted" value="1" />
    <input type="hidden" name="settings_tab" value="files" />

    <div class="wpallexport-content-section wpae-set__panel">

        <?php wpae_settings_group(__('Storage', 'wp_all_export_plugin')); ?>

        <?php
        wpae_settings_toggle('secure', ! empty($post['secure']), array(
            'label'    => __('Secure mode', 'wp_all_export_plugin'),
            'checkbox' => __('Randomize folder names', 'wp_all_export_plugin'),
            'help'     => sprintf(
                __('If enabled, exported files and temporary files will be saved in a folder with a randomized name in <code>%s</code>. If disabled, exported files will be saved in the Media Library.', 'wp_all_export_plugin'),
                esc_html(preg_replace('%.*wp-content%', 'wp-content', wp_normalize_path($uploads_basedir)))
            ),
        ));
        ?>

        <?php require(PMXE_Plugin::ROOT_DIR . '/views/admin/settings/_save_actions.php'); ?>
    </div>
</form>
