<?php

namespace Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Cache;

/**
 * The single store: the transient API, and nothing else. The key is the
 * caller's — Updater::__construct builds it — so passing the one the previous
 * implementations used is what keeps caches written by the old code valid.
 */
final class TransientCache implements VersionInfoCache
{
    const TTL = 86400;

    /** @var int */
    private $ttl;

    public function __construct($ttl = self::TTL)
    {
        $this->ttl = (int) $ttl;
    }

    public function get($key)
    {
        $value = get_transient($key);

        return is_object($value) ? $value : null;
    }

    public function put($key, $value, $ttl = null)
    {
        set_transient($key, $value, null === $ttl ? $this->ttl : (int) $ttl);
    }

    public function forget($key)
    {
        delete_transient($key);
    }
}
