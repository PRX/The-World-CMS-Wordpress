<?php

namespace Wpae\App\Gate;

/**
 * Customer-facing gate copy. No message says "renew" or "lapsed" unless the state is
 * one the plugin can tell is an expiry. Many sites refused here never held a licence.
 */
final class Messages
{
    /**
     * Kept for callers outside this class. The __() calls below pass the
     * literal instead: wp-cli's make-pot parses statically and cannot
     * resolve a constant, so every string here was invisible to it.
     */
    const TEXT_DOMAIN = 'wp_all_export_plugin';

    const PRICING_URL = 'https://www.wpallimport.com/order-now/';

    const PORTAL_URL = 'https://www.wpallimport.com/portal/';

    /** @return string */
    public function settingsUrl()
    {
        return admin_url('admin.php?page=' . Gate::SETTINGS_PAGE);
    }

    /** The state vocabulary, as this class branches on it. */
    const STATE_VALID = 'valid';

    const STATE_NO_KEY = 'no_key';

    const STATE_GRACE = 'grace';

    const STATE_BLOCKED_UNVERIFIED = 'blocked_unverified';

    const STATE_BLOCKED_INVALID = 'blocked_invalid';

    const STATE_BLOCKED_EXPIRED = 'blocked_expired';

    const STATE_BLOCKED_LIMIT = 'blocked_limit';

    const REASON_UNVERIFIED = 'unverified';

    const REASON_EXPIRED = 'expired';

    const REASON_LIMIT = 'limit';

    /** @return string  An unrecognised state falls back to the no-key wording, which is true either way. */
    public function forState($state, $reason = '', $graceUntil = 0)
    {
        return $this->stateBody((string) $state, (string) $reason, (int) $graceUntil)
            . $this->capabilitySuffix();
    }

    /** @return string */
    private function stateBody($state, $reason, $graceUntil)
    {
        switch ($state) {
            case self::STATE_GRACE:
                return $this->graceBody($reason, $graceUntil);

            case self::STATE_BLOCKED_EXPIRED:
                return $this->expiredBody();

            case self::STATE_BLOCKED_LIMIT:
                return $this->limitBody();

            case self::STATE_BLOCKED_UNVERIFIED:
                return $this->unverifiedBody();

            case self::STATE_BLOCKED_INVALID:
                return $this->invalidBody();

            case self::STATE_NO_KEY:
            default:
                return $this->noKeyBody();
        }
    }

    /** @return string  '' when the deadline is absent or already elapsed, so there is nothing to act on. */
    private function deadline($graceUntil)
    {
        $graceUntil = (int) $graceUntil;

        if ($graceUntil <= $this->now()) {
            return '';
        }

        return (string) date_i18n((string) get_option('date_format'), $graceUntil);
    }

    /** @return int  protected so the clock can be pinned. */
    protected function now()
    {
        return time();
    }

    /** @return string  No renewal language: no prior purchase can be assumed. */
    private function noKeyBody()
    {
        /* translators: %1$s: URL of the plugin's Settings page. %2$s: pricing page URL. %3$s: customer portal URL. */
        return sprintf(
            __(
                '<strong>WP All Export Pro requires a license key.</strong> Exports will not run until a license key is entered on the <a href="%1$s">Settings</a> page. No key? See <a href="%2$s" target="_blank">details &amp; pricing</a>. Already a customer? Get your key from the <a href="%3$s" target="_blank">customer portal</a>.',
                'wp_all_export_plugin'
            ),
            esc_url($this->settingsUrl()),
            esc_url(self::PRICING_URL),
            esc_url(self::PORTAL_URL)
        );
    }

    /** @return string */
    public function notice()
    {
        return $this->noKeyBody() . $this->capabilitySuffix();
    }

    /** @return string */
    private function invalidBody()
    {
        /* translators: %1$s: URL of the plugin's Settings page. %2$s: customer portal URL. */
        return sprintf(
            __(
                '<strong>The license key entered for WP All Export Pro is not valid.</strong> Exports will not run until a valid key is saved on the <a href="%1$s">Settings</a> page. Your key is in the <a href="%2$s" target="_blank">customer portal</a>.',
                'wp_all_export_plugin'
            ),
            esc_url($this->settingsUrl()),
            esc_url(self::PORTAL_URL)
        );
    }

    /** @return string  A manual Save is what applies a renewal immediately, so the copy asks for one. */
    private function expiredBody()
    {
        /* translators: %1$s: customer portal URL. %2$s: URL of the plugin's Settings page. */
        return sprintf(
            __(
                '<strong>Your WP All Export Pro license has expired.</strong> Exports will not run until it is renewed. Renew in the <a href="%1$s" target="_blank">customer portal</a>, then save your key again on the <a href="%2$s">Settings</a> page (or wait up to a day).',
                'wp_all_export_plugin'
            ),
            esc_url(self::PORTAL_URL),
            esc_url($this->settingsUrl())
        );
    }

    /** @return string  Nothing is wrong with the key here, so it must not ask anyone to re-check one. */
    private function limitBody()
    {
        /* translators: %s: customer portal URL. */
        return sprintf(
            __(
                '<strong>Your WP All Export Pro license has reached its site limit.</strong> Exports will not run on this site until a site is freed up. Manage your sites in the <a href="%s" target="_blank">customer portal</a>.',
                'wp_all_export_plugin'
            ),
            esc_url(self::PORTAL_URL)
        );
    }

    /** @return string  Never confirmed, not rejected: an outage or a firewall is as likely as a bad key. */
    private function unverifiedBody()
    {
        /* translators: %s: URL of the plugin's Settings page. */
        return sprintf(
            __(
                '<strong>WP All Export Pro could not verify your license key.</strong> The licensing server has not confirmed it in 30 days. Check the key on the <a href="%s">Settings</a> page and save it again.',
                'wp_all_export_plugin'
            ),
            esc_url($this->settingsUrl())
        );
    }

    /** @return string  The site still works: nothing here may imply it has stopped. */
    private function graceBody($reason, $graceUntil)
    {
        $date = $this->deadline($graceUntil);

        if ($date === '') {
            return $this->undatedGraceBody();
        }

        switch ($reason) {
            case self::REASON_EXPIRED:
                return sprintf(
                    /* translators: %1$s: date the license grace period ends. %2$s: customer portal URL. %3$s: URL of the plugin's Settings page. */
                    __(
                        '<strong>Your WP All Export Pro license has expired.</strong> Exports will stop working on %1$s. Renew in the <a href="%2$s" target="_blank">customer portal</a>, then save your key again on the <a href="%3$s">Settings</a> page.',
                        'wp_all_export_plugin'
                    ),
                    esc_html($date),
                    esc_url(self::PORTAL_URL),
                    esc_url($this->settingsUrl())
                );

            case self::REASON_UNVERIFIED:
                return sprintf(
                    /* translators: %1$s: date the license grace period ends. %2$s: URL of the plugin's Settings page. */
                    __(
                        '<strong>WP All Export Pro could not verify your license key yet.</strong> Exports keep working until %1$s while we retry daily. If this persists, save your key again on the <a href="%2$s">Settings</a> page.',
                        'wp_all_export_plugin'
                    ),
                    esc_html($date),
                    esc_url($this->settingsUrl())
                );

            default:
                return sprintf(
                    /* translators: %1$s: date the license grace period ends. %2$s: URL of the plugin's Settings page. %3$s: customer portal URL. */
                    __(
                        '<strong>Your WP All Export Pro license is no longer valid.</strong> Exports will stop working on %1$s. Check the key on the <a href="%2$s">Settings</a> page or get it from the <a href="%3$s" target="_blank">customer portal</a>.',
                        'wp_all_export_plugin'
                    ),
                    esc_html($date),
                    esc_url($this->settingsUrl()),
                    esc_url(self::PORTAL_URL)
                );
        }
    }

    /**
     * The one grace sentence carrying no date.
     *
     * The state machine names grace only while the deadline is ahead, so this
     * is reached within a single request: the deadline elapses between the
     * state being resolved and the message being rendered.
     *
     * @return string
     */
    private function undatedGraceBody()
    {
        /* translators: %1$s: URL of the plugin's Settings page. %2$s: customer portal URL. */
        return sprintf(
            __(
                '<strong>Your WP All Export Pro license is no longer valid.</strong> Exports will stop working. Check the key on the <a href="%1$s">Settings</a> page or get it from the <a href="%2$s" target="_blank">customer portal</a>.',
                'wp_all_export_plugin'
            ),
            esc_url($this->settingsUrl()),
            esc_url(self::PORTAL_URL)
        );
    }

    /** @return string  Never appended to the client-mode 403, which already says this. */
    public function capabilitySuffix()
    {
        if (! function_exists('current_user_can') || ! class_exists('PMXE_Plugin')) {
            return '';
        }

        if (current_user_can(\PMXE_Plugin::$capabilities)) {
            return '';
        }

        return ' ' . __(
            'If you cannot open Settings, ask a site administrator.',
            'wp_all_export_plugin'
        );
    }

    /** @return string  Shown on Settings after a blocked screen redirected here; "below" points at the licence form. */
    public function redirected()
    {
        return __(
            'That page isn\'t available until WP All Export Pro is licensed. Enter your license key below and you\'ll be able to open it again.',
            'wp_all_export_plugin'
        );
    }

    /** @return string  The Settings box, for any state that is not fully valid, grace included. */
    public function settingsNotice($state, $reason = '', $graceUntil = 0, $blocked = true)
    {
        $body = $this->stateBody((string) $state, (string) $reason, (int) $graceUntil);

        if ($blocked) {
            $body .= ' ' . $this->blockedPageAddendum();
        }

        return $body . $this->capabilitySuffix();
    }

    /**
     * The Licenses chip's one sentence about what stops and when.
     *
     * Silent for any state it is not sure of: the chip also renders for keys
     * this gate does not gate, and a false claim that exports are stopping is
     * worse than none.
     *
     * @return string
     */
    public function chipConsequence($state, $graceUntil = 0)
    {
        $state = (string) $state;

        if ($state === self::STATE_GRACE) {
            $date = $this->deadline($graceUntil);

            if ($date === '') {
                return __('Exports will stop working when the grace period ends.', 'wp_all_export_plugin');
            }

            /* translators: %s: date the license grace period ends. */
            return sprintf(__('Exports will stop working on %s.', 'wp_all_export_plugin'), esc_html($date));
        }

        switch ($state) {
            case self::STATE_NO_KEY:
            case self::STATE_BLOCKED_UNVERIFIED:
            case self::STATE_BLOCKED_INVALID:
            case self::STATE_BLOCKED_EXPIRED:
            case self::STATE_BLOCKED_LIMIT:
                return __('Exports aren\'t running on this site.', 'wp_all_export_plugin');
        }

        return '';
    }

    /** @return string  The only explanation the emptied page gets, so it does not read as deletion. */
    private function blockedPageAddendum()
    {
        return __(
            'The rest of this page is hidden until a valid license key is entered. Your exports, templates and settings have not been changed and nothing has been deleted.',
            'wp_all_export_plugin'
        );
    }

    /**
     * The same message, with the markup taken off.
     *
     * Entities are decoded as well as tags stripped. wp_strip_all_tags() alone
     * leaves `details &amp; pricing`, which is not a sentence anyone wrote. The
     * escaping is part of the HTML rendering, not part of the words.
     *
     * @return string
     */
    private function plain($html)
    {
        return html_entity_decode(wp_strip_all_tags((string) $html), ENT_QUOTES, 'UTF-8');
    }

    /**
     * The refusal a reader with a screen gets: the state message, as text.
     *
     * Not a second set of sentences. The same string forState() renders into a
     * notice, so no transport can drift away from what the page says.
     *
     * @return string
     */
    public function ajaxRefusal($state, $reason = '', $graceUntil = 0)
    {
        return $this->plain($this->forState($state, $reason, $graceUntil));
    }

    /** @return string  No capability suffix: machine transports have no current user to advise. */
    public function apiRefusal($state, $reason = '', $graceUntil = 0)
    {
        return $this->plain(
            $this->stateBody((string) $state, (string) $reason, (int) $graceUntil)
        );
    }

    /** @return string  No portal or pricing link: this reader has no account and cannot act on one. */
    public function clientModeForbidden()
    {
        return __(
            '<strong>WP All Export Pro isn\'t licensed on this site.</strong><br>Exports can\'t be run until a site administrator enters a license key. Please contact whoever manages this site; they\'ll be able to sort it out from the WP All Export settings.',
            'wp_all_export_plugin'
        );
    }

    /** @return string The title bar of the client-mode 403 page. */
    public function clientModeForbiddenTitle()
    {
        return __('License required', 'wp_all_export_plugin');
    }

    /** @return string  A log line, not UI: the refusal it belongs to has no screen to render on. */
    public function executionLogLine()
    {
        return __(
            'Export skipped: WP All Export Pro isn\'t licensed on this site.',
            'wp_all_export_plugin'
        );
    }

    /**
     * The state message first, then why "will not run" is not enough: a stopped
     * run left a partial file. Must not promise resumption: every path back into
     * a run restarts it.
     *
     * @return string
     */
    public function executionStopped($state, $reason = '', $graceUntil = 0)
    {
        return $this->apiRefusal($state, $reason, $graceUntil)
            . ' '
            . __(
                'This export stopped part-way, so the file it had started writing is incomplete. Nothing was deleted. Once the license is valid, run the export again to get a complete file.',
                'wp_all_export_plugin'
            );
    }

    /** @return string  Its own wording because the column sits beside "Export canceled". */
    public function executionStoppedSummary()
    {
        return __('Export stopped, license required', 'wp_all_export_plugin');
    }
}
