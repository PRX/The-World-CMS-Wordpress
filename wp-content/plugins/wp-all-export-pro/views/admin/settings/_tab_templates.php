<form class="settings wpae-set__form" method="post" action="<?php echo esc_url($tab_url[$current_tab]); ?>" enctype="multipart/form-data">
    <?php wp_nonce_field('delete-templates', '_wpnonce_delete-templates') ?>
    <input type="hidden" name="is_templates_submitted" value="1"/>

    <div class="wpallexport-content-section wpae-set__panel">

        <?php wpae_settings_group(
            __('Saved templates', 'wp_all_export_plugin'),
            __('A template is a saved export configuration. Export one to reuse it on another site, or to keep a copy before you change an export.', 'wp_all_export_plugin')
        ); ?>

        <?php $templates = new PMXE_Template_List();
        $templates->getBy()->convertRecords() ?>
        <?php if ($templates->total()): ?>
            <fieldset class="wpae-set-templates">
                <legend class="screen-reader-text"><?php esc_html_e('Saved templates', 'wp_all_export_plugin'); ?></legend>
                <ul class="wpae-set-templates__list">
                    <?php foreach ($templates as $t): ?>
                        <li>
                            <label class="wpae-set-check" for="template-<?php echo esc_attr($t->id); ?>">
                                <input id="template-<?php echo esc_attr($t->id); ?>" type="checkbox" name="templates[]" value="<?php echo esc_attr($t->id); ?>" />
                                <span><?php echo esc_html(wp_all_export_clear_xss($t->name)); ?></span>
                            </label>
                        </li>
                    <?php endforeach ?>
                </ul>
            </fieldset>
            <p class="wpae-set-actions">
                <button type="submit" class="button button-primary" name="export_templates" value="1"><?php esc_html_e('Export selected', 'wp_all_export_plugin') ?></button>
                <button type="submit" class="button wpae-set-danger" name="delete_templates" value="1"
                    data-wpae-confirm="<?php esc_attr_e('Delete the selected templates? This cannot be undone. Exports already created from them are not affected.', 'wp_all_export_plugin'); ?>"
                    data-wpae-confirm-label="<?php esc_attr_e('Delete templates', 'wp_all_export_plugin'); ?>"><?php esc_html_e('Delete selected', 'wp_all_export_plugin') ?></button>
            </p>
        <?php else: ?>
            <div class="wpae-set-empty">
                <p class="wpae-set-empty__title"><?php esc_html_e('No saved templates yet', 'wp_all_export_plugin') ?></p>
                <p class="wpae-set-empty__body"><?php esc_html_e('When you save an export as a template, it appears here, ready to export to another site.', 'wp_all_export_plugin') ?></p>
            </div>
        <?php endif ?>

        <?php wpae_settings_group(__('Import from a file', 'wp_all_export_plugin')); ?>

        <?php
        ob_start();
        ?>
        <span class="wpae-set-inputgroup wpae-set-inputgroup--file">
            <input type="file" id="wpae-set-template-file" name="template_file" accept=".txt"
                aria-describedby="wpae-set-template-file-help" />
            <button type="submit" class="button" name="import_templates" value="1"><?php esc_html_e('Import templates', 'wp_all_export_plugin') ?></button>
        </span>
        <?php
        $control = ob_get_clean();

        wpae_settings_row(array(
            'label'   => __('Template file', 'wp_all_export_plugin'),
            'for'     => 'wpae-set-template-file',
            'control' => $control,
            'help'    => __('Accepts a .txt file exported from WP All Export. Files exported from WP All Import won\'t work here.', 'wp_all_export_plugin'),
        ));
        ?>
    </div>
</form>
