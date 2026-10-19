<div class="wpallexport-content-section wpae-set__panel">

    <?php wpae_settings_group(
        __('Get help', 'wp_all_export_plugin'),
        __('Support answers faster when a system report is attached. It saves a round trip asking for your versions and limits.', 'wp_all_export_plugin')
    ); ?>

    <div class="wpae-set-actions">
        <a class="button button-primary" href="https://www.wpallimport.com/support/" target="_blank"><?php esc_html_e('Contact support', 'wp_all_export_plugin'); ?></a>
        <a class="button" href="https://www.wpallimport.com/documentation/" target="_blank"><?php esc_html_e('Documentation', 'wp_all_export_plugin'); ?></a>
    </div>

    <?php wpae_settings_group(__('System report', 'wp_all_export_plugin')); ?>

    <?php
    ob_start();
    ?>
    <div class="wpae-set-report">
        <textarea id="wpae-set-system-report" class="wpae-set-report__text" rows="14" readonly
            aria-describedby="wpae-set-system-report-help"><?php echo esc_textarea($system_report); ?></textarea>
        <p class="wpae-set-actions">
            <button type="button" class="button wpae-set-copy" data-target="wpae-set-system-report"><span data-copy-label><?php esc_html_e('Copy', 'wp_all_export_plugin'); ?></span><span class="screen-reader-text"> <?php esc_html_e('system report', 'wp_all_export_plugin'); ?></span></button>
        </p>
    </div>
    <?php
    $control = ob_get_clean();

    wpae_settings_row(array(
        'label'   => __('Environment', 'wp_all_export_plugin'),
        'for'     => 'wpae-set-system-report',
        'control' => $control,
        'help'    => __('Versions, server limits, and active plugins. No license keys, secret keys, site URLs, or export data. Your theme and plugin list are included, which on a client site may identify the build.', 'wp_all_export_plugin'),
        'class'   => 'wpae-set-row--stack',
    ));
    ?>
</div>
