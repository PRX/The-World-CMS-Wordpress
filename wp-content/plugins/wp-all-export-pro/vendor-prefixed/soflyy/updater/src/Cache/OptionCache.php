<?php

namespace Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Cache;

/**
 * The same record in an option row.
 *
 * A transient is not a durable store. Where a persistent object cache drop-in
 * is installed, set_transient() writes to that cache and never to the options
 * table, so a cache that has been misconfigured, has filled, or is evicting
 * immediately leaves every write going nowhere and every read a miss — the
 * client then asks the service again on the next render, for as long as the
 * condition lasts. An option survives it: update_option() always writes its
 * row, and get_option() falls back to the table when the cache misses.
 *
 * Not autoloaded. A version record carries the changelog and the sections, and
 * six products autoloading theirs would be paid for on every request the site
 * serves, including the ones that never look at an update.
 */
final class OptionCache implements VersionInfoCache
{
    const PREFIX = 'soflyy_updater_';

    const TTL = 86400;

    /** @var int */
    private $ttl;

    public function __construct($ttl = self::TTL)
    {
        $this->ttl = (int) $ttl;
    }

    public function get($key)
    {
        $entry = get_option($this->option($key));

        if (! is_array($entry) || ! isset($entry['expires'], $entry['value'])) {
            return null;
        }

        if ($entry['expires'] > 0 && $entry['expires'] <= time()) {
            return null;
        }

        return is_object($entry['value']) ? $entry['value'] : null;
    }

    public function put($key, $value, $ttl = null)
    {
        $ttl = null === $ttl ? $this->ttl : (int) $ttl;

        update_option($this->option($key), [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
        ], false);
    }

    public function forget($key)
    {
        delete_option($this->option($key));
    }

    /**
     * @param  string  $key
     * @return string
     */
    private function option($key)
    {
        return self::PREFIX . $key;
    }
}
