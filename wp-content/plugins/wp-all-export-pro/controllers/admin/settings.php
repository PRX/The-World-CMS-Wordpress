<?php

/**
 * Admin Statistics page
 *
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class PMXE_Admin_Settings extends PMXE_Controller_Admin
{
    public $slug = 'wp-all-export-pro';

    /** @var  \Wpae\App\Service\License\LicenseActivator */
    private $licenseActivator;

    protected function init()
    {
        if(!current_user_can(PMXE_Plugin::$capabilities)) {
            die('Security check');
        }

        $this->licenseActivator = new \Wpae\App\Service\License\LicenseActivator();

        $this->enqueue_settings_assets();
    }

    /** @var array<string,string>|null */
    private $tabs;

    public function get_tabs()
    {
        if ($this->tabs !== null) {
            return $this->tabs;
        }

        $tabs = array(
            'licenses'    => __('Licenses', 'wp_all_export_plugin'),
            'cron'        => __('Cron', 'wp_all_export_plugin'),
            'files'       => __('Files', 'wp_all_export_plugin'),
            'zapier'      => __('Zapier', 'wp_all_export_plugin'),
            'templates'   => __('Templates', 'wp_all_export_plugin'),
            'functions'   => __('Functions', 'wp_all_export_plugin'),
            'client_mode' => __('Client Mode', 'wp_all_export_plugin'),
            'support'     => __('Support', 'wp_all_export_plugin'),
        );

        /**
         * Register a settings tab. Slug => label.
         *
         * A tab added here renders via `pmxe_settings_tab_content`; it does not need a
         * view file. Core tabs can be relabelled but are rendered by core.
         */
        $filtered = apply_filters('pmxe_settings_tabs', $tabs);

        if (! is_array($filtered)) {
            return $this->tabs = $tabs;
        }

        // Lookups go through sanitize_key(), so a slug that does not survive it renders a
        // tab that can never be selected.
        foreach ($filtered as $slug => $label) {
            if ('' === (string) $slug || (string) $slug !== sanitize_key((string) $slug) || ! is_scalar($label) || '' === trim((string) $label)) {
                unset($filtered[$slug]);
                _doing_it_wrong(
                    'pmxe_settings_tabs',
                    esc_html(sprintf('Tab slug "%s" must be sanitize_key()-safe with a string label; it was dropped.', is_scalar($slug) ? $slug : gettype($slug))),
                    '1.9.16'
                );
            }
        }

        // The licence form is the one thing a blocked site can reach; no filter takes it away.
        if (! isset($filtered['licenses'])) {
            $filtered = array('licenses' => $tabs['licenses']) + $filtered;
        }

        return $this->tabs = $filtered;
    }

    protected function get_submitted_tab()
    {
        $tab  = isset($_POST['settings_tab']) ? sanitize_key($_POST['settings_tab']) : '';
        $tabs = $this->get_tabs();

        return isset($tabs[$tab]) ? $tab : $this->get_current_tab();
    }

    protected function get_visible_tabs()
    {
        if (! empty($this->data['wpae_gate_blocked'])) {
            return array_intersect_key($this->get_tabs(), array('licenses' => true));
        }

        return $this->get_tabs();
    }

    protected function get_current_tab()
    {
        $tabs = $this->get_tabs();

        // Blocked: Licenses is the only tab whose actions still run, so a deep link to any
        // other lands here rather than on a dead panel.
        if (! empty($this->data['wpae_gate_blocked'])) {
            return 'licenses';
        }

        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : '';

        return isset($tabs[$tab]) ? $tab : key($tabs);
    }

    protected function normalise_saved_options($post, $pin = array(), $owns = null)
    {
        $stored = PMXE_Plugin::getInstance()->getOption();

        // $post carries every option key merged over the request, so a branch that renders
        // only its own fields is otherwise a general settings-write primitive. When a branch
        // declares what it owns, everything else comes from stored.
        if (is_array($owns)) {
            $post = array_intersect_key($post, array_flip($owns)) + $stored;
        }

        // Read past the object cache: a concurrent activation writes these after this
        // request's copy was loaded, so pinning the in-memory value would revert it.
        if ($pin) {
            $option_name = get_class(PMXE_Plugin::getInstance()) . '_Options';
            wp_cache_delete($option_name, 'options');
            $fresh = get_option($option_name);
            $fresh = is_array($fresh) ? $fresh : $stored;
        }

        foreach ($pin as $licence_key) {
            if (array_key_exists($licence_key, $fresh)) {
                $post[$licence_key] = $fresh[$licence_key];
            } elseif (array_key_exists($licence_key, $stored)) {
                $post[$licence_key] = $stored[$licence_key];
            }
        }

        foreach ($post as $key => $value) {
            if (is_scalar($value) || ! isset($stored[$key]) || is_array($stored[$key])) {
                continue;
            }

            $post[$key] = $stored[$key];
        }

        // $_POST is all strings, so an untouched int option comes back as "5" against a
        // stored 5, and saveOptions() compares with !==. The write is whole-row, so one
        // spurious change re-asserts every other option from this request's boot copy.
        foreach ($post as $key => $value) {
            if (is_int($stored[$key] ?? null) && is_string($value) && is_numeric($value)
                && (string) (int) $value === $value) {
                $post[$key] = (int) $value;
            }
        }

        return $post;
    }

    protected function utf8_safe($value)
    {
        $value = (string) $value;

        if ('' === $value || (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8'))) {
            return $value;
        }

        if (function_exists('iconv')) {
            $stripped = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if (false !== $stripped) {
                return $stripped;
            }
        }

        return preg_replace('/[\x80-\xFF]/', '', $value);
    }

    /** What is set, never what it is set to: this is pasted into support tickets. */
    protected function get_system_report()
    {
        global $wpdb, $wp_version;

        $options = PMXE_Plugin::getInstance()->getOption();
        $uploads = wp_upload_dir();
        $functions = $uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_EXPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php';
        $roles = isset($options['client_mode_roles']) && is_array($options['client_mode_roles']) ? $options['client_mode_roles'] : array();
        $theme = wp_get_theme();

        $active = array();
        foreach ((array) get_option('active_plugins', array()) as $plugin) {
            if (! is_string($plugin) || 0 !== validate_file($plugin)) {
                continue;
            }
            $data    = @get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin, false, false);
            $name    = $this->utf8_safe((string) (empty($data['Name']) ? $plugin : $data['Name']));
            $version = $this->utf8_safe((string) (empty($data['Version']) ? '' : $data['Version']));
            $active[] = '' === $version ? $name : $name . ' ' . $version;
        }
        sort($active);

        $lines = array(
            '== WP All Export ==',
            'WP All Export:      ' . PMXE_VERSION . (defined('PMXE_EDITION') ? ' (' . PMXE_EDITION . ')' : ''),
            'Secure mode:        ' . (empty($options['secure']) ? 'off' : 'on'),
            'Functions file:     ' . (file_exists($functions) ? 'present' : 'absent'),
            'Cron secret key:    ' . (empty($options['cron_job_key']) ? 'not set' : 'set'),
            'Zapier API key:     ' . (empty($options['zapier_api_key']) ? 'not set' : 'set'),
            'Client mode roles:  ' . ($roles ? implode(', ', array_map('strval', $roles)) : 'none'),
            '',
            '== Environment ==',
            'WordPress:          ' . $wp_version . (is_multisite() ? ' (multisite)' : ''),
            'Theme:              ' . $this->utf8_safe((string) $theme->get('Name')) . ' ' . $this->utf8_safe((string) $theme->get('Version')),
            'PHP:                ' . PHP_VERSION,
            'Memory limit:       ' . ini_get('memory_limit'),
            'Max execution:      ' . ((string) (get_cfg_var('max_execution_time') ?: ini_get('max_execution_time')) ?: 'unlimited'),
            'Max upload size:    ' . size_format(wp_max_upload_size()),
            // The two raw limits behind it: the computed figure is the smaller of the
            // pair, so on its own it never says which one a failed upload hit.
            'Upload max filesize: ' . ini_get('upload_max_filesize'),
            'Post max size:      ' . ini_get('post_max_size'),
            // Truncates a large export's field list without reporting anything.
            'Max input vars:     ' . ini_get('max_input_vars'),
            'libxml:             ' . (defined('LIBXML_DOTTED_VERSION') ? LIBXML_DOTTED_VERSION : 'unknown'),
            'Server:             ' . (empty($_SERVER['SERVER_SOFTWARE']) ? 'unknown' : $this->utf8_safe((string) $_SERVER['SERVER_SOFTWARE'])),
            'MySQL:              ' . $this->utf8_safe((string) $wpdb->db_version()),
            'WP_DEBUG:           ' . (defined('WP_DEBUG') && WP_DEBUG ? 'on' : 'off'),
            '',
            '== Active plugins (' . count($active) . ') ==',
        );

        // esc_textarea() is htmlspecialchars() without ENT_SUBSTITUTE, so one invalid byte
        // anywhere in here returns '' for the entire report.
        return $this->utf8_safe(implode("\n", array_merge($lines, $active)));
    }

    protected function enqueue_settings_assets()
    {
        wp_enqueue_style('pmxe-settings-style', PMXE_ROOT_URL . '/static/css/settings.css', array('pmxe-admin-style'), PMXE_VERSION);
        wp_enqueue_script('pmxe-settings-script', PMXE_ROOT_URL . '/static/js/settings.js', array('jquery'), PMXE_VERSION, true);

        wp_localize_script('pmxe-settings-script', 'wpaeSettingsL10n', array(
            'nonce'                 => wp_create_nonce('wp_all_export_secure'),
            'saving'                => __('Saving…', 'wp_all_export_plugin'),
            'leaveWarning'          => __('You have unsaved changes that will be lost.', 'wp_all_export_plugin'),
            'copied'                => __('Copied', 'wp_all_export_plugin'),
            'copyFailed'            => __('Press Ctrl+C', 'wp_all_export_plugin'),
            'confirm'               => __('Confirm', 'wp_all_export_plugin'),
            'confirmTitle'          => __('Are you sure?', 'wp_all_export_plugin'),
            'cancel'                => __('Cancel', 'wp_all_export_plugin'),
            'statusUnavailable'     => __('Status unavailable', 'wp_all_export_plugin'),
            'statusUnavailableHelp' => __("Couldn't reach the licensing server. Your license is unaffected. Retry when you have a connection.", 'wp_all_export_plugin'),
            // Kept word for word with wpae_settings_unreachable_state().
            'notActiveHere'         => __('Not active on this site', 'wp_all_export_plugin'),
            'statusUncheckedHelp'   => __("Couldn't reach the licensing server, so this key couldn't be re-checked. This site stays unlicensed until it can be.", 'wp_all_export_plugin'),
            'regenerateWarning'     => __('Generate a new secret key? Every cron job you have already set up will stop working until you update it with the new URLs.', 'wp_all_export_plugin'),
            'regenerateLabel'       => __('Generate new key', 'wp_all_export_plugin'),
            'zapierRegenerateWarning' => __('Generate a new API key? Every Zap that uses the current key will stop working until you update it.', 'wp_all_export_plugin'),
        ));
    }

    /** Called again after the licence branch, which rewrites what the gate reads. */
    protected function readGate($gate = null)
    {
        $gate = $gate ?: \Wpae\App\Gate\Gate::instance();

        $blocked = $gate->blocks();

        $this->data['wpae_gate_blocked'] = $blocked;
        $this->data['wpae_gate_redirected'] = $blocked
            && isset($_GET['wpae_gate'])
            && $_GET['wpae_gate'] === 'blocked';
        $this->data['wpae_gate_messages'] = $gate->messages();

        // Not $blocked: the grace warning shows while the page still works.
        $this->data['wpae_gate_state'] = $gate->state();
        $this->data['wpae_gate_state_shown'] = $gate->state() !== \Wpae\App\Gate\Gate::STATE_VALID;
        $this->data['wpae_gate_message'] = $gate->messages()->settingsNotice(
            $gate->state(),
            $gate->stateReason(),
            $gate->graceUntil(),
            $blocked
        );
    }

    public function index_action()
    {
        global $wp_roles, $wpdb;

        $this->data['post'] = $post = $this->postWithStoredOptions();

        $this->data['roles'] = $wp_roles->roles;

        // Read before the branches below, which refuse every form but the licence one.
        $this->readGate();

        if ($this->input->post('is_settings_submitted') && ! $this->data['wpae_gate_blocked']) { // save settings form
            $owns = array(
                'cron'   => array('cron_job_key'),
                'files'  => array('secure'),
            );
            $tab = $this->get_submitted_tab();

            $this->save_settings($this->normalise_saved_options(
                $post,
                array('license', 'license_status', 'scheduling_license', 'scheduling_license_status'),
                isset($owns[$tab]) ? $owns[$tab] : array()
            ));
        }

        if ($this->input->post('is_license_submitted')) {

            $post = $this->save_license($post);

            $this->readGate();
        }

        if ($this->input->post('is_scheduling_license_submitted') && ! $this->data['wpae_gate_blocked']) {

            $post = $this->save_scheduling_license($post);
        }

        if ($this->input->post('is_client_mode_submitted') && ! $this->data['wpae_gate_blocked']) {
            $post = $this->save_client_mode_settings($post);
        }

        // Nothing on this path may reach the network; the recheck is queued instead.
        \Wpae\App\License\Licensing::instance()->queueForcedRecheck();

        $post['scheduling_license_status'] = PMXE_Plugin::getInstance()->getOption('scheduling_license_status');

        $this->data['is_license_active'] = false;
        if (!empty($post['license_status']) && $post['license_status'] == 'valid') {
            $this->data['is_license_active'] = true;
        }


        $this->data['is_scheduling_license_active'] = false;
        if (!empty($post['scheduling_license_status']) && $post['scheduling_license_status'] == 'valid') {
            $this->data['is_scheduling_license_active'] = true;
        }

        $this->data['exportList'] = [];

        $table_prefix = PMXE_Plugin::getInstance()->getTablePrefix();
        $exports = $wpdb->get_results("SELECT * FROM {$table_prefix}exports ORDER BY `registered_on` DESC");

        foreach($exports as $exportPost) {
            $this->data['exportList'][] = [
                'id' => $exportPost->id,
                'name' => $exportPost->friendly_name
            ];
        }

        $this->data['triggerTestData'] = get_option('wpae_trigger_test_data', 1);
        $this->data['zapierSpecificExport'] = get_option('wpae_zapier_specific_export', 0);

        if ($this->input->post('is_templates_submitted') && ! $this->data['wpae_gate_blocked']) { // delete templates form

            $this->save_templates();
        }

        $this->data['tabs']        = $this->get_visible_tabs();
        $this->data['current_tab'] = $this->get_current_tab();
        $this->data['tab_url']     = array();
        foreach ($this->data['tabs'] as $slug => $label) {
            $this->data['tab_url'][$slug] = add_query_arg('tab', $slug, admin_url('admin.php?page=pmxe-admin-settings'));
        }
        $this->data['system_report'] = $this->get_system_report();

        $this->render();

    }

    public function dismiss_action()
    {

        PMXE_Plugin::getInstance()->updateOption("dismiss", 1);

        exit('OK');
    }

    /*
    *
    * Check plugin's license
    *
    */
    /**
     * @param bool $manual A Save on the licence form: a definitive refusal blocks
     *                     the site now instead of opening a grace window.
     * @return string|false
     */
    private function check_license($manual = true)
    {
        wp_all_export_pro_forget_version_record();

        $result = \Wpae\App\License\Licensing::instance()->recheck($manual);

        $status = isset($result['options']['license_status']) ? $result['options']['license_status'] : '';

        return (is_string($status) and $status !== '') ? $status : false;
    }

    private function check_scheduling_license()
    {
        $options = PMXE_Plugin::getInstance()->getOption();

        wp_all_export_pro_forget_version_record();

        return $this->licenseActivator->checkLicense(PMXE_Plugin::getSchedulingName(), $options, \Wpae\App\Service\License\LicenseActivator::CONTEXT_SCHEDULING);
    }

    /**
     * @param $post
     * @return mixed
     */
    private function save_scheduling_license($post)
    {
        check_admin_referer('edit-license', '_wpnonce_edit-scheduling-license');

        if (! empty($_POST['pmxe_scheduling_license_remove'])) {
            $this->remove_scheduling_license();
        }

        $post = $this->normalise_saved_options(
            $post,
            array('license', 'license_status', 'scheduling_license_status'),
            array('scheduling_license')
        );

        $stored_key = PMXE_Plugin::getInstance()->getOption('scheduling_license');

        if (! isset($post['scheduling_license']) || ! is_scalar($post['scheduling_license']) || '' === trim((string) $post['scheduling_license'])) {
            $post['scheduling_license'] = $stored_key;
        }

        // The stored status is the server's answer about the stored key, so a
        // different key starts without one rather than reading as active.
        if ($post['scheduling_license'] !== $stored_key) {
            $post['scheduling_license_status'] = '';
        }

        if (!$this->errors->get_error_codes()) { // no validation errors detected

            $this->saveOptions($post);

            // The form's only submit is Activate, so the check runs for it as well
            // as for a plain save; only a deactivate skips it.
            if (empty($_POST['pmxe_scheduling_license_deactivate']) && ! empty($post['scheduling_license'])) {
                $status = $this->check_scheduling_license();

                // Anything but a literal is "no answer": false for a timeout, null
                // for a body without `license`. Storing it would clear a licensed
                // customer's status because we were unreachable.
                if (is_string($status) and $status !== '') {
                    $post['scheduling_license_status'] = $status;
                }

                if ($post['scheduling_license_status'] == 'valid') {
                    $this->data['scheduling_license_message'] = esc_html__('License activated.', 'wp_all_export_plugin');
                }

                $this->saveOptions($post);

                // Only an answer is worth handing over: an outage here must not clear
                // a status the sibling plugin holds.
                if (class_exists('PMXI_Plugin') && method_exists('PMXI_Plugin', 'getSchedulingName') && !empty($post['scheduling_license_status'])) {
                    PMXI_Plugin::getInstance()->updateOption(array(
                        'scheduling_license_status' => $post['scheduling_license_status'],
                        'scheduling_license'        => $post['scheduling_license'],
                    ));
                }

                if (! empty($_POST['pmxe_scheduling_license_activate'])) {
                    $this->activate_scheduling_licenses();
                }
            }
        }

        $this->data['post'] = $post = PMXE_Plugin::getInstance()->getOption();
        return $post;
    }

    protected function remove_license()
    {
        // Together: a cleared key beside a stale `valid` still reads as licensed.
        PMXE_Plugin::getInstance()->updateOption(array('license' => '', 'license_status' => ''));

        // Removing the key leaves a record that still carries a package URL,
        // so the row keeps offering an update the site is no longer entitled
        // to until it expires.
        wp_all_export_pro_forget_version_record();

        wp_redirect(esc_url_raw(add_query_arg('pmxe_nt', urlencode(__('License removed', 'wp_all_export_plugin')), $this->baseUrl)));
        die();
    }

    protected function remove_scheduling_license()
    {
        PMXE_Plugin::getInstance()->updateOption(array('scheduling_license' => '', 'scheduling_license_status' => ''));

        wp_all_export_pro_forget_version_record();

        wp_redirect(esc_url_raw(add_query_arg('pmxe_nt', urlencode(__('License removed', 'wp_all_export_plugin')), $this->baseUrl)));
        die();
    }

    private function save_templates()
    {
        check_admin_referer('delete-templates', '_wpnonce_delete-templates');

        if ($this->input->post('import_templates')) {

            if (!empty($_FILES)) {
                $file_name = $_FILES['template_file']['name'];
                $file_size = $_FILES['template_file']['size'];
                $tmp_name = $_FILES['template_file']['tmp_name'];

                if (isset($file_name)) {

                    $filename = stripslashes($file_name);
                    $extension = strtolower(pmxe_getExtension($filename));

                    if (($extension != "txt")) {
                        $this->errors->add('form-validation', esc_html__('Unknown File extension. Only txt files are permitted', 'wp_all_export_plugin'));
                    } else {
                        $import_data = @file_get_contents($tmp_name);
                        if (!empty($import_data)) {
                            $templates_data = json_decode($import_data, true);

                            if (!empty($templates_data)) {
                                $templateOptions = empty($templates_data[0]['options']) ? false : unserialize($templates_data[0]['options']);
                                if (empty($templateOptions)) {
                                    $this->errors->add('form-validation', esc_html__('The template is invalid. Options are missing.', 'wp_all_export_plugin'));
                                } else {
                                    if (!isset($templateOptions['is_user_export'])) {
                                        $this->errors->add('form-validation', esc_html__('The template you\'ve uploaded is intended to be used with WP All Import plugin.', 'wp_all_export_plugin'));
                                    } else {
                                        $template = new PMXE_Template_Record();
                                        foreach ($templates_data as $template_data) {
                                            unset($template_data['id']);
                                            $template->clear()->set($template_data)->insert();
                                        }
                                        wp_redirect($this->tab_url_with_notice('templates', sprintf(_n('%d template imported', '%d templates imported', count($templates_data), 'wp_all_export_plugin'), count($templates_data))));
                                        die();
                                    }
                                }
                            } else $this->errors->add('form-validation', esc_html__('Wrong imported data format', 'wp_all_export_plugin'));
                        } else $this->errors->add('form-validation', esc_html__('File is empty or doesn\'t exests', 'wp_all_export_plugin'));
                    }
                } else $this->errors->add('form-validation', esc_html__('Undefined entry!', 'wp_all_export_plugin'));
            } else $this->errors->add('form-validation', esc_html__('Please select file.', 'wp_all_export_plugin'));

        } else {
            $templates_ids = $this->input->post('templates', array());
            if (empty($templates_ids)) {
                $this->errors->add('form-validation', esc_html__('Templates must be selected', 'wp_all_export_plugin'));
            }

            if (!$this->errors->get_error_codes()) { // no validation errors detected
                if ($this->input->post('delete_templates')) {
                    $template = new PMXE_Template_Record();
                    foreach ($templates_ids as $template_id) {
                        $template->clear()->set('id', $template_id)->delete();
                    }
                    wp_redirect($this->tab_url_with_notice('templates', sprintf(_n('%d template deleted', '%d templates deleted', count($templates_ids), 'wp_all_export_plugin'), count($templates_ids))));
                    die();
                }
                if ($this->input->post('export_templates')) {
                    $export_data = array();
                    $template = new PMXE_Template_Record();
                    foreach ($templates_ids as $template_id) {
                        $export_data[] = $template->clear()->getBy('id', $template_id)->toArray(TRUE);
                    }

                    $uploads = wp_upload_dir();
                    $targetDir = $uploads['basedir'] . DIRECTORY_SEPARATOR . PMXE_Plugin::TEMP_DIRECTORY;
                    $export_file_name = "templates_" . uniqid() . ".txt";
                    file_put_contents($targetDir . DIRECTORY_SEPARATOR . $export_file_name, json_encode($export_data));

                    PMXE_download::csv($targetDir . DIRECTORY_SEPARATOR . $export_file_name);

                }
            }
        }
    }

    /**
     * @param $post
     * @return mixed
     */
    private function save_license($post)
    {
        check_admin_referer('edit-license', '_wpnonce_edit-license');

        if (! empty($_POST['pmxe_license_remove'])) {
            $this->remove_license();
        }

        // The key is the only thing this form may write: the status beside it is the
        // server's answer, and a blocked site can reach this form.
        $post = $this->normalise_saved_options(
            $post,
            array('license_status', 'scheduling_license', 'scheduling_license_status'),
            array('license')
        );

        // The field renders empty on purpose, so an empty submit means "leave it as
        // it is". Clearing is done with Remove.
        if (! isset($post['license']) || ! is_scalar($post['license']) || '' === trim((string) $post['license'])) {
            $post['license'] = PMXE_Plugin::getInstance()->getOption('license');
        }

        if (!$this->errors->get_error_codes()) { // no validation errors detected

            $this->saveOptions($post);

            if (empty($_POST['pmxe_license_activate']) and empty($_POST['pmxe_license_deactivate'])) {
                $status = $this->check_license(true);

                // A non-answer must not be stored: it would clear a licensed badge.
                if (is_string($status) and $status !== '') {
                    $post['license_status'] = $status;
                }

                if ($status === 'valid') {
                    $this->data['license_message'] = esc_html__('License activated.', 'wp_all_export_plugin');
                }
                $this->saveOptions($post);

            }

            if (isset($_POST['pmxe_license_activate'])) {
                $this->activate_licenses();

                $this->check_license(true);
            }
        }

        $this->data['post'] = $post = PMXE_Plugin::getInstance()->getOption();
        return $post;
    }

    /**
     * @param $post
     */
    private function save_settings($post)
    {
        check_admin_referer('edit-settings', '_wpnonce_edit-settings');

        if ($this->input->post('regenerate_cron_job_key')) {
            $post['cron_job_key'] = wp_all_export_url_title(wp_all_export_rand_char(12));
        }

        if (isset($_POST['cron_job_key'])) {
            $submitted = is_scalar($post['cron_job_key']) ? (string) $post['cron_job_key'] : '';
            $sanitised = wp_all_export_url_title($submitted);

            if ('' === $sanitised) {
                $this->errors->add('form-validation', __('Secret key must contain at least one letter, number, dot, dash, or underscore.', 'wp_all_export_plugin'));
            } elseif ($sanitised !== $submitted) {
                $this->errors->add('form-validation', sprintf(__('Secret key can only contain letters, numbers, dots, dashes, and underscores. Try %s.', 'wp_all_export_plugin'), $sanitised));
            }
        }

        if (!$this->errors->get_error_codes()) { // no validation errors detected

            $current_cron_job_key = PMXE_Plugin::getInstance()->getOption('cron_job_key');
            $new_cron_job_key = $post['cron_job_key'];

            if($new_cron_job_key !== $current_cron_job_key) {

                // Cron job key changed
                $scheduling_service = \Wpae\Scheduling\Scheduling::create();
                $exports = new PMXE_Export_List();
                $exports = $exports->getBy('parent_id', 0)->convertRecords();

                foreach ($exports as $export) {
                    if($export->options['scheduling_enable'] === "1") {
                        $scheduling_service->updateApiKey($export->id, $new_cron_job_key);
                    }
                }
            }

            $this->saveOptions($post);

            wp_redirect($this->tab_url_with_notice($this->get_submitted_tab(), __('Settings saved', 'wp_all_export_plugin')));
            die();
        }
    }

    /**
     * WP_Role::has_cap() reads the role's own list, where `setup_network` never
     * appears: it is a meta capability that map_meta_cap() resolves to
     * manage_options (or manage_network_options) at check time. Ask for the
     * capability it resolves to, or Administrator reads as a role that needs
     * client-mode access.
     *
     * @param  WP_Role|null  $role
     * @return bool
     */
    public static function role_can_manage_exports($role)
    {
        if (! $role) {
            return false;
        }

        $cap = PMXE_Plugin::$capabilities;

        if ('setup_network' === $cap) {
            $cap = is_multisite() ? 'manage_network_options' : 'manage_options';
        }

        return (bool) $role->has_cap($cap);
    }

    /** @return string  Back to the tab that posted, not the page's first tab. */
    protected function tab_url_with_notice($tab, $notice)
    {
        return esc_url_raw(add_query_arg(array('tab' => $tab, 'pmxe_nt' => urlencode($notice)), $this->baseUrl));
    }

    private function save_client_mode_settings($post)
    {
        check_admin_referer('edit-client-mode-settings', '_wpnonce_edit-client_mode_settings');

        if (!$this->errors->get_error_codes()) { // no validation errors detected

            global $wp_roles;

            $all_roles = array_keys($wp_roles->roles);

            $client_mode_roles = is_array($this->input->post('client_mode_roles')) ? $this->input->post('client_mode_roles') : [];

            foreach ($all_roles as $role_id) {

                $role = get_role($role_id);

                if (self::role_can_manage_exports($role)) {
                    continue;
                }

                if (in_array($role_id, $client_mode_roles)) {
                    $role->add_cap(PMXE_Plugin::CLIENT_MODE_CAP);
                } else {
                    $role->remove_cap(PMXE_Plugin::CLIENT_MODE_CAP);
                }

            }

            PMXE_Plugin::getInstance()->updateOption(array('client_mode_roles' => $client_mode_roles));

            wp_redirect($this->tab_url_with_notice('client_mode', __('Settings saved', 'wp_all_export_plugin')));

            die();
        }
    }

    /*
     * Activate licenses for main plugin and all premium addons
     */
    private function activate_licenses()
    {

        // listen for our activate button to be clicked
        if (isset($_POST['pmxe_license_activate'])) {

            // retrieve the license from the database
            $options = PMXE_Plugin::getInstance()->getOption();

            wp_all_export_pro_forget_version_record();

            $this->licenseActivator->activateLicense(PMXE_Plugin::getEddName(), \Wpae\App\Service\License\LicenseActivator::CONTEXT_PMXE);
        }
    }

    /*
    *
    * Activate licenses for main plugin and all premium addons
    *
    */
    private function activate_scheduling_licenses()
    {
        // listen for our activate button to be clicked

        wp_all_export_pro_forget_version_record();

        // retrieve the license from the database
        return $this->licenseActivator->activateLicense(PMXE_Plugin::getSchedulingName(), \Wpae\App\Service\License\LicenseActivator::CONTEXT_SCHEDULING);

        return false;
    }
}