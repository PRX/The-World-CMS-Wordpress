<?php

namespace Wpae\App\License;

/**
 * license_grace_until is the plugin's only grace clock; it is NOT
 * LicenseStatus::graceUntil(), which is the server's and decides nothing here.
 */
final class LicenseState
{
    const OPTION_CHECKED_AT = 'license_checked_at';

    const OPTION_STATE = 'license_state';

    const OPTION_GRACE_UNTIL = 'license_grace_until';

    const OPTION_GATE_MIGRATED = 'license_gate_migrated';

    const VERSION = 1;

    const RECHECK_INTERVAL = 86400;    // 24 hours

    const RETRY_INTERVAL = 3600;       // the floor between two attempts

    /** Caps the doubling of the retry floor: an hour, two, four, eight, sixteen. */
    const MAX_BACKOFF_STEPS = 5;

    /** @var OptionsGateway */
    private $options;

    /** @var callable */
    private $clock;

    /** @var array<string,mixed>|null */
    private $state;

    /** @param  callable|null  $clock  Returns the current Unix time. */
    public function __construct(OptionsGateway $options, $clock = null)
    {
        $this->options = $options;
        $this->clock = is_callable($clock) ? $clock : 'time';
    }

    /** @return int */
    public function now()
    {
        return (int) call_user_func($this->clock);
    }

    /** @return array<string,mixed> */
    private function state()
    {
        if ($this->state !== null) {
            return $this->state;
        }

        $stored = $this->options->get(self::OPTION_STATE, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        $this->state = [
            'version' => isset($stored['version']) ? (int) $stored['version'] : self::VERSION,
            'key' => isset($stored['key']) ? (string) $stored['key'] : '',
            'answer' => isset($stored['answer']) ? $stored['answer'] : null,
            'last_attempt_at' => self::intOrNull($stored, 'last_attempt_at'),
            'failed_attempts' => self::failedAttemptsIn($stored),
        ];

        return $this->state;
    }

    /** @return int|null */
    private static function intOrNull(array $stored, $key)
    {
        return isset($stored[$key]) && is_numeric($stored[$key]) ? (int) $stored[$key] : null;
    }

    /** @return int  clamped: this is persisted site data and retryFloor() shifts by it */
    private static function failedAttemptsIn(array $stored)
    {
        $failures = self::intOrNull($stored, 'failed_attempts') ?: 0;

        return max(0, min($failures, self::MAX_BACKOFF_STEPS));
    }

    /** @param  int|null  $checkedAt  Written only when supplied. */
    private function persist(array $state, $checkedAt = null)
    {
        $this->state = $state;

        $values = [self::OPTION_STATE => $state];

        if ($checkedAt !== null) {
            $values[self::OPTION_CHECKED_AT] = (int) $checkedAt;
        }

        $this->options->update($values);
    }

    /** @return LicenseStatus|null */
    public function answer()
    {
        return LicenseStatus::fromArray($this->state()['answer']);
    }

    /**
     * The cached answer only while it is about $key. Remove drops the key but
     * cannot reach this record, so the answer for a key the site no longer has
     * would otherwise keep entitling it until the next recheck.
     *
     * @return LicenseStatus|null
     */
    public function answerFor($key)
    {
        if ($this->state()['key'] !== self::fingerprint($key)) {
            return null;
        }

        return $this->answer();
    }

    /** @return int */
    public function checkedAt()
    {
        $stored = $this->options->get(self::OPTION_CHECKED_AT, 0);

        return is_numeric($stored) ? (int) $stored : 0;
    }

    /** @return int|null */
    public function lastAttemptAt()
    {
        return $this->state()['last_attempt_at'];
    }

    /** @return int */
    public function failedAttempts()
    {
        return $this->state()['failed_attempts'];
    }

    /** @return int  0 means no window was ever granted for the key currently stored */
    public function pluginGraceUntil()
    {
        $stored = $this->options->get(self::OPTION_GRACE_UNTIL, 0);

        return is_numeric($stored) ? (int) $stored : 0;
    }

    public function setPluginGraceUntil($until)
    {
        $this->options->update([self::OPTION_GRACE_UNTIL => (int) $until]);
    }

    /** @return string */
    public function keyFingerprint()
    {
        return $this->state()['key'];
    }

    /** @return string  trimmed only: the server trims too, but compares case-sensitively */
    public static function normaliseKey($key)
    {
        return trim((string) $key);
    }

    /** @return string */
    public static function fingerprint($key)
    {
        $key = self::normaliseKey($key);

        return $key === '' ? '' : sha1($key);
    }

    /** @return bool  whether anything was reset; zeroing the deadline re-arms the grace window */
    public function syncKey($key)
    {
        $fingerprint = self::fingerprint($key);
        $state = $this->state();

        if ($state['key'] === $fingerprint) {
            return false;
        }

        // A FIRST sync is not a key change: the fingerprint is empty on an upgrade.
        $changed = $state['key'] !== '';

        $state['key'] = $fingerprint;
        $state['answer'] = null;

        $this->persist($state, 0);

        if ($changed) {
            $this->setPluginGraceUntil(0);
        }

        return true;
    }

    /** Called before every check-in; the retry floor is measured from it. */
    public function recordAttempt()
    {
        $state = $this->state();

        $state['last_attempt_at'] = $this->now();

        $this->persist($state);
    }

    public function recordDefinitive(LicenseStatus $status)
    {
        $state = $this->state();
        $at = $status->checkedAt() ?: $this->now();

        $state['answer'] = $status->toArray();
        $state['failed_attempts'] = 0;

        $this->persist($state, $at);
    }

    /**
     * Moves the CADENCE only. Nothing the entitlement decision reads may be
     * touched here, or an outage of ours decides a customer's entitlement.
     */
    public function recordNonDefinitive()
    {
        $state = $this->state();

        $state['failed_attempts'] = min($state['failed_attempts'] + 1, self::MAX_BACKOFF_STEPS);

        $this->persist($state);
    }

    /**
     * Clears the retry floor and nothing else. The cached answer and the grace
     * deadline are preserved, or every reactivate would hand out a fresh window.
     */
    public function forgetOnDeactivate()
    {
        $state = $this->state();

        $state['last_attempt_at'] = null;

        $this->persist($state);
    }

    /** @return string  what to show a customer, not whether the site is entitled */
    public function status($key = '')
    {
        $answer = $this->answerFor($key);

        if ($answer !== null) {
            return $answer->status();
        }

        if (self::fingerprint($key) === '') {
            return LicenseStatus::NO_KEY;
        }

        return LicenseStatus::UNAVAILABLE;
    }

    /** @return string[] */
    public function problems()
    {
        $answer = $this->answer();

        return $answer === null ? [] : $answer->problems();
    }

    /** @return int|null  the SERVER's payment deadline, not the plugin's grace clock */
    public function graceUntil()
    {
        $answer = $this->answer();

        return $answer === null ? null : $answer->graceUntil();
    }

    /** @return int  an hour after the first unanswered attempt, doubling per failure */
    private function retryFloor()
    {
        $failures = $this->state()['failed_attempts'];

        if ($failures < 1) {
            return self::RETRY_INTERVAL;
        }

        return self::RETRY_INTERVAL * (1 << ($failures - 1));
    }

    /** @return bool  a cost bound rather than a freshness policy */
    public function isPastRetryFloor()
    {
        $lastAttempt = $this->state()['last_attempt_at'];

        if ($lastAttempt === null) {
            return true;
        }

        $sinceAttempt = $this->now() - $lastAttempt;

        return $sinceAttempt < 0 || $sinceAttempt >= $this->retryFloor();
    }

    /** @return bool  a negative elapsed time counts as due, for a clock dragged backwards */
    public function isDueForRecheck()
    {
        if (! $this->isPastRetryFloor()) {
            return false;
        }

        $sinceAnswer = $this->now() - $this->checkedAt();

        return $sinceAnswer < 0 || $sinceAnswer >= self::RECHECK_INTERVAL;
    }
}
