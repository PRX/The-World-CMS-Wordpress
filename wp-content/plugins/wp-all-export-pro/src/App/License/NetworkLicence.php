<?php

namespace Wpae\App\License;

/**
 * A sub-site inherits the main site's licence: a network is sold one licence, so
 * the key lives on the main site and the sub-sites have none. The row is read
 * through switch_to_blog(), not get_blog_option() alone, because the stored key
 * is obfuscated with a salt kept in a per-site option. Decoded from a sub-site
 * it uses the wrong salt, and an absent key decodes to a non-empty blob.
 */
final class NetworkLicence
{
    const OPTION = 'PMXE_Plugin_Options';

    const PRODUCT_CLASS = 'PMXE_Plugin';

    const KEY_OPTION = 'license';

    const STATUS_OPTION = 'license_status';

    /** @var callable|null */
    private $reader;

    /** @param callable|null $reader  the main site's options array, or null when there is no other main site */
    public function __construct($reader = null)
    {
        $this->reader = is_callable($reader) ? $reader : [__CLASS__, 'readMainSite'];
    }

    /** @return array<string,mixed>|null */
    public static function readMainSite()
    {
        if (! function_exists('is_multisite') || ! is_multisite()) {
            return null;
        }

        if (! function_exists('is_main_site') || is_main_site()) {
            return null;
        }

        $mainId = function_exists('get_main_site_id') ? (int) get_main_site_id() : 1;

        if (! function_exists('switch_to_blog')) {
            $options = function_exists('get_blog_option') ? get_blog_option($mainId, self::OPTION) : null;

            return is_array($options) ? $options : null;
        }

        switch_to_blog($mainId);

        try {
            $options = get_option(self::OPTION);
        } finally {
            restore_current_blog();
        }

        return is_array($options) ? $options : null;
    }

    /** @return array{status:mixed,has_key:bool,grace_until:int}|null */
    public function mainSite()
    {
        $options = call_user_func($this->reader);

        if (! is_array($options)) {
            return null;
        }

        $status = isset($options[self::STATUS_OPTION]) ? $options[self::STATUS_OPTION] : '';
        $stored = isset($options[self::KEY_OPTION]) ? $options[self::KEY_OPTION] : '';

        return [
            'status' => $status,
            'has_key' => self::decodedKey($stored) !== '',
            'grace_until' => isset($options['license_grace_until']) && is_numeric($options['license_grace_until'])
                ? (int) $options['license_grace_until']
                : 0,
        ];
    }

    /** @return string */
    private static function decodedKey($stored)
    {
        if (! is_scalar($stored) || (string) $stored === '') {
            return '';
        }

        // encode('') is a non-empty blob, so a non-empty raw value proves nothing.
        return class_exists(self::PRODUCT_CLASS)
            ? trim((string) call_user_func([self::PRODUCT_CLASS, 'decode'], $stored))
            : trim((string) $stored);
    }
}
