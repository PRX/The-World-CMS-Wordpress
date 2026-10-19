<?php

namespace Wpae\App\License;

/**
 * The manual-versus-background result table, as one pure function.
 *
 * A background recheck is unasked-for, so a definitive refusal opens a window
 * first. A manual Save never opens or extends one, but a refusal for the same
 * key leaves a running window alone: the customer told to renew and save again
 * may save before the renewal reaches the server. A new key inherits nothing,
 * because LicenseState::syncKey() zeroes the deadline before this runs. The
 * plugin owns the only grace clock: a server-sent grace_until never replaces,
 * shortens or extends what is decided here.
 *
 * A window is granted once: the never-verified row keys on `grace_until == 0`
 * rather than `<= now`, because an elapsed window leaves a past timestamp behind
 * and that is how the site remembers it has already had its thirty days.
 */
final class GraceTable
{
    /** 30 days. */
    const WINDOW = 2592000;

    const VALID = 'valid';

    /** `''` before any check, `false` for a transport failure, `null` for a malformed body. */
    public static function neverConfirmed($status)
    {
        return $status === '' || $status === false || $status === null;
    }

    public static function isValid($status)
    {
        return is_string($status) && strtolower(trim($status)) === self::VALID;
    }

    public static function previouslyLicensed(array $prev, $now)
    {
        if (self::isValid(isset($prev['status']) ? $prev['status'] : '')) {
            return true;
        }

        if (! empty($prev['entitles'])) {
            return true;
        }

        return ! empty($prev['has_key']) && (int) $prev['grace_until'] > (int) $now;
    }

    /**
     * @param  array<string,mixed>  $prev  status, grace_until, has_key, entitles
     * @param  array<string,mixed>|null  $answer  the literal and whether it entitles;
     *         null when the answer was not definitive. Never the server's grace fields.
     * @return array{status:mixed,checked_at:int|null,grace_until:int}  a null
     *         checked_at means the field must not be written at all.
     */
    public static function apply(array $prev, $answer, $manual, $now)
    {
        $prev += ['status' => '', 'grace_until' => 0, 'has_key' => false, 'entitles' => false];

        $status = $prev['status'];
        $graceUntil = (int) $prev['grace_until'];
        $now = (int) $now;

        // checked_at records the last DEFINITIVE answer, so it is left alone.
        if (empty($prev['has_key'])) {
            return ['status' => '', 'checked_at' => null, 'grace_until' => 0];
        }

        if ($answer === null) {
            if (self::neverConfirmed($status) && $graceUntil === 0) {
                $graceUntil = $now + self::WINDOW;
            }

            return ['status' => $status, 'checked_at' => null, 'grace_until' => $graceUntil];
        }

        $licensedBefore = self::previouslyLicensed($prev, $now);

        $status = (string) $answer['literal'];

        if (! empty($answer['entitles'])) {
            return ['status' => $status, 'checked_at' => $now, 'grace_until' => 0];
        }

        if ($manual) {
            return ['status' => $status, 'checked_at' => $now, 'grace_until' => $graceUntil];
        }

        if ($licensedBefore && $graceUntil <= $now) {
            $graceUntil = $now + self::WINDOW;
        }

        return ['status' => $status, 'checked_at' => $now, 'grace_until' => $graceUntil];
    }
}
