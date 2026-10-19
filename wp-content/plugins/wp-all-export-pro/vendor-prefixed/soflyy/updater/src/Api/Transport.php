<?php

namespace Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Api;

interface Transport
{
    /**
     * The endpoint this transport sends a version check to.
     *
     * The path belongs to the transport, not to the caller.
     *
     * @param  string  $apiUrl  Trailing-slashed base URL of the update service.
     * @return string
     */
    public function endpoint($apiUrl);

    /**
     * @param  string  $endpoint  Absolute endpoint URL, which may already carry
     *                            a query string.
     * @param  array<string,string>  $params
     * @return array<string,mixed>|\WP_Error The raw wp_remote_* response.
     */
    public function send($endpoint, array $params);
}
