<form name="client-mode-settings" method="post" action="<?php echo esc_url($tab_url[$current_tab]); ?>" class="client-mode-settings wpae-set__form">
    <div class="wpallexport-content-section wpae-set__panel">

        <?php wpae_settings_group(
            __('Client Mode', 'wp_all_export_plugin'),
            __('Roles with access can run the exports you allow, and nothing else.', 'wp_all_export_plugin')
        ); ?>

        <?php
        ob_start();
        ?>
        <fieldset class="wpae-set-checklist">
            <legend class="screen-reader-text"><?php esc_html_e('Roles with access', 'wp_all_export_plugin'); ?></legend>
            <?php foreach ($roles as $key => $role) {
                $roleObject = get_role($key);
                ?>
                <label class="wpae-set-check" for="role-<?php echo esc_attr($key); ?>">
                    <input type="checkbox" id="role-<?php echo esc_attr($key); ?>"
                           value="<?php echo esc_attr($key); ?>"
                        <?php if(is_array($post['client_mode_roles']) && in_array($key, $post['client_mode_roles'])) {?> checked="checked" <?php } ?>
                        <?php if (PMXE_Admin_Settings::role_can_manage_exports($roleObject)) {?> disabled="disabled" checked="checked" <?php }?>
                           name="client_mode_roles[]"/>
                    <span><?php echo esc_html($role['name']); ?></span>
                </label>
            <?php } ?>
        </fieldset>
        <?php
        $control = ob_get_clean();

        wpae_settings_row(array(
            'label'   => __('Roles with access', 'wp_all_export_plugin'),
            'control' => $control,
            'class'   => 'wpae-set-row--stack',
        ));
        ?>

        <?php wp_nonce_field('edit-client-mode-settings', '_wpnonce_edit-client_mode_settings') ?>
        <input type="hidden" name="is_client_mode_submitted" value="1"/>

        <?php require(PMXE_Plugin::ROOT_DIR . '/views/admin/settings/_save_actions.php'); ?>
    </div>
</form>
