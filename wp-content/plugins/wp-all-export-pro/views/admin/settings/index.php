<?php
if(!defined('ABSPATH')) {
    die();
}

require_once(PMXE_Plugin::ROOT_DIR . '/views/admin/settings/_fields.php');

$wpae_gate_blocked = ! empty($wpae_gate_blocked);

$tab_files = array(
    'licenses'    => '_tab_licenses.php',
    'cron'        => '_tab_cron.php',
    'files'       => '_tab_files.php',
    'zapier'      => '_tab_zapier.php',
    'templates'   => '_tab_templates.php',
    'functions'   => '_tab_functions.php',
    'client_mode' => '_tab_client_mode.php',
    'support'     => '_tab_support.php',
);
?>
<?php if (! empty($wpae_gate_state_shown)): ?>
    <?php if (! empty($wpae_gate_redirected)): ?>
        <div class="notice notice-warning wpae-license-gate-notice"><p><?php echo wp_kses_post($wpae_gate_messages->redirected()); ?></p></div>
    <?php endif; ?>
    <div class="notice <?php echo $wpae_gate_blocked ? 'notice-error wpae-license-gate-blocked' : 'notice-warning'; ?> wpae-license-gate-settings"><p><?php echo wp_kses_post($wpae_gate_message); ?></p></div>
<?php endif; ?>
<?php
// WordPress moves every .notice inside .wrap to just after the first h1/h2, or to .wp-header-end when one is present.
?>
<hr class="wp-header-end" />

<div class="wpae-set">

    <div class="wpallexport-header wpae-set__header">
        <div class="wpallexport-logo"></div>
        <div class="wpallexport-title">
            <h3><?php esc_html_e('Settings', 'wp_all_export_plugin'); ?></h3>
        </div>
        <div class="wpallexport-links wpae-set__links">
            <a href="https://www.wpallimport.com/support/" target="_blank"><?php esc_html_e('Support', 'wp_all_export_plugin'); ?></a> |
            <a href="https://www.wpallimport.com/documentation/" target="_blank"><?php esc_html_e('Documentation', 'wp_all_export_plugin'); ?></a>
        </div>
    </div>

    <nav class="nav-tab-wrapper wpae-set__tabs" aria-label="<?php esc_attr_e('Settings sections', 'wp_all_export_plugin'); ?>">
        <?php foreach ($tabs as $slug => $label): ?>
            <a href="<?php echo esc_url($tab_url[$slug]); ?>"
               class="nav-tab<?php echo $slug === $current_tab ? ' nav-tab-active' : ''; ?>"
               <?php if ($slug === $current_tab): ?>aria-current="page"<?php endif; ?>><?php echo esc_html($label); ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($this->errors->get_error_codes()): ?>
        <div class="wpae-set__errors" role="alert" tabindex="-1" data-wpae-errors><?php $this->error(); ?></div>
    <?php endif; ?>

    <?php if (!empty($license_message)): ?>
        <div class="notice notice-success inline wpae-set__notice"><p><?php echo esc_html($license_message); ?></p></div>
    <?php endif; ?>

    <?php
    if (isset($tab_files[$current_tab])) {
        require_once(PMXE_Plugin::ROOT_DIR . '/views/admin/settings/' . $tab_files[$current_tab]);
    } else {
        /**
         * Renders a tab contributed via `pmxe_settings_tabs`.
         *
         * Wrap output in `.wpallexport-content-section .wpae-set__panel`, and include
         * `<input type="hidden" name="settings_tab" value="{$slug}">` in any form.
         *
         * @param string $slug  Tab slug.
         * @param array  $post  Current options.
         */
        ob_start();
        do_action('pmxe_settings_tab_content', $current_tab, $post);
        do_action('pmxe_settings_tab_content_' . $current_tab, $post);
        $contributed = trim(ob_get_clean());

        if ('' !== $contributed) {
            echo $contributed;
        } else {
            echo '<div class="wpallexport-content-section wpae-set__panel"><div class="wpae-set-empty">'
                . '<p class="wpae-set-empty__title">' . esc_html__('Nothing to show here', 'wp_all_export_plugin') . '</p>'
                . '<p class="wpae-set-empty__body">' . esc_html__('This tab was added by an add-on that is no longer rendering it.', 'wp_all_export_plugin') . '</p>'
                . '</div></div>';
        }
    }
    ?>

</div>

<div class="wpallexport-overlay"></div>
<div class="wpallexport-loader"
     style="border-radius: 5px; z-index: 999999; display:none; position: fixed;top: 200px;    left: 50%; width: 100px;height: 100px;background-color: #fff; text-align: center;">
    <img style="margin-top: 45%;" src="<?php echo PMXE_ROOT_URL; ?>/static/img/preloader.gif"/>
</div>
<div class="wpallexport-super-overlay"></div>

<a href="http://soflyy.com/" target="_blank"
   class="wpallexport-created-by"><?php esc_html_e('Created by', 'wp_all_export_plugin'); ?> <span></span></a>
