<?php

namespace Wpae\App\Service\License;


use \PMXE_Plugin;
use Wpae\App\License\LicenseStatus;
use Wpae\App\License\ResponseAdapter;

class LicenseActivator
{
    const CONTEXT_PMXE = 1;
    const CONTEXT_SCHEDULING = 2;

    /**
     * The endpoint dispatches on edd_action, so the caller has to say which one
     * it wants. Both callers used to share a hardcoded `check_license`, which
     * made activateLicense() ask what state the licence was already in and
     * leave an unactivated key unactivated (soflyy/wp-all-import-pro#1946).
     *
     * @param  string  $eddAction  `activate_license` or `check_license`
     */
    private function ask(array $options, $key, $productName, $eddAction, ?array &$body = null)
    {
        $api_params = array(
            'edd_action' => $eddAction,
            'license' => $key,
            'item_name' => urlencode($productName),
            'url' => home_url()
        );

        $url = esc_url_raw(add_query_arg($api_params, $options['info_api_url_new'] . '/check_license'));

        $response = wp_remote_get($url, array('timeout' => 15, 'sslverify' => true));

        if (is_wp_error($response)) {
            return null;
        }

        $raw = (string) wp_remote_retrieve_body($response);
        $decoded = json_decode($raw, true);
        $body = is_array($decoded) ? $decoded : array();

        $adapter = new ResponseAdapter();

        return $adapter->adapt(array(
            'error' => false,
            'code' => (int) wp_remote_retrieve_response_code($response),
            'body' => $raw,
        ), time());
    }

    public function activateLicense($productName, $context = self::CONTEXT_PMXE)
    {
        if($context == self::CONTEXT_PMXE) {
            $licenseField = 'license';
            $licenseStatusField = 'license_status';
        } else {
            $licenseField = 'scheduling_license';
            $licenseStatusField = 'scheduling_license_status';
        }

        $options = PMXE_Plugin::getInstance()->getOption();

        if ($productName !== false) {
            $body = array();
            $status = $this->ask($options, PMXE_Plugin::decode($options[$licenseField]), $productName, 'activate_license', $body);

            // Non-definitive: a transport failure, a 503, or a body that is not
            // an answer. Leave the stored status exactly as it was.
            if ($status === null || $status->license() === null) {
                return false;
            }

            if ('scheduling_license' == $licenseField) {
                update_option('wpai_wpae_scheduling_license_site_limit', isset($body['license_limit']) && is_numeric($body['license_limit']) ? $body['license_limit'] : 0);
            }

            // Only the field this method decided: ask() blocks, so writing the whole
            // pre-request array back would re-assert options changed meanwhile.
            PMXE_Plugin::getInstance()->updateOption(array($licenseStatusField => $status->license()));
        }
    }

    /**
     * @param $productName
     * @param $options
     * @param $context
     * @return bool
     */
    public function checkLicense($productName, $options, $context)
    {
        if($context == self::CONTEXT_PMXE) {
            $licenseField = 'license';
        } else {
            $licenseField = 'scheduling_license';
        }

        if (!empty($options[$licenseField])) {

            if ($productName !== false) {

                $status = $this->ask($options, PMXE_Plugin::decode($options[$licenseField]), $productName, 'check_license');

                return $status === null ? false : $status->license();

            }
        }

        return false;
    }
}