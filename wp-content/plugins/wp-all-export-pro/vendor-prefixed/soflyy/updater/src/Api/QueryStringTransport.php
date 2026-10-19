<?php

namespace Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Api;

/**
 * Sends the ping as a GET request with every parameter in the query string.
 *
 * Not built with add_query_arg(): it inserts the values handed to it without
 * encoding them, while urlencode_deep()ing whatever was already in the URL's
 * query string, so the same parameter would be encoded once or not at all
 * depending on where it came from. http_build_query() encodes every value here,
 * exactly once.
 */
final class QueryStringTransport implements Transport
{
    const TIMEOUT = 15;

    public function endpoint($apiUrl)
    {
        return trailingslashit($apiUrl) . 'check_version/';
    }

    public function send($endpoint, array $params)
    {
        $separator = false === strpos($endpoint, '?') ? '?' : '&';
        $url = $endpoint . $separator . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return wp_remote_get(esc_url_raw($url), [
            'timeout' => self::TIMEOUT,
            'sslverify' => true,
        ]);
    }
}
