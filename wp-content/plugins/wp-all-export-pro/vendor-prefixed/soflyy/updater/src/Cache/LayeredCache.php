<?php

namespace Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Cache;

/**
 * Reads from the first store that answers and writes to all of them.
 *
 * The transient comes first because on a healthy site it is the cheapest read
 * there is — an object cache hit, no query. The option behind it costs one row
 * per product per day and is what the site falls back to when the transient
 * did not survive; without it a broken object cache means an HTTP request per
 * render, measured in production at one every 41 seconds per product.
 *
 * A store that throws is skipped rather than allowed to take the update check
 * down with it: this whole path exists to offer an update, and no failure in
 * it should surface to the customer.
 */
final class LayeredCache implements VersionInfoCache
{
    /**
     * The default a record is written under, which is the stores' own. Kept
     * here because a caller holding this cache asks it, not the stores, how
     * long a record lives.
     */
    const TTL = TransientCache::TTL;

    /** @var VersionInfoCache[] */
    private $stores;

    /** @var bool|null */
    private $volatile;

    public function __construct(array $stores)
    {
        $this->stores = $stores;
    }

    public function get($key)
    {
        foreach ($this->stores as $store) {
            try {
                $value = $store->get($key);
            } catch (\Exception $e) {
                continue;
            }

            if (null !== $value) {
                return $value;
            }
        }

        return null;
    }

    public function put($key, $value, $ttl = null)
    {
        foreach ($this->stores as $store) {
            try {
                $store->put($key, $value, $ttl);
            } catch (\Exception $e) {
                continue;
            }
        }

        $this->volatile = $this->firstStoreForgot($key, $value);
    }

    public function forget($key)
    {
        foreach ($this->stores as $store) {
            try {
                $store->forget($key);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    /**
     * Whether the write to the first store did not survive being read back.
     *
     * Answered only after a write, and null before one: "not known" and "the
     * cache is fine" are different things, and a site that has not written
     * this request must not be reported as either.
     *
     * @return bool|null
     */
    public function isVolatile()
    {
        return $this->volatile;
    }

    /**
     * @param  string  $key
     * @param  object  $value
     * @return bool
     */
    private function firstStoreForgot($key, $value)
    {
        if ([] === $this->stores) {
            return true;
        }

        try {
            return null === $this->stores[0]->get($key);
        } catch (\Exception $e) {
            return true;
        }
    }
}
