<?php

if (!class_exists('PMXE_Updater')) {

    class PMXE_Updater extends \Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Updater
    {
        protected function textDomain()
        {
            return 'wp_all_export_plugin';
        }

        protected function productName()
        {
            return isset($this->apiData['item_name']) && '' !== $this->apiData['item_name']
                ? (string) $this->apiData['item_name']
                : 'WP All Export Pro';
        }

        private function isMainPlugin()
        {
            return 'wp-all-export-pro' === $this->slug;
        }

        protected function settingsUrl()
        {
            return admin_url('admin.php?page=pmxe-admin-settings');
        }

        protected function pluginRowPriority()
        {
            return 11;
        }

        // On a multisite subsite core's wp_plugin_update_row() prints nothing at all,
        // so without the is_multisite() arm no update row appears there.
        protected function rendersUpdateRow()
        {
            return $this->isMainPlugin() || is_multisite();
        }

        protected function licenseRequiredMessage()
        {
            $message = '<span class="wp-all-export-pro-new-version-notice">' . sprintf(
                __('A new version of WP All Export Pro is available. <strong>A valid license is required to use WP All Export Pro and to receive updates.</strong> Enter or check your license key on the <a href="%1$s">Settings</a> page.', 'wp_all_export_plugin'),
                esc_url($this->settingsUrl())
            ) . '</span>';

            $message .= '<span class="wp-all-export-pro-licence-error-notice">' . sprintf(
                __('If you don\'t have a license key, please see the <a href="%1$s" target="_blank">details & pricing</a> page. If you do have a license key, you can access it at the <a href="%2$s" target="_blank">customer portal</a>.', 'wp_all_export_plugin'),
                esc_url($this->pricingUrl()),
                esc_url($this->portalUrl())
            ) . '</span>';

            return $message;
        }

        protected function updateAvailableMessage($versionInfo)
        {
            if (! $this->isMainPlugin()) {
                return parent::updateAvailableMessage($versionInfo);
            }

            return '<span class="wp-all-export-pro-new-version-notice">' . sprintf(
                __('There is a new version of WP All Export Pro available. <a target="_blank" class="thickbox" href="%1$s">View version %2$s details</a> or <a href="%3$s" class="update-link">update now</a>.', 'wp_all_export_plugin'),
                esc_url($this->changelogUrl()),
                esc_html($versionInfo->new_version),
                esc_url(wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=') . $this->name, 'upgrade-plugin_' . $this->name))
            ) . '</span>';
        }

        protected function phpRefusalMessage($version, $requires)
        {
            return $this->isMainPlugin()
                ? '<span class="wp-all-export-pro-new-version-notice">' . parent::phpRefusalMessage($version, $requires) . '</span>'
                : parent::phpRefusalMessage($version, $requires);
        }

        protected function wordPressRefusalMessage($version, $requires)
        {
            return $this->isMainPlugin()
                ? '<span class="wp-all-export-pro-new-version-notice">' . parent::wordPressRefusalMessage($version, $requires) . '</span>'
                : parent::wordPressRefusalMessage($version, $requires);
        }

        protected function bothRefusalMessage($version, $requiresPhp, $requiresWp)
        {
            return $this->isMainPlugin()
                ? '<span class="wp-all-export-pro-new-version-notice">' . parent::bothRefusalMessage($version, $requiresPhp, $requiresWp) . '</span>'
                : parent::bothRefusalMessage($version, $requiresPhp, $requiresWp);
        }

        protected function phpRequirementMessage($versionInfo)
        {
            if (! $this->isMainPlugin()) {
                return parent::phpRequirementMessage($versionInfo);
            }

            return '<span class="wp-all-export-pro-new-version-notice">' . sprintf(
                __('WP All Export Pro %1$s requires PHP %2$s or greater, you are using PHP %3$s. Please contact your host and tell them to update your server to at least PHP %2$s.', 'wp_all_export_plugin'),
                esc_html($versionInfo->new_version),
                esc_html($versionInfo->php_version),
                esc_html($this->phpVersion)
            ) . '</span>';
        }

        // The trailing script removes WordPress's own update row; without it the
        // plugins screen shows two rows for the same plugin.
        protected function renderUpdateRow($plugin, $versionInfo, $message)
        {
            if (! is_string($message) || '' === $message) {
                return;
            }

            $plugin_slug = sanitize_title(isset($plugin['Name']) ? $plugin['Name'] : $this->productName());
            $active = is_plugin_active($this->name) ? 'active' : '';
            $shiny_updates = version_compare(get_bloginfo('version'), '1.4.7-beta1-37926', '>=');
            $update_msg_classes = $shiny_updates ? 'notice inline notice-warning notice-alt' : 'pre-shiny-updates';

            if (! $this->hasPackage($versionInfo) && $shiny_updates) {
                $update_msg_classes .= ' post-shiny-updates';
            }
            ?>
            <tr class="plugin-update-tr <?php echo $active; ?> wp-all-export-pro-custom">
                <td colspan="4" class="plugin-update colspanchange">
                    <div class="update-message <?php echo $update_msg_classes; ?>">
                        <p>
                            <?php echo $message; ?>
                            <?php do_action('in_plugin_update_message-' . $this->name, [], $versionInfo); ?>
                        </p>
                    </div>
                </td>
            </tr>
            <script type="text/javascript">
                (function ($) {
                    var wp_all_export_row = jQuery('[data-slug=<?php echo $plugin_slug; ?>]:first');

                    // Fallback for earlier versions of WordPress.
                    if (!wp_all_export_row.length) {
                        wp_all_export_row = jQuery('#<?php echo $plugin_slug; ?>');
                    }

                    wp_all_export_row.addClass('update');

                    var next_row = wp_all_export_row.next();

                    // If there's a plugin update row - need to keep the original update row available so we can switch it out
                    // if the user has a successful response from the 'check my license again' link
                    if (next_row.hasClass('plugin-update-tr') && !next_row.hasClass('wp-all-export-pro-custom')) {
                        var original = next_row.clone();
                        // original.add;
                        next_row.html(next_row.next().html()).addClass('wp-all-export-pro-custom-visible');
                        next_row.next().remove();
                        next_row.after(original);
                        original.addClass('wp-all-export-original-update-row');
                    }
                })(jQuery);
            </script>
            <?php
        }
    }

}
