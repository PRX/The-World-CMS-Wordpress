<?php

namespace Soflyy\WpAllExportPro\Vendor\Soflyy\Updater;

use Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Api\Client;
use Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Api\QueryStringTransport;
use Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Api\RequestParameters;
use Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Cache\LayeredCache;
use Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Cache\OptionCache;
use Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Cache\TransientCache;
use Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Cache\Unresolved;
use Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Cache\VersionInfoCache;

/**
 * Update client for a single plugin.
 *
 * A subclass supplies the text domain, the product name and the settings-page
 * URL. Everything else — hooks, caching, the ping and the update rows — lives
 * here.
 *
 * Licensing is decided by whether the constructor data has a 'license' key at
 * all, not by whether that key holds a value.
 */
abstract class Updater
{
    /** Seconds a lookup that produced no version record is remembered for. */
    const UNRESOLVED_TTL = 3600;

    /** @var string */
    protected $apiUrl;

    /** @var string Absolute path to the plugin's main file. */
    protected $pluginFile;

    /** @var string plugin_basename() of the main file. */
    protected $name;

    /** @var string */
    protected $slug;

    /** @var string */
    protected $version;

    /** @var string */
    protected $phpVersion;

    /** @var array<string,mixed> */
    protected $apiData;

    /** @var bool */
    protected $licenseAware;

    /**
     * The one `wp_ajax_` action every product's dismissal posts to.
     *
     * Spelled out in full at the add_action() below, not concatenated from
     * this constant: a consumer's gate enumerates the hooks its tree binds by
     * reading string literals out of the source, and a name it has to evaluate
     * to learn is a hook it cannot rule on. The two are held together by
     * RefusalDismissalTest.
     */
    const REFUSAL_DISMISSAL_ACTION = 'soflyy_updater_dismiss_refusal';

    /**
     * The shortest interval between two calls to the service for one product.
     *
     * A floor, not a cache TTL: the record's own 24 hours is what spaces out a
     * healthy site's checks, and this is what a site whose stores keep nothing
     * is held to instead of asking on every render. Short enough that a person
     * pressing "Check again" twice does not notice it, long enough that a site
     * in that state costs 60 requests a day per product rather than 2,500.
     */
    const MINIMUM_CALL_INTERVAL = 60;

    /** @var string */
    protected $callStampKey;

    /**
     * How long the answer already resolved is reused for: the second it was
     * resolved in, and no longer.
     *
     * An updater lives for one request in WordPress, but nothing stops a
     * caller holding one while the clock moves, and reusing an answer past the
     * record's own expiry would be a stale offer rather than a saved call.
     * Every duplicate this exists to remove lands inside one second in
     * production — up to four for the same product.
     */
    const RESOLVED_WINDOW = 1;

    /**
     * The record this render resolved, false when the lookup produced none,
     * and null before either has happened.
     *
     * checkUpdate() and showUpdateNotification() both ask, and on a site whose
     * cache keeps nothing each of them was a separate HTTP request inside one
     * render — measured in production as up to four for one product in the
     * same second.
     *
     * @var object|false|null
     */
    private $resolved;

    /** @var int|null */
    private $resolvedAt;

    /**
     * The stamp row for this request. Read once: the floor, the diagnostic and
     * the verdict all consult it, and on a warm render none of them run at all.
     *
     * @var array<string,mixed>|null
     */
    private $stamp;

    /** @var string */
    protected $cacheKey;

    /** @var Client */
    protected $client;

    /** @var VersionInfoCache */
    protected $cache;

    /**
     * @param  string  $apiUrl  Base URL of the update service.
     * @param  string  $pluginFile  Absolute path to the plugin's main file.
     * @param  array<string,mixed>  $apiData  version, author, item_name, optional item_id,
     *                                        optional slug, and 'license' only for licensed products.
     */
    public function __construct($apiUrl, $pluginFile, array $apiData = [], ?Client $client = null, ?VersionInfoCache $cache = null)
    {
        $this->apiUrl = trailingslashit($apiUrl);
        $this->pluginFile = (string) $pluginFile;
        $this->apiData = $apiData;
        $this->name = plugin_basename($this->pluginFile);
        $this->version = isset($apiData['version']) ? (string) $apiData['version'] : '';
        $this->phpVersion = phpversion();
        $this->licenseAware = array_key_exists('license', $apiData);
        $this->slug = isset($apiData['slug']) && '' !== $apiData['slug']
            ? (string) $apiData['slug']
            : $this->deriveSlug();
        $this->cacheKey = md5('edd_plugin_' . sanitize_key($this->name) . '_version_info');
        $this->callStampKey = 'soflyy_updater_asked_' . $this->cacheKey;
        $this->cache = $cache ?: new LayeredCache([new TransientCache(), new OptionCache()]);
        $this->client = $client ?: new Client(
            $this->apiUrl,
            $this->slug,
            $apiData,
            new RequestParameters(
                $this->slug,
                $this->pluginFile,
                $this->version,
                $this->phpVersion
            ),
            $this->transport()
        );

        // Resolved here, not on first use: a failed update can delete this
        // package's files before versionInfo() touches the class. Do not remove.
        class_exists(Unresolved::class);

        $this->init();
        add_action('admin_init', [$this, 'showChangelog']);
    }

    // ---------------------------------------------------------------- subclass

    /**
     * @return string Text domain the notices are translated against.
     */
    abstract protected function textDomain();

    /**
     * @return string Customer-facing product name, e.g. "WP All Import Pro".
     */
    abstract protected function productName();

    /**
     * @return string Admin URL of the page carrying the licence form.
     */
    abstract protected function settingsUrl();

    /**
     * Extra parameters to append to the ping. Empty on purpose: the service
     * discards anything outside php_version, rollout_bucket and
     * active_schedules.
     *
     * @return array<string,mixed>
     */
    protected function additionalRequestParameters()
    {
        return [];
    }

    /**
     * Every product GETs `<base>/check_version/`, whatever base URL it holds.
     *
     * @return \Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Api\Transport
     */
    protected function transport()
    {
        return new QueryStringTransport();
    }

    protected function deriveSlug()
    {
        return basename($this->pluginFile, '.php');
    }

    protected function updateTransientPriority()
    {
        return 20;
    }

    protected function pluginRowPriority()
    {
        return 10;
    }

    protected function hidesAutoUpdateSetting()
    {
        return false;
    }

    /**
     * Whether this package draws the update row instead of WordPress's own, as
     * it must on multisite subsites, where core shows none.
     *
     * False leaves WordPress to report a PHP refusal, which needs a record in
     * the update_plugins transient — see phpRefusalNeedsWordPressToReportIt().
     */
    protected function rendersUpdateRow()
    {
        return $this->licenseAware || is_multisite();
    }

    /**
     * Screens the refusal notice is allowed onto, by $pagenow. The plugins
     * screen is not one: there the refusal is the product's own update row.
     *
     * @return array<int,string>
     */
    protected function refusalNoticeScreens()
    {
        return ['update-core.php'];
    }

    protected function pricingUrl()
    {
        return 'https://www.wpallimport.com/order-now/';
    }

    protected function portalUrl()
    {
        return 'https://www.wpallimport.com/portal/';
    }

    // ------------------------------------------------------------------- hooks

    public function init()
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate'], $this->updateTransientPriority());
        add_filter('plugins_api', [$this, 'pluginsApiFilter'], 10, 3);
        add_action('after_plugin_row_' . $this->name, [$this, 'showUpdateNotification'], $this->pluginRowPriority(), 2);
        add_filter('plugin_row_meta', [$this, 'pluginRowMeta'], 10, 2);
        add_action('in_plugin_update_message-' . $this->name, [$this, 'customUpdateNote'], 10, 2);
        add_action('all_admin_notices', [$this, 'showRefusalNotice']);
        add_action('wp_ajax_soflyy_updater_dismiss_refusal', [$this, 'dismissRefusalNotice']);

        if ($this->hidesAutoUpdateSetting()) {
            add_filter('plugin_auto_update_setting_html', [$this, 'pluginAutoUpdateSettingHtml'], 10, 3);
        }
    }

    public function isLicenseAware()
    {
        return $this->licenseAware;
    }

    public function cacheKey()
    {
        return $this->cacheKey;
    }

    public function clearCache()
    {
        $this->resolved = null;
        $this->resolvedAt = null;
        $this->stamp = null;
        delete_option($this->callStampKey);
        $this->cache->forget($this->cacheKey);
    }

    /**
     * The version record, or false when the last lookup produced none.
     *
     * A failed lookup is cached too, under a much shorter TTL: this runs on
     * every plugins.php render, so an uncached failure is one HTTP request per
     * render for as long as the condition lasts.
     *
     * @return object|false
     */
    public function versionInfo()
    {
        if (null !== $this->resolved && (time() - (int) $this->resolvedAt) < self::RESOLVED_WINDOW) {
            return $this->resolved;
        }

        $cached = $this->cache->get($this->cacheKey);

        if (Unresolved::is($cached)) {
            return $this->remember(false);
        }

        if (null !== $cached) {
            return $this->remember($cached);
        }

        if ($this->askedTooRecently()) {
            return $this->remember(false);
        }

        $this->stampCall();

        $fetched = $this->client->fetch([], $this->additionalRequestParameters() + $this->diagnosticParameters());

        if (is_object($fetched)) {
            $this->cache->put($this->cacheKey, $fetched);
            $this->recordCacheState($this->cacheVolatility());

            return $this->remember($fetched);
        }

        $this->cache->put($this->cacheKey, Unresolved::record(), $this->unresolvedCacheTtl());
        $this->recordCacheState($this->cacheVolatility());

        return $this->remember(false);
    }

    /**
     * @param  object|false  $value
     * @return object|false
     */
    private function remember($value)
    {
        $this->resolvedAt = time();

        return $this->resolved = $value;
    }

    /**
     * Whether this product called the service inside the floor.
     *
     * Read from an option rather than from the cache the record lives in: the
     * case this exists for is a store that keeps nothing, and a stamp kept
     * beside the record in that same store would be lost with it.
     *
     * @return bool
     */
    protected function askedTooRecently()
    {
        $interval = $this->minimumCallInterval();

        if ($interval <= 0) {
            return false;
        }

        $stamp = $this->callStamp();

        return isset($stamp['at']) && (time() - (int) $stamp['at']) < $interval;
    }

    /**
     * @return void
     */
    protected function stampCall()
    {
        $stamp = $this->callStamp();
        $stamp['at'] = time();

        $this->stamp = $stamp;
        update_option($this->callStampKey, $stamp, false);
    }

    /**
     * What the site should tell the service about itself.
     *
     * cache_state is the verdict the last write left behind, not this one's:
     * a write is read back after it happens and the request has already gone.
     * A site that keeps nothing asks again within the minute, so the answer
     * arrives on the next call rather than not at all, and a site that has
     * never written one sends nothing rather than guessing.
     *
     * @return array<string,string>
     */
    protected function diagnosticParameters()
    {
        $stamp = $this->callStamp();

        if (! isset($stamp['volatile'])) {
            return [];
        }

        return ['cache_state' => $stamp['volatile'] ? 'volatile' : 'ok'];
    }

    /**
     * @return array<string,mixed>
     */
    private function callStamp()
    {
        if (null === $this->stamp) {
            $stamp = get_option($this->callStampKey);
            $this->stamp = is_array($stamp) ? $stamp : [];
        }

        return $this->stamp;
    }

    /**
     * @param  bool|null  $volatile
     * @return void
     */
    private function recordCacheState($volatile)
    {
        if (null === $volatile) {
            return;
        }

        $stamp = $this->callStamp();

        if (isset($stamp['volatile']) && $stamp['volatile'] === (bool) $volatile) {
            return;
        }

        $stamp['volatile'] = (bool) $volatile;

        $this->stamp = $stamp;
        update_option($this->callStampKey, $stamp, false);
    }

    /**
     * @return int
     */
    protected function minimumCallInterval()
    {
        return self::MINIMUM_CALL_INTERVAL;
    }

    /**
     * @return bool|null Null from a store that cannot answer for itself.
     */
    private function cacheVolatility()
    {
        return $this->cache instanceof LayeredCache ? $this->cache->isVolatile() : null;
    }

    /**
     * How long a lookup that produced no record is remembered for. An hour,
     * against the successful record's day, so a newly published add-on is
     * picked up the same session.
     *
     * @return int
     */
    protected function unresolvedCacheTtl()
    {
        return self::UNRESOLVED_TTL;
    }

    /**
     * @param  mixed  $transient
     * @return mixed
     */
    public function checkUpdate($transient)
    {
        global $pagenow;

        if (! is_object($transient)) {
            $transient = new \stdClass();
        }

        if ('plugins.php' === $pagenow && is_multisite()) {
            return $transient;
        }

        if (! empty($transient->response) && ! empty($transient->response[$this->name])) {
            return $transient;
        }

        $versionInfo = $this->versionInfo();

        if (! is_object($versionInfo) || ! isset($versionInfo->new_version)) {
            return $transient;
        }

        // A build the service withheld is drawn in this package's own row, so
        // nothing is handed to core: a record naming the withheld build would
        // have core offer it, and the wp leg has no floor core could read.
        if ('' !== $this->refusalMessage($versionInfo)) {
            $transient->last_checked = time();
            $transient->checked[$this->name] = $this->version;

            return $transient;
        }

        if ($this->isOfferable($versionInfo)) {
            $transient->response[$this->name] = $versionInfo;
        } elseif ($this->phpRefusalNeedsWordPressToReportIt($versionInfo)) {
            $transient->response[$this->name] = $this->withPhpFloor($versionInfo);
        }

        $transient->last_checked = time();
        $transient->checked[$this->name] = $this->version;

        return $transient;
    }

    /**
     * @param  string  $file
     * @param  array  $plugin
     * @return void
     */
    public function showUpdateNotification($file, $plugin)
    {
        if (! current_user_can('update_plugins') || $this->name !== $file) {
            return;
        }

        // after_plugin_row_{file} fires inside the plugins table: a Throwable
        // loose here truncates the screen mid-table, on the one page needed to
        // deactivate the plugin. Contained the way the notice path is.
        try {
            $this->drawRow($plugin);
        } catch (\Throwable $e) {
            add_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate'], $this->updateTransientPriority());
        }
    }

    /**
     * @param  array  $plugin
     * @return void
     */
    private function drawRow($plugin)
    {
        // A build the service withheld is drawn here whatever product this is
        // and whatever site it is on: it is the one thing core has no row for,
        // since the record names no version newer than the installed one.
        $refusal = $this->refusalMessage($this->versionInfo());

        if (is_string($refusal) && '' !== $refusal) {
            $this->renderUpdateRow($plugin, $this->versionInfo(), $refusal);

            return;
        }

        if (! $this->rendersUpdateRow()) {
            return;
        }

        remove_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate'], $this->updateTransientPriority());

        $updateCache = get_site_transient('update_plugins');

        if (! is_object($updateCache) || empty($updateCache->response) || empty($updateCache->response[$this->name])) {
            if (! is_object($updateCache)) {
                $updateCache = new \stdClass();
            }

            $versionInfo = $this->versionInfo();

            if (! is_object($versionInfo)) {
                add_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate'], $this->updateTransientPriority());

                return;
            }

            if (version_compare($this->version, $versionInfo->new_version, '<')) {
                // Whatever core does with the network transient must find the
                // floor under the name it reads and a package property to
                // read, empty -- never a URL for a build that was withheld.
                $updateCache->response[$this->name] = $this->satisfiesPhpRequirement($versionInfo)
                    ? $versionInfo
                    : $this->withPhpFloor($versionInfo);
            }

            $updateCache->last_checked = time();
            $updateCache->checked[$this->name] = $this->version;

            set_site_transient('update_plugins', $updateCache);
        } else {
            $versionInfo = $updateCache->response[$this->name];
        }

        add_filter('pre_set_site_transient_update_plugins', [$this, 'checkUpdate'], $this->updateTransientPriority());

        if (empty($updateCache->response[$this->name]) || ! version_compare($this->version, $versionInfo->new_version, '<')) {
            return;
        }

        $this->renderUpdateRow($plugin, $versionInfo, $this->updateRowMessage($versionInfo));
    }

    /**
     * Renders a refusal the service stated. A refusal names no newer version,
     * so there is no update row for the other renderers to use.
     *
     * A Throwable loose in `all_admin_notices` white-screens every admin page
     * on the site, so nothing may escape from here.
     *
     * The message is not always a string: sprintf() returns false on PHP 7.4
     * for a translation carrying a placeholder its arguments do not cover, and
     * echoing that draws an empty, undismissable warning box.
     *
     * @return void
     */
    public function showRefusalNotice()
    {
        try {
            if (! $this->refusalNoticeBelongsOnThisScreen() || ! current_user_can('update_plugins')) {
                return;
            }

            $versionInfo = $this->versionInfo();
            $message = $this->refusalMessage($versionInfo);

            if (! is_string($message) || '' === $message) {
                return;
            }

            if ($this->refusalNoticeIsDismissed($versionInfo)) {
                return;
            }

            echo '<div class="notice notice-warning is-dismissible soflyy-updater-refusal"'
                . ' data-dismiss-product="' . esc_attr($this->cacheKey) . '"'
                . ' data-dismiss-nonce="' . esc_attr(wp_create_nonce($this->refusalDismissalAction())) . '">'
                . '<p>' . $message . '</p></div>'
                . $this->refusalDismissalScript();
        } catch (\Throwable $e) {
            return;
        }
    }

    /**
     * @param  mixed  $data
     * @param  string  $action
     * @param  mixed  $args
     * @return mixed
     */
    public function pluginsApiFilter($data, $action = '', $args = null)
    {
        if ('plugin_information' !== $action) {
            return $data;
        }

        if (! isset($args->slug) || $args->slug !== $this->slug) {
            return $data;
        }

        $versionInfo = $this->versionInfo();

        return is_object($versionInfo) ? $versionInfo : $data;
    }

    /**
     * @param  array  $links
     * @param  string  $file
     * @return array
     */
    final public function pluginRowMeta($links, $file)
    {
        if ($file !== $this->name) {
            return (array) $links;
        }

        $url = admin_url(
            'plugin-install.php?tab=plugin-information&plugin=' . $this->slug
            . '&section=changelog&TB_iframe=true&width=600&height=800'
        );

        return array_merge((array) $links, [
            'changelog' => '<a href="' . esc_url($url) . '" class="thickbox open-plugin-details-modal" title="'
                . esc_attr($this->changelogMetaTitle()) . '">' . esc_html(__('Changelog', $this->textDomain())) . '</a>',
        ]);
    }

    /**
     * Tooltip on the row-meta changelog link.
     *
     * @return string
     */
    protected function changelogMetaTitle()
    {
        return sprintf(
            /* translators: %s: product name. */
            __('View %s Changelog', $this->textDomain()),
            $this->productName()
        );
    }

    /**
     * @param  string  $html
     * @param  string  $file
     * @param  array  $pluginData
     * @return string
     */
    public function pluginAutoUpdateSettingHtml($html, $file, $pluginData)
    {
        return $file === $this->name ? '' : $html;
    }

    /**
     * @param  array  $data
     * @param  mixed  $response
     * @return void
     */
    public function customUpdateNote($data, $response)
    {
        if (! is_object($response) || empty($response->custom_update_note) || empty($response->update_note_version)) {
            return;
        }

        if (version_compare($this->version, $response->update_note_version, '<=')) {
            echo wp_kses($response->custom_update_note, 'post');
        }
    }

    /**
     * @return void
     */
    public function showChangelog()
    {
        if (empty($_REQUEST['edd_sl_action']) || 'view_plugin_changelog' !== $_REQUEST['edd_sl_action']) {
            return;
        }

        if (empty($_REQUEST['plugin']) || empty($_REQUEST['slug'])) {
            return;
        }

        if ((string) $_REQUEST['slug'] !== $this->slug) {
            return;
        }

        if (! current_user_can('update_plugins')) {
            wp_die(
                esc_html(__('You do not have permission to install plugin updates', $this->textDomain())),
                esc_html(__('Error', $this->textDomain())),
                ['response' => 403]
            );
        }

        $versionInfo = $this->versionInfo();

        if (is_object($versionInfo) && isset($versionInfo->sections['changelog'])) {
            echo '<div style="background:#fff;padding:10px;">' . wp_kses_post($versionInfo->sections['changelog']) . '</div>';
        }

        $this->halt();
    }

    /**
     * Ends the request after the changelog has been written. A seam:
     * showChangelog() inlining a bare `exit` would take the test runner down
     * with it.
     *
     * @return void
     */
    protected function halt()
    {
        exit;
    }

    // ---------------------------------------------------------------- messages

    /**
     * @param  object  $versionInfo
     * @return bool
     */
    protected function isOfferable($versionInfo)
    {
        if (! version_compare($this->version, $versionInfo->new_version, '<')) {
            return false;
        }

        return $this->satisfiesPhpRequirement($versionInfo);
    }

    /**
     * Whether the site clears the PHP floor the service states for this build.
     *
     * A site sitting exactly on the floor clears it. Final so the comparison
     * stays in one place.
     *
     * @param  object  $versionInfo
     * @return bool
     */
    final protected function satisfiesPhpRequirement($versionInfo)
    {
        if (empty($versionInfo->php_version)) {
            return true;
        }

        return version_compare($this->phpVersion, $versionInfo->php_version, '>=');
    }

    /**
     * Whether a PHP refusal would go unreported unless WordPress reports it.
     *
     * Exactly one renderer names PHP for a given updater: with
     * rendersUpdateRow() this package draws the row and names PHP in it,
     * otherwise WordPress draws it — and only for plugins present in the
     * update_plugins transient.
     *
     * @param  object  $versionInfo
     * @return bool
     */
    protected function phpRefusalNeedsWordPressToReportIt($versionInfo)
    {
        return ! $this->rendersUpdateRow()
            && version_compare($this->version, $versionInfo->new_version, '<')
            && ! $this->satisfiesPhpRequirement($versionInfo);
    }

    /**
     * The record as WordPress needs to read it.
     *
     * Core gates on requires_php where this package gates on php_version, so
     * the floor is raised to this package's and never lowered to core's —
     * including when requires_php states the lower of the two, which core would
     * clear and this package would not.
     *
     * The package URL survives under the name core reads it by. A record
     * with none still carries the property, empty: core's own row reads it
     * before it looks at the PHP floor, so it prints "automatic update is
     * unavailable" rather than naming PHP either way, and the upgrader reads
     * it unconditionally, so leaving it unset is a warning on every install
     * attempt.
     *
     * @param  object  $versionInfo
     * @return object
     */
    protected function withPhpFloor($versionInfo)
    {
        $record = clone $versionInfo;
        $floor = $versionInfo->php_version;

        if (empty($record->requires_php) || version_compare($record->requires_php, $floor, '<')) {
            $record->requires_php = $floor;
        }

        if (empty($record->package)) {
            $record->package = empty($record->download_link) ? '' : $record->download_link;
        }

        return $record;
    }

    /**
     * Whether the service named something WordPress could install.
     *
     * One predicate for two field names: the service sends the same URL under
     * `download_link` and `package`, and either one alone is a package.
     *
     * @param  object  $versionInfo
     * @return bool
     */
    final protected function hasPackage($versionInfo)
    {
        return ! empty($versionInfo->package) || ! empty($versionInfo->download_link);
    }

    /**
     * Which message a row carries.
     *
     * Final: it is the only caller of the leaf messages below, and a subclass
     * reimplementing the sequence without the PHP branch would draw a live
     * upgrade link for a build the site cannot run. Subclasses override the
     * leaf messages; the order they are asked in is not theirs to change.
     *
     * @param  object  $versionInfo
     * @return string
     */
    final protected function updateRowMessage($versionInfo)
    {
        if (! $this->satisfiesPhpRequirement($versionInfo)) {
            return $this->phpRequirementMessage($versionInfo);
        }

        if (! $this->hasPackage($versionInfo)) {
            return $this->licenseAware
                ? $this->licenseRequiredMessage()
                : $this->updateWithoutPackageMessage($versionInfo);
        }

        return $this->updateAvailableMessage($versionInfo);
    }

    /**
     * @param  object  $versionInfo
     * @return string
     */
    protected function phpRequirementMessage($versionInfo)
    {
        return sprintf(
            /* translators: 1: product name, 2: new version, 3: required PHP version, 4: current PHP version. */
            __(
                '%1$s %2$s requires PHP %3$s or greater, you are using PHP %4$s. Please contact your host and tell them to update your server to at least PHP %3$s.',
                $this->textDomain()
            ),
            esc_html($this->productName()),
            esc_html($versionInfo->new_version),
            esc_html($versionInfo->php_version),
            esc_html($this->phpVersion)
        );
    }

    // ------------------------------------------------------- the refusal notice

    /**
     * @return bool
     */
    protected function refusalNoticeBelongsOnThisScreen()
    {
        global $pagenow;

        return in_array((string) $pagenow, $this->refusalNoticeScreens(), true);
    }

    /**
     * The refusal the service stated, or '' when it stated none. Missing fields
     * mean "no opinion": responses predating the service change omit them all.
     *
     * The record is cached for a day and the site is not: a refusal the site
     * has since answered — by moving off the floor, or by installing the build
     * by hand — states nothing.
     *
     * Final: it is the only caller of the two leaf messages, and a subclass
     * replacing the sequence orphans both legs.
     *
     * @param  mixed  $versionInfo
     * @return string
     */
    final protected function refusalMessage($versionInfo)
    {
        if (! is_object($versionInfo) || empty($versionInfo->blocked)) {
            return '';
        }

        $version = isset($versionInfo->blocked_version) ? (string) $versionInfo->blocked_version : '';
        $requires = isset($versionInfo->blocked_requires) ? (string) $versionInfo->blocked_requires : '';
        $by = isset($versionInfo->blocked_by) ? (string) $versionInfo->blocked_by : '';

        if ('' === $version || '' === $requires) {
            return '';
        }

        if (version_compare($this->version, $version, '>=')) {
            return '';
        }

        // The service names one leg — the first it found — but the response
        // also carries the withheld build's other floor, under `requires` (WP)
        // and `requires_php`. A site below both is told both, or it would clear
        // one, ask again, and be refused for the other.
        if ('php' === $by) {
            if ($this->siteMeetsRefusalFloor($this->phpVersion, $requires)) {
                return '';
            }

            $wpFloor = isset($versionInfo->requires) ? (string) $versionInfo->requires : '';

            return '' !== $wpFloor && ! $this->siteMeetsRefusalFloor($this->siteWordPressVersion(), $wpFloor)
                ? $this->bothRefusalMessage($version, $requires, $wpFloor)
                : $this->phpRefusalMessage($version, $requires);
        }

        if ('wp' === $by) {
            if ($this->siteMeetsRefusalFloor($this->siteWordPressVersion(), $requires)) {
                return '';
            }

            $phpFloor = isset($versionInfo->requires_php) ? (string) $versionInfo->requires_php : '';

            return '' !== $phpFloor && ! $this->siteMeetsRefusalFloor($this->phpVersion, $phpFloor)
                ? $this->bothRefusalMessage($version, $phpFloor, $requires)
                : $this->wordPressRefusalMessage($version, $requires);
        }

        return '';
    }

    /**
     * Whether the site now stands on or above a floor a cached refusal states.
     * A version the site cannot name meets nothing.
     *
     * @param  string  $current
     * @param  string  $requires
     * @return bool
     */
    final protected function siteMeetsRefusalFloor($current, $requires)
    {
        return '' !== (string) $current && version_compare((string) $current, $requires, '>=');
    }

    /**
     * @param  string  $version  The build that was withheld.
     * @param  string  $requires  The PHP floor that withheld it.
     * @return string
     */
    protected function phpRefusalMessage($version, $requires)
    {
        return sprintf(
            /* translators: 1: product name, 2: withheld version, 3: required PHP version, 4: current PHP version. */
            __(
                '%1$s %2$s was not offered to this site: it requires PHP %3$s or greater, and this site is running PHP %4$s. Please contact your host and tell them to update your server to at least PHP %3$s.',
                $this->textDomain()
            ),
            esc_html($this->productName()),
            esc_html($version),
            esc_html($requires),
            esc_html($this->phpVersion)
        );
    }

    /**
     * @param  string  $version  The build that was withheld.
     * @param  string  $requiresPhp  The PHP floor it needs.
     * @param  string  $requiresWp  The WordPress floor it needs.
     * @return string
     */
    protected function bothRefusalMessage($version, $requiresPhp, $requiresWp)
    {
        return sprintf(
            /* translators: 1: product name, 2: withheld version, 3: required PHP version, 4: required WordPress version, 5: current PHP version, 6: current WordPress version. */
            __(
                '%1$s %2$s was not offered to this site: it requires PHP %3$s or greater and WordPress %4$s or greater, and this site is running PHP %5$s and WordPress %6$s. Please contact your host and tell them to update your server to at least PHP %3$s, and update WordPress to at least version %4$s.',
                $this->textDomain()
            ),
            esc_html($this->productName()),
            esc_html($version),
            esc_html($requiresPhp),
            esc_html($requiresWp),
            esc_html($this->phpVersion),
            esc_html($this->siteWordPressVersion())
        );
    }

    /**
     * @param  string  $version  The build that was withheld.
     * @param  string  $requires  The WordPress floor that withheld it.
     * @return string
     */
    protected function wordPressRefusalMessage($version, $requires)
    {
        return sprintf(
            /* translators: 1: product name, 2: withheld version, 3: required WordPress version, 4: current WordPress version. */
            __(
                '%1$s %2$s was not offered to this site: it requires WordPress %3$s or greater, and this site is running WordPress %4$s. Please update WordPress to at least version %3$s.',
                $this->textDomain()
            ),
            esc_html($this->productName()),
            esc_html($version),
            esc_html($requires),
            esc_html($this->siteWordPressVersion())
        );
    }

    /**
     * The WordPress version the service read: core's default request
     * User-Agent carries it.
     *
     * @return string
     */
    protected function siteWordPressVersion()
    {
        return (string) get_bloginfo('version');
    }

    /**
     * The nonce action a dismissal is signed with. One per product, so a nonce
     * minted for one product's notice cannot dismiss another's.
     *
     * @return string
     */
    protected function refusalDismissalAction()
    {
        return self::REFUSAL_DISMISSAL_ACTION . '_' . $this->cacheKey;
    }

    /**
     * User meta key the dismissal is recorded under, holding the build that was
     * dismissed. One notice per product, one dismissal per administrator.
     *
     * @return string
     */
    protected function refusalDismissalMetaKey()
    {
        return 'soflyy_updater_dismissed_refusal_' . $this->cacheKey;
    }

    /**
     * Whether this administrator has dismissed the refusal now stated.
     *
     * Keyed by the withheld build, so a later refusal of a different build is
     * not pre-dismissed. Final for the reason refusalMessage() is: a subclass
     * replacing the comparison hides refusals nobody has seen.
     *
     * @param  mixed  $versionInfo
     * @return bool
     */
    final protected function refusalNoticeIsDismissed($versionInfo)
    {
        $version = is_object($versionInfo) && isset($versionInfo->blocked_version)
            ? (string) $versionInfo->blocked_version
            : '';

        return '' !== $version
            && $version === (string) get_user_meta(get_current_user_id(), $this->refusalDismissalMetaKey(), true);
    }

    /**
     * Records this administrator's dismissal.
     *
     * The build dismissed is read from the record rather than taken from the
     * request, so a dismissal can only ever name the refusal on screen.
     *
     * @return void
     */
    public function dismissRefusalNotice()
    {
        $product = isset($_POST['product']) ? (string) $_POST['product'] : '';

        // Every instance on the site binds this one hook, so each runs on
        // every dismissal; the product names which notice was closed.
        if ($product !== $this->cacheKey) {
            return;
        }

        if (! current_user_can('update_plugins') || ! check_ajax_referer($this->refusalDismissalAction(), false, false)) {
            return;
        }

        $versionInfo = $this->versionInfo();

        if (is_object($versionInfo) && ! empty($versionInfo->blocked_version)) {
            update_user_meta(
                get_current_user_id(),
                $this->refusalDismissalMetaKey(),
                (string) $versionInfo->blocked_version
            );
        }
    }

    /**
     * Core's `is-dismissible` only hides the notice on the page it is on, so
     * the button it draws is wired to the handler above. Inline because the
     * dist archive carries no assets, and printed with the notice so it reaches
     * no screen the notice does not.
     *
     * @return string
     */
    protected function refusalDismissalScript()
    {
        return '<script>if(!window.soflyyUpdaterRefusalDismiss){window.soflyyUpdaterRefusalDismiss=1;'
            . 'document.addEventListener("click",function(e){'
            . 'var b=e.target&&e.target.closest?e.target.closest(".soflyy-updater-refusal .notice-dismiss"):null;'
            . 'if(!b){return;}'
            . 'var n=b.closest(".soflyy-updater-refusal"),d=new FormData();'
            . 'd.append("action","' . self::REFUSAL_DISMISSAL_ACTION . '");'
            . 'd.append("product",n.getAttribute("data-dismiss-product"));'
            . 'd.append("_ajax_nonce",n.getAttribute("data-dismiss-nonce"));'
            . 'fetch(ajaxurl,{method:"POST",body:d,credentials:"same-origin"});'
            . '});}</script>';
    }

    /**
     * @return string
     */
    protected function licenseRequiredMessage()
    {
        $message = sprintf(
            /* translators: 1: product name, 2: settings page URL. */
            __(
                'A new version of %1$s is available. <strong>A valid license is required to enable updates - enter your license key on the <a href="%2$s">Settings</a> page.</strong>',
                $this->textDomain()
            ),
            esc_html($this->productName()),
            esc_url($this->settingsUrl())
        );

        $message .= ' ' . sprintf(
            /* translators: 1: pricing page URL, 2: customer portal URL. */
            __(
                'If you don\'t have a license, please see <a href="%1$s" target="_blank">details &amp; pricing</a>. If you do have a license, you can access it at the <a href="%2$s" target="_blank">customer portal</a>.',
                $this->textDomain()
            ),
            esc_url($this->pricingUrl()),
            esc_url($this->portalUrl())
        );

        return $message;
    }

    /**
     * @param  object  $versionInfo
     * @return string
     */
    protected function updateWithoutPackageMessage($versionInfo)
    {
        return sprintf(
            /* translators: 1: product name, 2: changelog URL, 3: new version. */
            __(
                'There is a new version of %1$s available. <a target="_blank" class="thickbox" href="%2$s">View version %3$s details</a>.',
                $this->textDomain()
            ),
            esc_html($this->productName()),
            esc_url($this->changelogUrl()),
            esc_html($versionInfo->new_version)
        );
    }

    /**
     * @param  object  $versionInfo
     * @return string
     */
    protected function updateAvailableMessage($versionInfo)
    {
        return sprintf(
            /* translators: 1: product name, 2: changelog URL, 3: new version, 4: update URL. */
            __(
                'There is a new version of %1$s available. <a target="_blank" class="thickbox" href="%2$s">View version %3$s details</a> or <a href="%4$s" class="update-link">update now</a>.',
                $this->textDomain()
            ),
            esc_html($this->productName()),
            esc_url($this->changelogUrl()),
            esc_html($versionInfo->new_version),
            esc_url(wp_nonce_url(
                self_admin_url('update.php?action=upgrade-plugin&plugin=') . $this->name,
                'upgrade-plugin_' . $this->name
            ))
        );
    }

    protected function changelogUrl()
    {
        return self_admin_url(
            'plugin-install.php?tab=plugin-information&plugin=' . $this->slug
            . '&section=changelog&TB_iframe=true&width=772&height=412'
        );
    }

    /**
     * @param  array  $plugin
     * @param  object  $versionInfo
     * @param  string  $message  Or false, when a broken translation made sprintf() fail.
     * @return void
     */
    protected function renderUpdateRow($plugin, $versionInfo, $message)
    {
        if (! is_string($message) || '' === $message) {
            return;
        }

        $pluginSlug = sanitize_title(isset($plugin['Name']) ? $plugin['Name'] : $this->productName());
        $active = is_plugin_active($this->name) ? 'active' : '';
        $columns = get_column_headers(get_current_screen());
        $colspan = is_countable($columns) ? count($columns) : 3;

        echo '<tr class="plugin-update-tr ' . esc_attr($active) . ' soflyy-updater-custom" data-slug="' . esc_attr($pluginSlug) . '">'
            . '<td colspan="' . (int) $colspan . '" class="plugin-update colspanchange">'
            . '<div class="update-message notice inline notice-warning notice-alt"><p>'
            . $message;

        do_action('in_plugin_update_message-' . $this->name, [], $versionInfo);

        echo '</p></div></td></tr>';
    }
}
