<?php

namespace Wpae\App\License;

/**
 * The licence state a customer is in, named. Messaging keys off these rather
 * than off the server's literal, so the wording for "renew" and the wording for
 * "this key is not ours" cannot end up sharing a branch.
 */
final class StateNames
{
    const VALID = 'valid';

    const NO_KEY = 'no_key';

    const GRACE = 'grace';

    const BLOCKED_UNVERIFIED = 'blocked_unverified';

    const BLOCKED_INVALID = 'blocked_invalid';

    const BLOCKED_EXPIRED = 'blocked_expired';

    const BLOCKED_LIMIT = 'blocked_limit';

    const REASON_UNVERIFIED = 'unverified';

    const REASON_INVALID = 'invalid';

    const REASON_EXPIRED = 'expired';

    const REASON_LIMIT = 'limit';

    /** @var string[]  the literals that mean the seat limit rather than a bad key */
    const LIMIT_LITERALS = ['active sites limit reached', 'no_activations_left'];

    /** @return string */
    public static function reasonFor($status)
    {
        if (GraceTable::neverConfirmed($status)) {
            return self::REASON_UNVERIFIED;
        }

        $literal = is_string($status) ? strtolower(trim($status)) : '';

        if ($literal === 'expired') {
            return self::REASON_EXPIRED;
        }

        if (in_array($literal, self::LIMIT_LITERALS, true)) {
            return self::REASON_LIMIT;
        }

        return self::REASON_INVALID;
    }

    /**
     * @param  bool  $hasKey  whether a non-empty DECODED key is stored
     * @return array{state:string,reason:string}
     */
    public static function of($status, $hasKey, $graceUntil, $now)
    {
        // Before the status: a key removed outside the UI leaves its last literal behind.
        if (! $hasKey) {
            return ['state' => self::NO_KEY, 'reason' => ''];
        }

        if (GraceTable::isValid($status)) {
            return ['state' => self::VALID, 'reason' => ''];
        }

        $reason = self::reasonFor($status);

        if ((int) $graceUntil > (int) $now) {
            return ['state' => self::GRACE, 'reason' => $reason];
        }

        return ['state' => 'blocked_' . $reason, 'reason' => $reason];
    }

    /** @param array{state:string,reason:string} $state */
    public static function entitles(array $state)
    {
        return $state['state'] === self::VALID || $state['state'] === self::GRACE;
    }
}
