<?php

namespace Wpae\App\License;

use PMXE_Plugin;

final class Licensing
{
    const CRON_HOOK = 'pmxe_license_recheck';

    /** Separate from CRON_HOOK so an out-of-band refresh cannot unschedule the daily event. */
    const REFRESH_HOOK = 'pmxe_license_recheck_now';

    const KEY_OPTION = 'license';

    const REASON_ANSWER = 'answer';

    const STATUS_OPTION = 'license_status';

    const TIMEOUT = 15;

    const ATTACHED_TIMEOUT = 3;

    /** Throttles the fallback for a site whose WP-Cron is not running. */
    const LOCK_TRANSIENT = 'pmxe_license_recheck_lock';

    const LOCK_SECONDS = 43200;

    /** How late the daily event may be before the fallback treats it as dead. */
    const OVERDUE_AFTER = 86400;

    /** @var self|null */
    private static $instance;

    /** @var LicenseState */
    private $state;

    /** @var Records */
    private $records;

    /** @var OptionsGateway */
    private $options;

    /** @var callable */
    private $cronSpawns;

    /** @var callable */
    private $finishesRequest;

    /** @var int */
    private $timeout = self::TIMEOUT;

    /** @var NetworkLicence */
    private $network;

    /** @var bool|null */
    private $licensedMemo;

    /** @var array{state:string,reason:string,grace_until:int}|null */
    private $stateMemo;

    /** @param  callable|null  $cronSpawns  Returns whether WordPress will spawn a cron loopback. */
    public function __construct(?OptionsGateway $options = null, ?LicenseState $state = null, ?Records $records = null, $cronSpawns = null, $finishesRequest = null, ?NetworkLicence $network = null)
    {
        $this->options = $options ?: new PluginOptionsGateway();
        $this->state = $state ?: new LicenseState($this->options);
        $this->records = $records ?: new Records();
        $this->cronSpawns = is_callable($cronSpawns) ? $cronSpawns : [__CLASS__, 'wpCronSpawns'];
        $this->finishesRequest = is_callable($finishesRequest) ? $finishesRequest : [__CLASS__, 'finishRequest'];
        $this->network = $network ?: new NetworkLicence();
    }

    /** @return bool  false only for DISABLE_WP_CRON; a real crontab is invisible here */
    public static function wpCronSpawns()
    {
        return ! (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON);
    }

    /** @return bool  whether the client is no longer waiting; LiteSpeed ships only the second name */
    public static function finishRequest()
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();

            return true;
        }

        if (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();

            return true;
        }

        return false;
    }

    /** @return self */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function reset()
    {
        self::$instance = null;
    }

    /** @return LicenseState */
    public function state()
    {
        return $this->state;
    }

    /** Nothing registered here may talk to the server during a page render: check() is a fifteen-second wp_remote_get. */
    public function boot()
    {
        add_action('init', [$this, 'scheduleRecheck']);
        // backgroundRecheck(), not check(): check() alone skips the result table.
        add_action(self::CRON_HOOK, [$this, 'backgroundRecheck']);
        add_action(self::REFRESH_HOOK, [$this, 'backgroundRecheck']);
        add_action('admin_init', [$this, 'queueRefreshIfDue'], 1);
    }

    /** register_deactivation_hook() cannot take an instance method on a lazy singleton. */
    public static function deactivateHook()
    {
        self::instance()->deactivate();
    }

    /** The offset is JITTER, not a delay: a fixed one puts every install on one minute. */
    public function scheduleRecheck()
    {
        if (! wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + rand(0, DAY_IN_SECONDS), 'daily', self::CRON_HOOK);
        }
    }

    /**
     * Whether the daily event is missing, or so late that nothing is running
     * it. A due timestamp in the past is normal, so only a full day counts.
     *
     * @return bool
     */
    public function recheckEventIsOverdue()
    {
        $next = wp_next_scheduled(self::CRON_HOOK);

        if (! $next) {
            return true;
        }

        return (time() - (int) $next) > self::OVERDUE_AFTER;
    }

    private function rescheduleRecheck()
    {
        $next = wp_next_scheduled(self::CRON_HOOK);

        if ($next) {
            wp_unschedule_event($next, self::CRON_HOOK);
        }

        wp_schedule_event(time() + rand(0, DAY_IN_SECONDS), 'daily', self::CRON_HOOK);
    }

    public function deactivate()
    {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);

        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }

        wp_clear_scheduled_hook(self::CRON_HOOK);
        wp_clear_scheduled_hook(self::REFRESH_HOOK);

        $this->state->forgetOnDeactivate();
    }

    /** @return bool  whether a refresh was arranged; makes NO request itself */
    public function queueRefreshIfDue()
    {
        if (! $this->state->isDueForRecheck()) {
            return false;
        }

        // Keyed on THE EVENT, not the age of the answer, or every upgraded site arrives at once.
        if ($this->licenseKey() === '') {
            return false;
        }

        if (! $this->recheckEventIsOverdue()) {
            return false;
        }

        if (get_transient(self::LOCK_TRANSIENT)) {
            return false;
        }

        // Put it back first, so a site whose event was lost is not here tomorrow too.
        $this->rescheduleRecheck();

        set_transient(self::LOCK_TRANSIENT, 1, self::LOCK_SECONDS);

        return $this->queueRefresh();
    }

    /** @return bool  whether a refresh was arranged; Settings screen only, or this is hourly */
    public function queueForcedRecheck()
    {
        if (! $this->shouldForceRecheck()) {
            return false;
        }

        return $this->queueRefresh();
    }

    /** @return bool */
    private function queueRefresh()
    {
        if (! wp_next_scheduled(self::REFRESH_HOOK)) {
            wp_schedule_single_event(time(), self::REFRESH_HOOK);
        }

        if (! call_user_func($this->cronSpawns)) {
            // refreshIfDue() asks again there, so anything that got in first
            // makes it a no-op.
            add_action('shutdown', [$this, 'refreshOnShutdown'], PHP_INT_MAX);
        }

        return true;
    }

    /** @return bool  not isDueForRecheck() alone, which would drop a forced recheck */
    private function recheckWanted()
    {
        return $this->state->isDueForRecheck() || $this->shouldForceRecheck();
    }

    /** @return LicenseStatus|null  shutdown still has the client connected, so detach first */
    public function refreshOnShutdown()
    {
        if (! $this->recheckWanted()) {
            return null;
        }

        if (! call_user_func($this->finishesRequest)) {
            $this->timeout = self::ATTACHED_TIMEOUT;
        }

        try {
            return $this->refreshIfDue();
        } finally {
            $this->timeout = self::TIMEOUT;
        }
    }

    /** @return LicenseStatus|null */
    public function refreshIfDue()
    {
        if (! $this->recheckWanted()) {
            return null;
        }

        $this->recheck(false);

        return $this->state->answer();
    }

    /**
     * Takes no argument on purpose: do_action() hands callbacks whatever it fired
     * with, and a truthy first argument would turn this into a manual Save.
     *
     * @return array{options:array<string,mixed>,changed:bool}
     */
    public function backgroundRecheck()
    {
        // Also here: this path reads options through the plugin's own accessor.
        $this->migrateForGateRelease();

        return $this->recheck(false);
    }

    /** @return bool  whether the one-shot ran in this call */
    public function migrateForGateRelease()
    {
        $options = $this->options->all();

        // The state's clock, not the wall clock: every other deadline is measured on it.
        $result = GateMigration::apply($options, $this->state->now());

        if (! $result['ran']) {
            return false;
        }

        // updateOption() throws for anything outside the declared set.
        $values = [LicenseState::OPTION_GATE_MIGRATED => 1];

        if ($result['granted']) {
            $values[LicenseState::OPTION_GRACE_UNTIL]
                = (int) $result['options'][LicenseState::OPTION_GRACE_UNTIL];
        }

        $this->options->update($values);

        $this->resetMemo();

        GateMigration::schedule($result['granted']);

        return true;
    }

    /** @return array{options:array<string,mixed>,changed:bool,status:LicenseStatus|null} */
    public function recheck($manual = false)
    {
        $result = LicenseRefresh::run($this->options->all(), $manual, $this);

        $options = $result['options'];

        $values = [
            self::STATUS_OPTION => isset($options[self::STATUS_OPTION]) ? $options[self::STATUS_OPTION] : '',
            LicenseState::OPTION_GRACE_UNTIL => (int) $options[LicenseState::OPTION_GRACE_UNTIL],
        ];

        if (isset($options[LicenseState::OPTION_CHECKED_AT])) {
            $values[LicenseState::OPTION_CHECKED_AT] = (int) $options[LicenseState::OPTION_CHECKED_AT];
        }

        $this->options->update($values);

        $this->resetMemo();

        return $result;
    }

    /** @return LicenseStatus|null  null when the answer was non-definitive */
    public function check($key = null)
    {
        $check = new LicenseCheck($this->state, new ResponseAdapter(), [$this, 'request']);

        $status = $check->run(
            (string) $this->options->get('info_api_url_new', 'https://update.wpallimport.com'),
            is_string($key) && $key !== '' ? $key : $this->licenseKey(),
            PMXE_Plugin::getEddName(),
            home_url()
        );

        if ($status !== null) {
            $this->mirrorLegacyStatus($status);
        }

        $this->resetMemo();

        return $status;
    }

    /** Written only for a definitive answer: anything else stores a null status. */
    private function mirrorLegacyStatus(LicenseStatus $status)
    {
        $literal = $status->legacyLiteral();

        if ($literal === null) {
            return;
        }

        if ($this->options->get(self::STATUS_OPTION, '') === $literal) {
            return;
        }

        $this->options->update([self::STATUS_OPTION => $literal]);
    }

    /** @return array{error:bool,code:int,body:string} */
    public function request($url)
    {
        $response = wp_remote_get(esc_url_raw($url), ['timeout' => $this->timeout, 'sslverify' => true]);

        if (is_wp_error($response)) {
            return ['error' => true, 'code' => 0, 'body' => ''];
        }

        return [
            'error' => false,
            'code' => (int) wp_remote_retrieve_response_code($response),
            'body' => (string) wp_remote_retrieve_body($response),
        ];
    }

    /** @return string */
    public function licenseKey()
    {
        $stored = $this->options->get(self::KEY_OPTION, '');

        if (! is_string($stored) || $stored === '') {
            return '';
        }

        return (string) PMXE_Plugin::decode($stored);
    }

    /** @return bool  means "not entitled" only when determinable() is true */
    public function isLicensed()
    {
        if ($this->licensedMemo !== null) {
            return $this->licensedMemo;
        }

        return $this->licensedMemo = StateNames::entitles($this->licenseState());
    }

    /**
     * A grace window this version has not yet asked the server about: the one
     * the upgrade opened on the previous version's stored literal, which can be
     * long out of date. Settings asks the server before it says anything, and
     * the site-wide notice holds its alarm until the first check has been
     * made, so the two cannot disagree on the first load after an update.
     *
     * @return bool
     */
    public function awaitingFirstAnswer()
    {
        return $this->licenseKey() !== ''
            && $this->state->checkedAt() === 0
            && $this->state->lastAttemptAt() === null
            && $this->licenseState()['state'] === StateNames::GRACE;
    }

    /** @return array{status:mixed,has_key:bool,grace_until:int}|null  the main site's licence, on a sub-site of a network */
    public function networkLicence()
    {
        return $this->network->mainSite();
    }

    /** @return array{state:string,reason:string}  the MAIN site's on an inheriting sub-site */
    public function licenseState()
    {
        $resolved = $this->resolveState();

        return ['state' => $resolved['state'], 'reason' => $resolved['reason']];
    }

    /** @return int  the deadline for the state licenseState() reported; 0 when there is none to quote */
    public function licenseDeadline()
    {
        return $this->resolveState()['grace_until'];
    }

    /** @return array{state:string,reason:string,grace_until:int} */
    private function resolveState()
    {
        if ($this->stateMemo !== null) {
            return $this->stateMemo;
        }

        $now = $this->state->now();
        $key = $this->licenseKey();
        $deadline = $this->state->pluginGraceUntil();

        $status = $this->legacyStatus();
        $hasKey = $key !== '';
        $local = StateNames::of($status, $hasKey, $deadline, $now);

        // Only a grace state carries a deadline; a cleared key still has the row.
        $local['grace_until'] = $local['state'] === StateNames::GRACE ? $deadline : 0;

        // A cached entitling answer wins whatever the legacy literal says.
        $answer = $this->state->answerFor($key);

        if ($answer !== null && $answer->entitles()) {
            $local = ['state' => StateNames::VALID, 'reason' => '', 'grace_until' => 0];
        }

        // Skipping this keeps switch_to_blog() off every licensed sub-site request.
        if ($local['state'] === StateNames::VALID) {
            return $this->stateMemo = $local;
        }

        $inherited = $this->inheritedState();

        if (! StateNames::entitles($inherited)) {
            // A keyless sub-site has no licence of its own to report on, only the network's.
            if ($local['state'] === StateNames::NO_KEY && $inherited['state'] !== StateNames::NO_KEY) {
                return $this->stateMemo = $inherited;
            }

            return $this->stateMemo = $local;
        }

        if (! StateNames::entitles($local)) {
            return $this->stateMemo = $inherited;
        }

        // Both entitle, so what closes is the LATER of the two windows.
        if ($inherited['state'] === StateNames::VALID
            || (int) $inherited['grace_until'] > (int) $local['grace_until']) {
            return $this->stateMemo = $inherited;
        }

        return $this->stateMemo = $local;
    }

    /** @return array{state:string,reason:string,grace_until:int}  not entitling with no main site to read */
    private function inheritedState()
    {
        $main = $this->network->mainSite();

        if ($main === null) {
            return ['state' => StateNames::NO_KEY, 'reason' => '', 'grace_until' => 0];
        }

        $state = StateNames::of($main['status'], $main['has_key'], $main['grace_until'], $this->state->now());

        // The MAIN site's deadline, because it is the main site's state being reported.
        $state['grace_until'] = $state['state'] === StateNames::GRACE ? (int) $main['grace_until'] : 0;

        return $state;
    }

    /** Never through getOption(): a stored null there makes PMXE_Plugin::getOption() throw. */
    private function legacyStatus()
    {
        return $this->options->get(self::STATUS_OPTION, '');
    }

    public function resetMemo()
    {
        $this->licensedMemo = null;
        $this->stateMemo = null;
    }

    /** @return bool  not isLicensed(), which is true throughout the grace windows this is for */
    public function shouldForceRecheck()
    {
        $key = $this->licenseKey();

        if ($key === '') {
            return false;
        }

        $answer = $this->state->answerFor($key);

        if ($answer !== null && $answer->entitles()) {
            return false;
        }

        return $this->state->isPastRetryFloor();
    }

    /** @return bool  false when the option read raised, so every answer below is a default */
    public function determinable()
    {
        return $this->options->readable();
    }

    /** @return string */
    public function status()
    {
        return $this->state->status($this->licenseKey());
    }

    /** @return string  the GROUND entitling this site, or '' when nothing does */
    public function reason()
    {
        $answer = $this->state->answerFor($this->licenseKey());

        return $answer !== null && $answer->entitles() ? self::REASON_ANSWER : '';
    }

    /** @return string[] */
    public function problems()
    {
        return $this->state->problems();
    }

    /** @return int|null */
    public function graceUntil()
    {
        return $this->state->graceUntil();
    }

    /** @return int */
    public function checkedAt()
    {
        return $this->state->checkedAt();
    }

    /** @return bool */
    public function recordsExist()
    {
        return $this->records->exist();
    }
}
