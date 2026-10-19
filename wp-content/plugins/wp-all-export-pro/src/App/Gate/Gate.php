<?php

namespace Wpae\App\Gate;

/**
 * Refuses only when a licence source can answer AND this is the paid edition:
 * free and Pro share every hook, callback, option and table name.
 */
final class Gate
{
    const EDITION_CONSTANT = 'PMXE_EDITION';

    const PAID = 'paid';

    const SETTINGS_PAGE = 'pmxe-admin-settings';

    /**
     * Actions still dispatchable on the Settings slug while blocked. The
     * `_action` suffix is required: the dispatcher builds $actionName as
     * strtolower($action) . '_action', so bare verbs match nothing.
     *
     * @var string[]
     */
    const SETTINGS_ACTIONS = ['index_action', 'dismiss_action'];

    /**
     * Screens that stay reachable for a named action while blocked.
     *
     * @var array<string,string[]>
     */
    const SCREEN_ACTIONS = ['pmxe-admin-manage' => ['cancel_action']];

    /**
     * Routes the umbrella ajax dispatcher keeps open while blocked. Both manage
     * credentials or probe service health; neither runs an export.
     *
     * @var string[]
     */
    const API_ROUTES = ['schedulingConnection/index', 'schedulingLicense/saveSchedulingLicense'];

    /**
     * Cron actions that stay open while blocked.
     *
     * @var string[]
     */
    const CRON_OPEN = ['cancel'];

    /**
     * Cron actions decided against the export record rather than the action
     * name: `processing` advances a run already in flight, and the dispatcher
     * cannot tell a continuation from a fresh start before it loads the record.
     *
     * @var string[]
     */
    const CRON_PER_RECORD = ['processing'];

    /** @var self|null */
    private static $instance;

    /** @var LicenceSource */
    private $source;

    /** @var Messages */
    private $messages;

    /** @var callable|null */
    private $clientModeProbe;

    /** @var array<int,bool>  export id => admitted in this request */
    private $admitted = [];

    /**
     * Kept rather than rethrown: the gate is bound to all_admin_notices and
     * debug_information, where an escaping Throwable takes out every admin
     * page, including the Settings page blocked customers are sent to.
     *
     * @var \Throwable|null
     */
    private $sourceError;

    public function __construct(?LicenceSource $source = null, ?Messages $messages = null, $clientModeProbe = null)
    {
        $this->source = $source ?: new ClientState();
        $this->messages = $messages ?: new Messages();
        $this->clientModeProbe = is_callable($clientModeProbe) ? $clientModeProbe : null;
    }

    /** @return self */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /** @return void */
    public static function reset()
    {
        self::$instance = null;
    }

    /** @return Messages */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * Every call into the licence source goes through here. The source reads
     * options through a filter any third-party plugin can hook, so on a throw
     * the gate answers with the value that REFUSES NOTHING: "cannot tell" is
     * not "unlicensed".
     *
     * @param  string  $method  a LicenceSource method taking no arguments
     * @param  mixed  $whenBroken  the answer that refuses nothing
     * @return mixed
     */
    private function ask($method, $whenBroken)
    {
        if ($this->sourceError !== null) {
            return $whenBroken;
        }

        try {
            return $this->source->$method();
        } catch (\Throwable $e) {
            $this->sourceError = $e;

            return $whenBroken;
        }
    }

    /** @return bool */
    public function sourceBroken()
    {
        return $this->sourceError !== null;
    }

    /** @return string  the class and message, never a key or any customer data */
    public function sourceErrorSummary()
    {
        if ($this->sourceError === null) {
            return '';
        }

        return get_class($this->sourceError) . ': ' . $this->sourceError->getMessage();
    }

    /** @return bool */
    public function isPaidEdition()
    {
        return defined(self::EDITION_CONSTANT) && constant(self::EDITION_CONSTANT) === self::PAID;
    }

    /**
     * Whether the licence source could read the state it owns. Asked outright
     * rather than caught: the gateway underneath already swallows Throwable and
     * returns defaults, so an unreadable store is otherwise indistinguishable
     * from a site that never bought anything.
     *
     * @return bool
     */
    public function sourceDeterminable()
    {
        return (bool) $this->ask('determinable', false);
    }

    /**
     * Whether the gate is in a position to refuse anything at all. True on a
     * licensed site too: it is about the machinery, not the answer.
     *
     * @return bool
     */
    public function enforcing()
    {
        return (bool) $this->ask('available', false)
            && $this->sourceDeterminable()
            && $this->isPaidEdition();
    }

    /** @return bool */
    public function blocks()
    {
        return $this->enforcing() && ! $this->ask('licensed', true);
    }

    /**
     * A user who holds CLIENT_MODE_CAP and NOT the admin capability. Such a
     * user reaches the Settings slug but PMXE_Admin_Settings::init() die()s on
     * them, so redirecting one there is a wall with no way out.
     *
     * @return bool
     */
    public function isClientMode()
    {
        if ($this->clientModeProbe !== null) {
            return (bool) call_user_func($this->clientModeProbe);
        }

        if (! function_exists('current_user_can') || ! class_exists('PMXE_Plugin')) {
            return false;
        }

        return current_user_can(\PMXE_Plugin::CLIENT_MODE_CAP)
            && ! current_user_can(\PMXE_Plugin::$capabilities);
    }

    /**
     * @param  string  $page    the already-lowercased, regex-matched slug
     * @param  string  $action  the dispatcher's $actionName, with its _action suffix
     * @return bool
     */
    public function blocksScreen($page, $action)
    {
        if (! $this->blocks()) {
            return false;
        }

        // Every slug, Settings included. See isClientMode().
        if ($this->isClientMode()) {
            return true;
        }

        if ($page === self::SETTINGS_PAGE) {
            return ! in_array((string) $action, self::SETTINGS_ACTIONS, true);
        }

        if (isset(self::SCREEN_ACTIONS[$page])) {
            return ! in_array((string) $action, self::SCREEN_ACTIONS[$page], true);
        }

        return true;
    }

    /**
     * @param  string  $page
     * @param  string  $action
     * @param  bool  $wantsJson
     * @return string  one of 'allow', 'forbidden', 'json', 'redirect'
     */
    public function screenRefusal($page, $action, $wantsJson = false)
    {
        if (! $this->blocksScreen($page, $action)) {
            return 'allow';
        }

        if ($this->isClientMode()) {
            return 'forbidden';
        }

        return $wantsJson ? 'json' : 'redirect';
    }

    /**
     * Route-level, because the umbrella dispatcher multiplexes must-gate and
     * must-stay-open routes behind one hook name.
     *
     * @param  string  $route  the raw $_GET['q']
     * @return bool
     */
    public function blocksApiRoute($route)
    {
        return $this->blocks() && ! in_array((string) $route, self::API_ROUTES, true);
    }

    /**
     * @param  string  $action  the cron dispatcher's $_GET['action']
     * @return bool
     */
    public function blocksCronAction($action)
    {
        $action = (string) $action;

        return $this->blocks()
            && ! in_array($action, self::CRON_OPEN, true)
            && ! in_array($action, self::CRON_PER_RECORD, true);
    }

    /** @return bool */
    public function blocksZapier()
    {
        return $this->blocks();
    }

    /**
     * The tokenised secure-download endpoints. Refusing them does not make the
     * files unreachable: default delivery is a redirect to a raw uploads URL.
     *
     * @return bool
     */
    public function blocksDownload()
    {
        return $this->blocks();
    }

    /**
     * A run already in progress finishes, which takes two facts about the
     * record: `exported` says the run ADVANCED, and a live flag says it has not
     * ended. Nothing resets `exported` when a run completes, so on the counter
     * alone every export that ever finished reads as permanently in flight.
     *
     * A Real-Time export is never a continuation. It calls execute() once per
     * created post and the counter accumulates across them, so $realTime skips
     * the in-progress branch entirely.
     *
     * @param  object  $record  a PMXE_Export_Record
     * @param  bool  $realTime  whether execute() was handed a $post_id
     * @return bool
     */
    public function blocksRun($record, $realTime = false)
    {
        $id = isset($record->id) ? (int) $record->id : 0;

        // Consulted before the licence source, not after: the interactive
        // runner asks once per chunk.
        if (! $realTime && $id !== 0 && isset($this->admitted[$id])) {
            return false;
        }

        if (! $this->blocks()) {
            return false;
        }

        if (! $realTime && $this->runInProgress($record)) {
            if ($id !== 0) {
                $this->admitted[$id] = true;
            }

            return false;
        }

        return true;
    }

    /**
     * The live flags a runner clears when its run ends. Read as a set: the
     * three runners do not agree on which one they use.
     *
     * @var string[]
     */
    const RUN_LIVE_FLAGS = ['executing', 'triggered', 'processing'];

    /**
     * Markers that say the run is over, whatever its live flags still say. Not
     * redundant with the live flags: a writer that clears only some of them
     * would otherwise leave a written-off run reading as in flight.
     *
     * @var string[]
     */
    const RUN_ENDED_FLAGS = ['canceled'];

    const RUN_PROGRESS_COLUMN = 'exported';

    /** Zero, not one: every path that starts a run writes the counter back to 0. */
    const RUN_PROGRESS_FLOOR = 0;

    /**
     * The columns that say WHEN the run last moved; the most recent decides.
     * Not `registered_on`, which on this table is stamped at completion and so
     * holds the moment the PREVIOUS run ended on a mid-flight export.
     *
     * @var string[]
     */
    const RUN_ACTIVITY_COLUMNS = ['last_activity'];

    /**
     * How long after its last chunk a run stays resumable. The counter and the
     * live flags are both one-way and a crash runs no cleanup, so without a
     * clock a run that died months ago stays resumable for ever.
     */
    const RUN_STALE_AFTER_SECONDS = 86400;

    /**
     * Advanced AND still live AND still moving. Any one of the three alone
     * admits something it should not.
     *
     * @param  object  $record
     * @return bool
     */
    public function runInProgress($record)
    {
        $counter = self::RUN_PROGRESS_COLUMN;

        if (! isset($record->$counter) || (int) $record->$counter <= self::RUN_PROGRESS_FLOOR) {
            return false;
        }

        if ($this->runEnded($record)) {
            return false;
        }

        foreach (self::RUN_LIVE_FLAGS as $flag) {
            if (isset($record->$flag) && (int) $record->$flag > 0) {
                return $this->advancedRecently($record);
            }
        }

        return false;
    }

    /** @return bool  whether a marker says the run is over, whatever its live flags still say */
    private function runEnded($record)
    {
        foreach (self::RUN_ENDED_FLAGS as $ended) {
            if (isset($record->$ended) && (int) $record->$ended > 0) {
                return true;
            }
        }

        return false;
    }

    const RUN_STOPPED_FLAG = 'canceled';

    const RUN_STOPPED_AT_COLUMN = 'canceled_on';

    /**
     * The key under the record's `options` that says a stop was the gate's
     * doing rather than a customer clicking Cancel. It holds the
     * RUN_STOPPED_AT_COLUMN value written beside it, so any later writer of
     * that column invalidates it without having to know it exists.
     */
    const RUN_STOPPED_MARKER = 'licence_stopped_on';

    /**
     * @param  object  $record
     * @return bool
     */
    public function runAdvanced($record)
    {
        $counter = self::RUN_PROGRESS_COLUMN;

        return isset($record->$counter)
            && (int) $record->$counter > self::RUN_PROGRESS_FLOOR;
    }

    /**
     * Whether refusing this run leaves a row that would otherwise read as a run
     * still in flight. A run refused on its way in leaves no residue, and
     * neither does one that already ended: `exported` keeps a finished run's
     * count, so the counter alone cannot say a run is still going.
     *
     * @param  object  $record
     * @return bool
     */
    public function stopsRunInFlight($record)
    {
        if ($this->runEnded($record)) {
            return false;
        }

        foreach (self::RUN_LIVE_FLAGS as $flag) {
            if (isset($record->$flag) && (int) $record->$flag > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * The row a run the gate stopped must be left holding. RUN_PROGRESS_COLUMN
     * is deliberately absent: the counter is what lets a renewed customer
     * continue.
     *
     * @param  string  $stoppedAt
     * @return array<string,mixed>
     */
    public function runStopFields($stoppedAt)
    {
        $fields = [];

        foreach (self::RUN_LIVE_FLAGS as $flag) {
            $fields[$flag] = 0;
        }

        $fields[self::RUN_STOPPED_FLAG] = 1;
        $fields[self::RUN_STOPPED_AT_COLUMN] = (string) $stoppedAt;

        return $fields;
    }

    /**
     * Leave a run this gate refused in a state that says what happened to it.
     *
     * @param  object  $record
     * @return bool  whether there was anything to write
     */
    public function stopRun($record)
    {
        if (! $this->stopsRunInFlight($record) || $this->alreadyStopped($record)) {
            return false;
        }

        $stoppedAt = date('Y-m-d H:i:s');

        $options = isset($record->options) && is_array($record->options) ? $record->options : [];
        $options[self::RUN_STOPPED_MARKER] = $stoppedAt;

        $fields = $this->runStopFields($stoppedAt);
        $fields['options'] = $options;

        $record->set($fields)->update();

        return true;
    }

    /**
     * Whether this row already carries the stop. Without it the timestamp would
     * advance on every cron tick, since the progress column is one-way and
     * stopsRunInFlight() stays true for ever afterwards.
     *
     * @param  object  $record
     * @return bool
     */
    private function alreadyStopped($record)
    {
        $flag = self::RUN_STOPPED_FLAG;
        $clock = self::RUN_STOPPED_AT_COLUMN;

        if (! isset($record->$flag) || (int) $record->$flag <= 0) {
            return false;
        }

        return self::stoppedThisRun(
            isset($record->$clock) ? $record->$clock : '',
            isset($record->options) ? $record->options : null
        );
    }

    /**
     * Static and argument-only so a screen can ask about a row out of a list
     * query, without a record object and without consulting the licence source.
     *
     * @param  mixed  $stoppedAt  the row's RUN_STOPPED_AT_COLUMN
     * @param  mixed  $options    the row's unserialised options
     * @return bool
     */
    public static function stoppedThisRun($stoppedAt, $options)
    {
        $stoppedAt = (string) $stoppedAt;

        if ($stoppedAt === '' || ! is_array($options)) {
            return false;
        }

        return isset($options[self::RUN_STOPPED_MARKER])
            && (string) $options[self::RUN_STOPPED_MARKER] === $stoppedAt;
    }

    /**
     * Whether the run moved recently enough to still be called live. The NEWEST
     * clock decides, and an unreadable one admits: `0000-00-00 00:00:00` is the
     * column's schema default and strtotime() parses it rather than rejecting
     * it, to two millennia before the epoch.
     *
     * @param  object  $record
     * @return bool
     */
    private function advancedRecently($record)
    {
        $newest = null;

        foreach (self::RUN_ACTIVITY_COLUMNS as $clock) {
            if (! isset($record->$clock)) {
                return true;
            }

            $stampedAt = strtotime((string) $record->$clock);

            if (! is_int($stampedAt) || $stampedAt <= 0) {
                return true;
            }

            if ($newest === null || $stampedAt > $newest) {
                $newest = $stampedAt;
            }
        }

        // Null rather than zero: seeded at zero, a stamp that parsed negative
        // would be silently discarded by the comparison above.
        if ($newest === null) {
            return true;
        }

        return (time() - $newest) <= self::RUN_STALE_AFTER_SECONDS;
    }

    /**
     * @param  object  $record
     * @return void
     */
    public function admit($record)
    {
        $id = isset($record->id) ? (int) $record->id : 0;

        if ($id !== 0) {
            $this->admitted[$id] = true;
        }
    }

    const REFUSAL_CODE = 'license_required';

    /**
     * Call after stopRun(), which leaves `exported` alone so a run that wrote
     * rows is still described as one.
     *
     * @param  object  $record
     * @return string
     */
    public function stopRefusal($record)
    {
        return $this->runAdvanced($record)
            && ($this->stopsRunInFlight($record) || $this->alreadyStopped($record))
            ? $this->executionStopped()
            : $this->apiRefusal();
    }

    /**
     * The refusal every transport sends, in the state this site is actually in.
     *
     * Here rather than at the call sites so no surface has to go and read the
     * state for itself, and none can answer in a different one.
     *
     * @return string
     */
    public function ajaxRefusal()
    {
        return $this->messages->ajaxRefusal($this->state(), $this->stateReason(), $this->graceUntil());
    }

    /** @return string */
    public function apiRefusal()
    {
        return $this->messages->apiRefusal($this->state(), $this->stateReason(), $this->graceUntil());
    }

    /** @return string */
    public function executionStopped()
    {
        return $this->messages->executionStopped($this->state(), $this->stateReason(), $this->graceUntil());
    }

    const STATE_VALID = 'valid';

    /** @return bool */
    public function stateNameable()
    {
        return $this->source instanceof LicenceStateSource;
    }

    /**
     * The named state, or the fail-open `valid` when the source cannot answer.
     *
     * @return string
     */
    public function state()
    {
        if (! $this->stateNameable()) {
            return self::STATE_VALID;
        }

        return (string) $this->ask('state', self::STATE_VALID);
    }

    /** @return string */
    public function stateReason()
    {
        if (! $this->stateNameable()) {
            return '';
        }

        return (string) $this->ask('stateReason', '');
    }

    /** @return int */
    public function graceUntil()
    {
        if (! $this->stateNameable()) {
            return 0;
        }

        return (int) $this->ask('graceUntil', 0);
    }

    /**
     * Whether the site should be told its licence is not fully valid.
     *
     * Deliberately NOT blocks(): blocks() is false for the whole of a grace
     * period, so a notice keyed on it would say nothing for thirty days and
     * then the site would stop with no warning at all.
     *
     * @return bool
     */
    public function shouldNotice()
    {
        return $this->enforcing()
            && $this->state() !== self::STATE_VALID
            && $this->ask('recordsExist', false)
            && ! $this->awaitingFirstAnswer();
    }

    /**
     * Optional on the source, so a source that cannot say keeps every notice.
     *
     * @return bool
     */
    private function awaitingFirstAnswer()
    {
        return method_exists($this->source, 'awaitingFirstAnswer')
            && (bool) $this->ask('awaitingFirstAnswer', false);
    }

    /** @return string */
    public function stateMessage()
    {
        return $this->messages->forState($this->state(), $this->stateReason(), $this->graceUntil());
    }

    /** @return string */
    public function status()
    {
        return (string) $this->ask('status', '');
    }

    /**
     * @return array<string,mixed>
     */
    public function describe()
    {
        return [
            'source' => $this->ask('name', 'unavailable'),
            'source_available' => $this->ask('available', false),
            'source_determinable' => $this->sourceDeterminable(),
            'edition' => defined(self::EDITION_CONSTANT) ? constant(self::EDITION_CONSTANT) : null,
            'edition_paid' => $this->isPaidEdition(),
            'enforcing' => $this->enforcing(),
            'blocking' => $this->blocks(),
            'blocked_reason' => $this->blockedReason(),
            'state' => $this->state(),
            'state_reason' => $this->stateReason(),
            'grace_until' => $this->graceUntil(),
            'should_notice' => $this->shouldNotice(),
            'source_error' => $this->sourceErrorSummary(),
        ];
    }

    /** @return string */
    public function blockedReason()
    {
        $available = $this->ask('available', false);

        // Checked after each call, never before: the throw happens DURING the
        // call, and a source that broke while being asked reads as absent.
        if ($this->sourceError !== null) {
            return 'source-error';
        }

        if (! $available) {
            return 'no-licence-source';
        }

        // Before the entitlement question, never after: an unreadable source
        // is not a fact about the customer's entitlement.
        if (! $this->sourceDeterminable()) {
            return 'source-indeterminate';
        }

        if ($this->sourceError !== null) {
            return 'source-error';
        }

        if (! $this->isPaidEdition()) {
            return 'not-paid-edition';
        }

        $licensed = $this->ask('licensed', true);

        if ($this->sourceError !== null) {
            return 'source-error';
        }

        return $licensed ? 'licensed' : 'unlicensed';
    }
}
