<?php

namespace Wpae\App\Gate;

/** Nothing here calls remove_action: free and Pro register the same hook and callback names. */
final class Surfaces
{
    /** Open while blocked: warnings must stay dismissable, and the licence responder is the way out. */
    const ALLOW = [
        'wp_ajax_dismiss_export_warnings',
        'wp_ajax_dismiss_review_modal',
        'wp_ajax_dismiss_warnings',
        'wp_ajax_send_feedback',
        'wp_ajax_soflyy_updater_dismiss_refusal',
        'wp_ajax_wpae_dismiss_ad',
        'wp_ajax_wpae_settings_check_license',
    ];

    /** Gated inside the handler: the runner is chunked, and a hook-level refusal would abandon a run mid-flight. */
    const GATED_IN_HANDLER = [
        'wp_ajax_wpallexport',
    ];

    /** Ajax hooks registered outside actions/, so the glob does not see them. */
    const EXTRA_HOOKS = [
        'wp_ajax_wpae_dismiss_ad',
    ];

    /** The umbrella dispatcher, gated per route rather than per hook. */
    const UMBRELLA = 'wp_ajax_wpae_api';

    /** @var Gate */
    private $gate;

    /** @var string */
    private $actionsDir;

    public function __construct(?Gate $gate = null, $actionsDir = null)
    {
        $this->gate = $gate ?: Gate::instance();
        $this->actionsDir = $actionsDir !== null
            ? $actionsDir
            : (defined('PMXE_ROOT_DIR') ? PMXE_ROOT_DIR . '/actions' : '');
    }

    /** @return string[] */
    public function gatedAjaxHooks()
    {
        $hooks = [];

        if ($this->actionsDir !== '' && is_dir($this->actionsDir)) {
            foreach ((array) glob($this->actionsDir . '/wp_ajax_*.php') as $path) {
                $hooks[] = basename($path, '.php');
            }
        }

        $hooks = array_merge($hooks, self::EXTRA_HOOKS);
        $hooks = array_unique($hooks);

        $open = array_merge(
            self::ALLOW,
            self::GATED_IN_HANDLER,
            [self::UMBRELLA]
        );

        $gated = array_values(array_diff($hooks, $open));
        sort($gated);

        return $gated;
    }

    /** @return void */
    public function register()
    {
        foreach ($this->gatedAjaxHooks() as $hook) {
            add_action($hook, [$this, 'refuseAjax'], 1);
        }

        add_action(self::UMBRELLA, [$this, 'refuseApiRoute'], 1);

        add_action('all_admin_notices', [$this, 'renderNotice']);
        add_filter('debug_information', [$this, 'siteHealth']);
    }

    /** @param callable $body  Nothing it raises may escape: all_admin_notices fires on every admin page, so a Throwable is a white screen. */
    private function guarded($body, $passthrough = null)
    {
        try {
            return call_user_func($body);
        } catch (\Throwable $e) {
            return $passthrough;
        }
    }

    /** @return void  Bound at priority 1, in front of the priority-10 handlers in actions/. */
    public function refuseAjax()
    {
        $this->guarded(function () {
            if (! $this->gate->blocks()) {
                return;
            }

            $this->sendAjaxRefusal();
        });
    }

    /** @return void */
    public function refuseApiRoute()
    {
        $this->guarded(function () {
            $route = isset($_GET['q']) ? (string) wp_unslash($_GET['q']) : '';

            if (! $this->gate->blocksApiRoute($route)) {
                return;
            }

            $this->sendAjaxRefusal();
        });
    }

    /** @return void  403 not the 401 used elsewhere: authenticated but unauthorised. `html` duplicates `message` for the umbrella JS. */
    private function sendAjaxRefusal()
    {
        $message = $this->gate->ajaxRefusal();

        $payload = ['status' => 403, 'message' => $message, 'html' => $message];

        if (function_exists('pmxe_send_json')) {
            pmxe_send_json($payload, 403);

            return;
        }

        wp_send_json($payload, 403);
    }

    /** @return void  Only where the plugin has records: a site with no exports has had nothing taken away. */
    public function renderNotice()
    {
        $this->guarded(function () {
            if (is_network_admin() || $this->onSettingsScreen() || ! $this->gate->shouldNotice()) {
                return;
            }

            echo '<div class="notice notice-error wpae-license-gate-notice"><p>'
                . wp_kses_post($this->gate->stateMessage())
                . '</p></div>';
        });
    }

    /** @return bool  The Settings screen states the position itself; a notice there would repeat it. */
    private function onSettingsScreen()
    {
        return isset($_GET['page']) && (string) $_GET['page'] === Gate::SETTINGS_PAGE;
    }

    /**
     * @param  array<string,mixed>  $info
     * @return array<string,mixed>
     */
    public function siteHealth($info)
    {
        return $this->guarded(function () use ($info) {
            return $this->siteHealthFields($info);
        }, $info);
    }

    /**
     * @param  array<string,mixed>  $info
     * @return array<string,mixed>
     */
    private function siteHealthFields($info)
    {
        if (! is_array($info)) {
            return $info;
        }

        $described = $this->gate->describe();

        $info['wp-all-export-license-gate'] = [
            'label' => __('WP All Export: license gate', 'wp_all_export_plugin'),
            'fields' => [
                'enforcing' => [
                    'label' => 'Enforcing',
                    'value' => $described['enforcing'] ? 'yes' : 'no',
                ],
                'reason' => [
                    'label' => 'Reason',
                    'value' => $described['blocked_reason'],
                ],
                'licence_source' => [
                    'label' => 'Licence source',
                    'value' => $described['source'] . ($described['source_available'] ? ' (available)' : ' (absent)'),
                ],
                'edition' => [
                    'label' => 'Edition',
                    'value' => (string) $described['edition'],
                ],
                'source_determinable' => [
                    'label' => 'Licence state determinable',
                    'value' => $described['source_determinable'] ? 'yes' : 'no',
                ],
                'source_error' => [
                    'label' => 'Licence source error',
                    'value' => $described['source_error'] === '' ? 'none' : $described['source_error'],
                ],
            ],
        ];

        return $info;
    }
}
