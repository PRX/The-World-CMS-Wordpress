<?php

/**
 * Plugin Name: TaxoPress Pro
 * Plugin URI: https://wordpress.org/plugins/simple-tags/
 * Description: TaxoPress allows you to create and manage Tags, Categories, and all your WordPress taxonomy terms.
 * Version: 3.55.0
 * Author: TaxoPress
 * Author URI: https://taxopress.com
 * Text Domain: taxopress-pro
 * Domain Path: /languages
 * Min WP Version: 4.9.7
 * Requires PHP: 7.4
 * License: GPLv3
 *
 * Copyright (c) 2022 Taxopress
 *
 * @package     taxopress-pro
 * @author      TaxoPress
 * @copyright   Copyright (c) 2022 Taxopress
 * @license     GNU General Public License version 2
 * @link        https://TaxoPress.com/
 */

######################################
/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

Contributors to the TaxoPress code include:
    - Kevin Drouvin (kevin.drouvin@gmail.com - http://inside-dev.net)
    - Martin Modler (modler@webformatik.com - http://www.webformatik.com)
    - Vladimir Kolesnikov (vladimir@extrememember.com - http://blog.sjinks.pro)

Sections of the TaxoPress code are based on Custom Post Type UI by WebDevStudios.

Credits Icons :
    - famfamfam - http://www.famfamfam.com/lab/icons/silk/

*/

// don't load directly
if (!defined('ABSPATH')) {
    die('-1');
}

if (!defined('STAGS_VERSION')) {
    define('STAGS_VERSION', '3.54.0');
}

$includeFileRelativePath = '/publishpress/instance-protection/include.php';
if (file_exists(__DIR__ . '/lib/vendor' . $includeFileRelativePath)) {
    require_once __DIR__ . '/lib/vendor' . $includeFileRelativePath;
} elseif (defined('TAXOPRESS_PRO_LIB_VENDOR_PATH') && file_exists(TAXOPRESS_PRO_LIB_VENDOR_PATH . $includeFileRelativePath)) {
    require_once TAXOPRESS_PRO_LIB_VENDOR_PATH . $includeFileRelativePath;
}

if (class_exists('PublishPressInstanceProtection\\Config')) {
    $pluginCheckerConfig = new PublishPressInstanceProtection\Config();
    $pluginCheckerConfig->pluginSlug = 'taxopress-pro';
    $pluginCheckerConfig->pluginName = 'TaxoPress Pro';
    $pluginChecker = new PublishPressInstanceProtection\InstanceChecker($pluginCheckerConfig);
}

$bundledTranslationsPath = '/publishpress/bundled-translations/core/include.php';
if (file_exists(__DIR__ . '/lib/vendor' . $bundledTranslationsPath)) {
    require_once __DIR__ . '/lib/vendor' . $bundledTranslationsPath;
} elseif (defined('TAXOPRESS_PRO_LIB_VENDOR_PATH') && file_exists(TAXOPRESS_PRO_LIB_VENDOR_PATH . $bundledTranslationsPath)) {
    require_once TAXOPRESS_PRO_LIB_VENDOR_PATH . $bundledTranslationsPath;
}

add_action('plugins_loaded', function () {

    if (class_exists('PublishPress\BundledTranslations\BundledTranslations')) {
        $bundledTranslations = new PublishPress\BundledTranslations\BundledTranslations('taxopress-pro', __DIR__ . '/languages', __FILE__);
        $bundledTranslations->init();
    }
}, 10);
if (defined('TAXOPRESS_PRO_FILE')) {
    return;
}

if (! defined('TAXOPRESS_PRO_FILE')) {
    define('TAXOPRESS_PRO_FILE', __FILE__);
}
if (! defined('TAXOPRESS_FILE')) {
    define('TAXOPRESS_FILE', __FILE__);
}
if (! defined('STAGS_MIN_PHP_VERSION')) {
    define('STAGS_MIN_PHP_VERSION', '7.4');
}
if (! defined('STAGS_OPTIONS_NAME')) {
    define('STAGS_OPTIONS_NAME', 'simpletags');
// Option name for save settings
}
if (! defined('STAGS_OPTIONS_NAME_AUTO')) {
    define('STAGS_OPTIONS_NAME_AUTO', 'simpletags-auto');
// Option name for save settings auto terms
}

if (! defined('TAXOPRESS_PRO_ABSPATH')) {
    define('TAXOPRESS_PRO_ABSPATH', __DIR__);
}
if (! defined('TAXOPRESS_PRO_PLUGIN_FILE')) {
    define('TAXOPRESS_PRO_PLUGIN_FILE', TAXOPRESS_PRO_ABSPATH . '/taxopress-pro.php');
}
if (! defined('TAXOPRESS_PRO_URL')) {
    define('TAXOPRESS_PRO_URL', plugins_url('', __FILE__));
}

if (! defined('TAXOPRESS_ABSPATH')) {
    define('TAXOPRESS_ABSPATH', TAXOPRESS_PRO_ABSPATH . '/lib/vendor/taxopress/simple-tags');
}
if (! defined('STAGS_DIR')) {
    define('STAGS_DIR', rtrim(TAXOPRESS_ABSPATH, '/'));
}
if (! defined('STAGS_URL')) {
    define('STAGS_URL', plugins_url('lib/vendor/taxopress/simple-tags', __FILE__));
}

define('TAXOPRESS_VERSION', STAGS_VERSION);
define('TAXOPRESS_PRO_VERSION', '3.55.0');
define('TAXOPRESS_PRO_EDD_ITEM_ID', 608);
define('TAXOPRESS_EDD_STORE_URL', 'https://taxopress.com');
define('TAXOPRESS_PLUGIN_AUTHOR', 'PublishPress');

/**
 * Display a permanent notice about receiving future TaxoPress updates.
 *
 * @return void
 */
function taxopress_pro_publishpress_contact_notice()
{
    $current_screen      = function_exists('get_current_screen') ? get_current_screen() : null;
    $is_plugins_screen   = $current_screen && in_array($current_screen->id, ['plugins', 'plugins-network'], true);
    $is_taxopress_screen = function_exists('is_taxopress_admin_page') && is_taxopress_admin_page();

    if (
        ! current_user_can('manage_options')
        || (! $is_taxopress_screen && ! $is_plugins_screen)
    ) {
        return;
    }
    ?>
    <div class="notice notice-success taxopress-notice taxopress-pro-publishpress-contact-notice">
        <h2><?php esc_html_e('Please contact us about your version of TaxoPress.', 'taxopress-pro'); ?></h2>
        <p><?php esc_html_e('The TaxoPress plugin is still going strong, but it has moved to a new website. You will need to contact us to keep getting plugin updates.', 'taxopress-pro'); ?></p>
        <p>
            <a
                class="button button-primary"
                href="<?php echo esc_url('https://taxopress.com/publishpress/'); ?>"
                target="_blank"
                rel="noopener noreferrer"
            ><?php esc_html_e('Click here to contact us', 'taxopress-pro'); ?></a>
        </p>
    </div>
    <style>
        .taxopress-pro-publishpress-contact-notice {
            border-left-color: #1f48ac;
        }

        .taxopress-pro-publishpress-contact-notice p,
        .taxopress-pro-publishpress-contact-notice .button {
            font-size: 15px;
        }

        .taxopress-pro-publishpress-contact-notice .button-primary,
        .taxopress-pro-publishpress-contact-notice .button-primary:hover,
        .taxopress-pro-publishpress-contact-notice .button-primary:active,
        .taxopress-pro-publishpress-contact-notice .button-primary:focus {
            border-color: #1f48ac;
            background: #1f48ac;
        }
    </style>
    <?php
}

add_action('admin_notices', 'taxopress_pro_publishpress_contact_notice');
add_action('network_admin_notices', 'taxopress_pro_publishpress_contact_notice');

if (function_exists('init_free_simple_tags')) {
    remove_action('init', 'init_free_simple_tags', 0);
}

if (! defined('TAXOPRESS_PRO_LIB_VENDOR_PATH')) {
    define('TAXOPRESS_PRO_LIB_VENDOR_PATH', __DIR__ . '/lib/vendor');
}

// Check PHP min version
if (version_compare(PHP_VERSION, STAGS_MIN_PHP_VERSION, '<')) {
    require STAGS_DIR . '/inc/class.compatibility.php';
// possibly display a notice, trigger error
    add_action('admin_init', array('SimpleTags_Compatibility', 'admin_init'));
// stop execution of this file
    return;
}

if (file_exists(__DIR__ . '/lib/vendor/autoload.php')) {
    require_once __DIR__ . '/lib/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (! class_exists('SimpleTags_Plugin')) {
    require STAGS_DIR . '/inc/loads.php';
}

// Activation, uninstall
register_activation_hook(__FILE__, array('SimpleTags_Plugin', 'activation'));
register_deactivation_hook(__FILE__, array('SimpleTags_Plugin', 'deactivation'));
add_action('init', 'init_simple_tags', 0);
// *** Pro includes ***
require_once(dirname(__FILE__) . '/includes-pro/load.php');
