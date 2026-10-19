<?php

namespace Wpae\App\License;

/**
 * One-shot at gate release: a site with a key but no cached `valid` answer gets
 * a fresh grace window and an expedited recheck.
 *
 * It runs from the constructor, not `admin_init`, which never fires on a
 * cron-only or sub-site install, and it decides over the merged options array
 * without writing: PMXE_Plugin::$instance is still null here, so the plugin's
 * own option accessors, and the unfiltered read below, would recurse.
 */
final class GateMigration
{
    /** 30 days. */
    const WINDOW = 2592000;

    /** A minute, not immediately: the cron spawn of this same request picks it up. */
    const EXPEDITED_DELAY = 60;

    const STATUS_OPTION = 'license_status';

    public static function due(array $options)
    {
        $flag = isset($options[LicenseState::OPTION_GATE_MIGRATED])
            ? $options[LicenseState::OPTION_GATE_MIGRATED]
            : 0;

        return ! (is_numeric($flag) && (int) $flag > 0);
    }

    /**
     * @return array{options:array<string,mixed>,ran:bool,granted:bool}
     */
    public static function apply(array $options, $now = null)
    {
        if (! self::due($options)) {
            return ['options' => $options, 'ran' => false, 'granted' => false];
        }

        $now = $now === null ? time() : (int) $now;
        $granted = false;

        $key = LicenseRefresh::decodedKey($options);

        if ($key !== '' && ! self::cachedValid($options)) {
            // Overwrites a part-elapsed window: a site three days from the end
            // of one is exactly what this exists for.
            $options[LicenseState::OPTION_GRACE_UNTIL] = $now + self::WINDOW;
            $options[LicenseState::OPTION_STATE] = self::stateOwnedBy($options, $key);
            $granted = true;
        }

        // Same array, one write: no window where a site is granted but unflagged.
        $options[LicenseState::OPTION_GATE_MIGRATED] = 1;

        return ['options' => $options, 'ran' => true, 'granted' => $granted];
    }

    /**
     * The window belongs to the key it was granted for. Without the fingerprint
     * the first key a customer saves is taken for the one they already had, and
     * a different key the server rejects would inherit the window.
     *
     * @return array<string,mixed>
     */
    private static function stateOwnedBy(array $options, $key)
    {
        $state = isset($options[LicenseState::OPTION_STATE]) && is_array($options[LicenseState::OPTION_STATE])
            ? $options[LicenseState::OPTION_STATE]
            : [];

        if (empty($state['key'])) {
            $state['key'] = LicenseState::fingerprint($key);
        }

        return $state;
    }

    /** Both grounds: the legacy literal, and a cached answer that entitles without one. */
    private static function cachedValid(array $options)
    {
        $status = isset($options[self::STATUS_OPTION]) ? $options[self::STATUS_OPTION] : '';

        if (GraceTable::isValid($status)) {
            return true;
        }

        $state = isset($options[LicenseState::OPTION_STATE]) && is_array($options[LicenseState::OPTION_STATE])
            ? $options[LicenseState::OPTION_STATE]
            : [];

        $answer = LicenseStatus::fromArray(isset($state['answer']) ? $state['answer'] : null);

        return $answer !== null && $answer->entitles();
    }

    /** The daily event goes through Licensing so the jitter expression has one owner. */
    public static function schedule($granted)
    {
        Licensing::instance()->scheduleRecheck();

        if (! $granted) {
            return;
        }

        if (! wp_next_scheduled(Licensing::REFRESH_HOOK)) {
            wp_schedule_single_event(time() + self::EXPEDITED_DELAY, Licensing::REFRESH_HOOK);
        }
    }
}
