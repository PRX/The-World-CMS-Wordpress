<?php

namespace Wpae\App\License;

/**
 * A licence answer, normalised. BLOCKED is not a server state: it is where
 * disabled, host_mismatch, unknown_key, no_key and any unrecognised literal
 * arrive while the server answers with the single legacy `invalid`.
 */
final class LicenseStatus
{
    const VALID = 'valid';

    const GRACE = 'grace';

    const EXPIRED = 'expired';

    const DISABLED = 'disabled';

    const LIMIT_REACHED = 'limit_reached';

    const HOST_MISMATCH = 'host_mismatch';

    const UNKNOWN_KEY = 'unknown_key';

    const NO_KEY = 'no_key';

    const UNAVAILABLE = 'unavailable';

    const BLOCKED = 'blocked';

    /** @var string[]  `unavailable` is absent: its wire form is the 503, never a stored answer. */
    const SERVER_STATES = [
        self::VALID,
        self::GRACE,
        self::EXPIRED,
        self::DISABLED,
        self::LIMIT_REACHED,
        self::HOST_MISMATCH,
        self::UNKNOWN_KEY,
        self::NO_KEY,
    ];

    /** @var string[] */
    const PROBLEMS = ['expired', 'limit', 'invalid', 'host_mismatch'];

    /** @var string */
    private $status;

    /** @var string|null */
    private $serverStatus;

    /** @var string|null */
    private $license;

    /** @var string[] */
    private $problems;

    /** @var bool */
    private $grace;

    /** @var string|null */
    private $graceReason;

    /** @var int|null */
    private $graceUntil;

    /** @var mixed */
    private $expires;

    /** @var int */
    private $checkedAt;

    public function __construct(array $fields)
    {
        $status = isset($fields['status']) ? (string) $fields['status'] : self::BLOCKED;

        $this->status = $status;
        $this->serverStatus = isset($fields['server_status']) && $fields['server_status'] !== null
            ? (string) $fields['server_status']
            : null;
        $this->license = isset($fields['license']) && $fields['license'] !== null
            ? (string) $fields['license']
            : null;
        $this->problems = self::normaliseProblems(isset($fields['problems']) ? $fields['problems'] : []);
        $this->grace = ! empty($fields['grace']);
        $this->graceReason = isset($fields['grace_reason']) && $fields['grace_reason'] !== null
            ? (string) $fields['grace_reason']
            : null;
        $this->graceUntil = isset($fields['grace_until']) && is_numeric($fields['grace_until'])
            ? (int) $fields['grace_until']
            : null;
        $this->expires = isset($fields['expires']) ? $fields['expires'] : null;
        $this->checkedAt = isset($fields['checked_at']) ? (int) $fields['checked_at'] : 0;
    }

    /** @return string[]  unknown entries dropped, sorted and unique */
    private static function normaliseProblems($problems)
    {
        if (! is_array($problems)) {
            return [];
        }

        $known = [];

        foreach ($problems as $problem) {
            if (is_string($problem) && in_array($problem, self::PROBLEMS, true)) {
                $known[$problem] = true;
            }
        }

        $known = array_keys($known);
        sort($known);

        return $known;
    }

    /** @return string */
    public function status()
    {
        return $this->status;
    }

    /** @return string|null  verbatim, including a value this build does not recognise */
    public function serverStatus()
    {
        return $this->serverStatus;
    }

    /** @return string|null */
    public function license()
    {
        return $this->license;
    }

    /** @return string[] */
    public function problems()
    {
        return $this->problems;
    }

    /** @return bool */
    public function inGrace()
    {
        return $this->grace;
    }

    /** @return string|null */
    public function graceReason()
    {
        return $this->graceReason;
    }

    /** @return int|null */
    public function graceUntil()
    {
        return $this->graceUntil;
    }

    /** @return mixed */
    public function expires()
    {
        return $this->expires;
    }

    /** @return int */
    public function checkedAt()
    {
        return $this->checkedAt;
    }

    /** @return bool  `grace` is the server's window, not this plugin's: two clocks would stack. */
    public function entitles()
    {
        return $this->status === self::VALID;
    }

    /** @return string|null  A graced answer ships `valid` beside it; stored as-is, `license_status` would entitle. */
    public function legacyLiteral()
    {
        if ($this->status === self::GRACE) {
            return $this->graceReason !== null ? $this->graceReason : self::EXPIRED;
        }

        return $this->license;
    }

    /** @return int|null  null for "lifetime" and for anything unreadable, never an expiry */
    public function expiresAt()
    {
        $raw = $this->expires;

        if ($raw === null || $raw === false || $raw === '' || $raw === 'lifetime') {
            return null;
        }

        if (is_int($raw)) {
            return $raw;
        }

        if (is_string($raw) && ctype_digit($raw)) {
            return (int) $raw;
        }

        if (is_string($raw)) {
            // UTC is pinned so one licence does not expire at two moments on two servers.
            try {
                return (new \DateTimeImmutable($raw, new \DateTimeZone('UTC')))->getTimestamp();
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /** @return array<string,mixed> */
    public function toArray()
    {
        return [
            'status' => $this->status,
            'server_status' => $this->serverStatus,
            'license' => $this->license,
            'problems' => $this->problems,
            'grace' => $this->grace,
            'grace_reason' => $this->graceReason,
            'grace_until' => $this->graceUntil,
            'expires' => $this->expires,
            'checked_at' => $this->checkedAt,
        ];
    }

    /** @return self|null */
    public static function fromArray($stored)
    {
        if (! is_array($stored) || ! isset($stored['status'])) {
            return null;
        }

        return new self($stored);
    }
}
