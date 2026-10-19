<?php
$uploads = wp_upload_dir();
$functions = $uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_EXPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php';
$functions = apply_filters( 'wp_all_export_functions_file_path', $functions );
$functions_content = file_get_contents($functions);
?>
<div class="wpallexport-content-section wpae-set__panel">
    <div class="function-editor">
        <?php wpae_settings_group(__('Function Editor', 'wp_all_export_plugin')); ?>

        <?php require_once(PMXE_Plugin::ROOT_DIR . '/views/admin/shared/function_editor.php');?>
    </div>
</div>
