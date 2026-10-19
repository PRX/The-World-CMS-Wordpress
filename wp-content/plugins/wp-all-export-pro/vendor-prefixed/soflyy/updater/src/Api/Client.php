<?php

namespace Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Api;

final class Client
{
    /** @var string Trailing-slashed base URL of the update service. */
    private $apiUrl;

    /** @var string */
    private $slug;

    /** @var array<string,mixed> */
    private $apiData;

    /** @var RequestParameters */
    private $parameters;

    /** @var Transport */
    private $transport;

    /**
     * @param  array<string,mixed>  $apiData
     */
    public function __construct($apiUrl, $slug, array $apiData, RequestParameters $parameters, ?Transport $transport = null)
    {
        $this->apiUrl = trailingslashit($apiUrl);
        $this->slug = (string) $slug;
        $this->apiData = $apiData;
        $this->parameters = $parameters;
        $this->transport = $transport ?: new QueryStringTransport();
    }

    /**
     * @param  array<string,mixed>  $data  Overrides merged over the constructor data.
     * @param  array<string,mixed>  $additional
     * @return object|false The decoded response, or false on any failure.
     */
    public function fetch(array $data = [], array $additional = [])
    {
        $data = array_merge(['slug' => $this->slug], $this->apiData, $data);

        if (! isset($data['slug']) || (string) $data['slug'] !== $this->slug) {
            return false;
        }

        if (untrailingslashit($this->apiUrl) === untrailingslashit(home_url())) {
            return false;
        }

        $params = $this->parameters->build($data, home_url(), $additional);
        $response = $this->transport->send($this->transport->endpoint($this->apiUrl), $params);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response));

        if (! is_object($body)) {
            return false;
        }

        // maybe_unserialize(), with the class restriction core's version does
        // not offer. These three fields are whatever the service put on the
        // wire, and core unserialises with no allowed_classes, so a serialized
        // object in one of them would be instantiated here and run its own
        // code. Refusing the class is not enough on its own: the incomplete
        // object it leaves behind serializes back out under the original class
        // name, and the record is about to be cached in an option core reads
        // back with no restriction at all. Anything holding an object is
        // dropped before that can happen.
        foreach (['contributors', 'banners', 'sections'] as $key) {
            if (isset($body->$key) && is_serialized($body->$key)) {
                $value = @unserialize(trim($body->$key), ['allowed_classes' => false]);
                $body->$key = self::holdsAnObject($value) ? null : $value;
            }
        }

        // A response without sections, or naming no version, is not a usable
        // record. The service answers an unknown slug with every field set to
        // the empty string rather than omitting them, which isset() admits and
        // the cache would then pin for its full TTL.
        if (! isset($body->sections) || empty($body->new_version)) {
            return false;
        }

        return $body;
    }

    /**
     * @param  mixed  $value
     * @return bool
     */
    private static function holdsAnObject($value)
    {
        if (is_object($value)) {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::holdsAnObject($item)) {
                    return true;
                }
            }
        }

        return false;
    }
}
