<?php

namespace Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Cache;

interface VersionInfoCache
{
    /**
     * @return object|null Null on a miss or an expired entry.
     */
    public function get($key);

    /**
     * @param  object  $value
     * @param  int|null  $ttl  Seconds, or null for the store's own default.
     * @return void
     */
    public function put($key, $value, $ttl = null);

    /**
     * @return void
     */
    public function forget($key);
}
