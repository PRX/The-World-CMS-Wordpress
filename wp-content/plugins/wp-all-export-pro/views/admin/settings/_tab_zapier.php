<?php
/* No form: the key is written by the generate_zapier_api_key ajax call, and the
 * field only ever displays it. */
?>
<div class="wpallexport-content-section wpae-set__panel">

    <?php wpae_settings_group(
        __('Zapier integration', 'wp_all_export_plugin'),
        sprintf(
            __('Zapier acts as a middle man between WP All Export and hundreds of other popular apps. To get started go to Zapier.com, create an account, and make a new Zap. Read more: <a target="_blank" href="%1$s">%1$s</a>', 'wp_all_export_plugin'),
            'https://zapier.com/zapbook/wp-all-export-pro/'
        )
    ); ?>

    <?php
    $api_id = wpae_settings_field_id('zapier_api_key');
    ob_start();
    ?>
    <span class="wpae-set-inputgroup">
        <input type="text" id="<?php echo esc_attr($api_id); ?>"
            class="wpae-set-input wpae-set-input--key" value="<?php echo esc_attr($post['zapier_api_key']); ?>" readonly
            data-wpae-zapier-key
            aria-describedby="<?php echo esc_attr($api_id); ?>-help" />
        <button type="button" class="wpae-set-iconbtn wpae-set-copy" data-target="<?php echo esc_attr($api_id); ?>"><span data-copy-label><?php esc_html_e('Copy', 'wp_all_export_plugin'); ?></span><span class="screen-reader-text"> <?php esc_html_e('API key', 'wp_all_export_plugin'); ?></span></button>
        <button type="button" class="wpae-set-iconbtn wpae-set-iconbtn--danger" data-wpae-generate-zapier-key><?php esc_html_e('Generate new key', 'wp_all_export_plugin'); ?></button>
    </span>
    <?php
    $control = ob_get_clean();

    wpae_settings_row(array(
        'label'   => __('API key', 'wp_all_export_plugin'),
        'for'     => $api_id,
        'control' => $control,
        'help'    => __('Changing the key will require you to update your existing Zaps on Zapier.', 'wp_all_export_plugin'),
    ));
    ?>
</div>
