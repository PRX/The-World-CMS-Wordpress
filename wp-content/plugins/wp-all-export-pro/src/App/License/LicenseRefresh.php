<?php

namespace Wpae\App\License;

use PMXE_Plugin;

final class LicenseRefresh
{
    const PRODUCT_CLASS = 'PMXE_Plugin';

    const KEY_OPTION = 'license';

    /** Never read through getOption(): a stored null there makes it throw. */
    const STATUS_OPTION = 'license_status';

    const OPTION_FILTER = 'wp_all_export_config_options';

    const SLUG = 'wp-all-export-pro';

    /**
     * @param  bool  $manual  True for a Save on the licence form, false for a background recheck.
     * @return array{options:array<string,mixed>,changed:bool,status:LicenseStatus|null}
     */
    public static function run(array $options, $manual, ?Licensing $licensing = null)
    {
        $manual = (bool) $manual;
        $licensing = $licensing ?: Licensing::instance();
        $state = $licensing->state();
        $now = $state->now();

        // Read the filtered copy, write the caller's: a key injected through the
        // filter is checked without being persisted.
        $filtered = self::filtered($options);

        $key = self::decodedKey($filtered);

        // A new key inherits nothing, deadline included: the window is keyed on
        // `grace_until == 0`, so a leftover elapsed value would deny it.
        $before = $state->keyFingerprint();
        $state->syncKey($key);

        if ($before !== '' && $before !== LicenseState::fingerprint($key)) {
            $filtered = self::reset($filtered);
            $options = self::reset($options);
        }

        // From the state, not the array: syncKey() above zeroed it on a key change.
        $prev = self::previous($filtered, $key);
        $prev['grace_until'] = $state->pluginGraceUntil();

        $cached = $state->answer();
        $prev['entitles'] = $cached !== null && $cached->entitles();

        $wasLicensed = GraceTable::previouslyLicensed($prev, $now);

        $answer = null;
        $status = null;

        if ($key !== '') {
            $status = $licensing->check($key);
            $answer = self::answerOf($status);
        }

        // $answer is null for no key and for a non-definitive answer alike.
        $result = GraceTable::apply($prev, $answer, $manual, $now);

        $state->setPluginGraceUntil($result['grace_until']);

        $options = self::applyTo($options, $result);

        $isLicensed = GraceTable::previouslyLicensed([
            'status' => $result['status'],
            'grace_until' => $result['grace_until'],
            'has_key' => $key !== '',
            'entitles' => $answer !== null && ! empty($answer['entitles']),
        ], $now);

        $changed = $isLicensed !== $wasLicensed;

        if ($changed || $manual) {
            self::flushUpdaterCaches();
        }

        $licensing->resetMemo();

        return ['options' => $options, 'changed' => $changed, 'status' => $status];
    }

    private static function filtered(array $options)
    {
        if (! function_exists('apply_filters')) {
            return $options;
        }

        $filtered = apply_filters(self::OPTION_FILTER, $options);

        return is_array($filtered) ? $filtered : $options;
    }

    private static function reset(array $options)
    {
        $options[self::STATUS_OPTION] = '';
        $options['license_checked_at'] = 0;

        // license_grace_until is not zeroed here: syncKey() does it on the state.
        return $options;
    }

    /** @return string */
    public static function decodedKey(array $options)
    {
        $stored = isset($options[self::KEY_OPTION]) ? $options[self::KEY_OPTION] : '';

        if (! is_scalar($stored) || (string) $stored === '') {
            return '';
        }

        // encode('') is a non-empty blob, so this must decode before it can tell.
        return trim((string) call_user_func([self::PRODUCT_CLASS, 'decode'], $stored));
    }

    /** @return array{status:mixed,grace_until:int,has_key:bool} */
    public static function previous(array $options, $key)
    {
        return [
            'status' => isset($options[self::STATUS_OPTION]) ? $options[self::STATUS_OPTION] : '',
            'grace_until' => isset($options['license_grace_until']) && is_numeric($options['license_grace_until'])
                ? (int) $options['license_grace_until']
                : 0,
            'has_key' => $key !== '',
        ];
    }

    /** @return array{literal:string,entitles:bool}|null */
    private static function answerOf(?LicenseStatus $status = null)
    {
        if ($status === null) {
            return null;
        }

        $literal = $status->legacyLiteral();

        // The server's own grace_until is NOT passed on; two clocks would stack.
        return [
            // A definitive answer carrying no legacy literal still has a status.
            'literal' => $literal === null ? $status->status() : $literal,
            'entitles' => $status->entitles(),
        ];
    }

    /** @param  array{status:mixed,checked_at:int|null,grace_until:int}  $result */
    private static function applyTo(array $options, array $result)
    {
        $options[self::STATUS_OPTION] = $result['status'];
        $options['license_grace_until'] = (int) $result['grace_until'];

        if ($result['checked_at'] !== null) {
            $options['license_checked_at'] = (int) $result['checked_at'];
        }

        return $options;
    }

    /** The key is computed rather than read from PMXE_Plugin::$cache_key, which is '' when the constructor returned early. */
    public static function flushUpdaterCaches()
    {
        if (! function_exists('delete_transient')) {
            return;
        }

        $cacheKey = self::cacheKey();

        delete_transient($cacheKey);
        delete_option(self::SLUG . '_' . $cacheKey);
        delete_option(self::SLUG . '_timeout_' . $cacheKey);
        delete_site_transient('update_plugins');
    }

    /** @return string */
    public static function cacheKey()
    {
        $basename = class_exists(self::PRODUCT_CLASS) && defined('PMXE_Plugin::FILE')
            ? plugin_basename(PMXE_Plugin::FILE)
            : self::SLUG . '/' . self::SLUG . '.php';

        return md5('edd_plugin_' . sanitize_key($basename) . '_version_info');
    }
}
