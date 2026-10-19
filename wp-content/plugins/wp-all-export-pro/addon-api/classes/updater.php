<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_Updater extends \Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Updater {

    protected function textDomain() {
        return 'wp_all_export_plugin';
    }

    protected function productName() {
        return isset( $this->apiData['item_name'] ) ? (string) $this->apiData['item_name'] : '';
    }

    protected function settingsUrl() {
        return admin_url( 'admin.php?page=pmxe-admin-settings' );
    }

    protected function updateTransientPriority() {
        return 21;
    }

    protected function changelogUrl() {
        return self_admin_url(
            'index.php?edd_sl_action=view_plugin_changelog&plugin=' . $this->name
            . '&slug=' . $this->slug . '&TB_iframe=true&width=772&height=911'
        );
    }

    protected function updateWithoutPackageMessage( $versionInfo ) {
        return sprintf(
            __( 'There is a new version of %1$s available. <a target="_blank" class="thickbox" href="%2$s">View version %3$s details</a>.', 'wp_all_export_plugin' ),
            esc_html( $versionInfo->name ),
            esc_url( $this->changelogUrl() ),
            esc_html( $versionInfo->new_version )
        );
    }

    protected function updateAvailableMessage( $versionInfo ) {
        return sprintf(
            __( 'There is a new version of %1$s available. <a target="_blank" class="thickbox" href="%2$s">View version %3$s details</a> or <a href="%4$s">update now</a>.', 'wp_all_export_plugin' ),
            esc_html( $versionInfo->name ),
            esc_url( $this->changelogUrl() ),
            esc_html( $versionInfo->new_version ),
            esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $this->name, 'upgrade-plugin_' . $this->name ) )
        );
    }

    protected function renderUpdateRow( $plugin, $versionInfo, $message ) {
        if ( ! is_string( $message ) || '' === $message ) {
            return;
        }

        $wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );

        echo '<tr class="plugin-update-tr"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange"><div class="update-message">';
        echo $message;
        echo '</div></td></tr>';
    }
}
