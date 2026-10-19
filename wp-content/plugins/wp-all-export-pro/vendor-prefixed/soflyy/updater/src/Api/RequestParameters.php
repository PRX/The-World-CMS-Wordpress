<?php

namespace Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Api;

/**
 * Builds the parameter set for a check_version ping.
 *
 * Pure: takes values in, returns an array of strings. No WordPress calls, so
 * the wire contract is directly assertable.
 */
final class RequestParameters
{
    /** @var string */
    private $slug;

    /** @var string */
    private $pluginFile;

    /** @var string */
    private $version;

    /** @var string */
    private $phpVersion;

    public function __construct($slug, $pluginFile, $version, $phpVersion)
    {
        $this->slug = (string) $slug;
        $this->pluginFile = (string) $pluginFile;
        $this->version = (string) $version;
        $this->phpVersion = (string) $phpVersion;
    }

    /**
     * @param  array<string,mixed>  $data  Merged constructor data for the product.
     * @param  string  $siteUrl  home_url() of the pinging site.
     * @param  array<string,mixed>  $additional  See Updater::additionalRequestParameters().
     * @return array<string,string>
     */
    public function build(array $data, $siteUrl, array $additional = [])
    {
        $params = ['edd_action' => 'get_version'];

        // Always present, empty when the product carries no key, which is what
        // the updaters this replaces put on the wire.
        $params['license'] = isset($data['license']) ? (string) $data['license'] : '';

        foreach (['item_name', 'item_id'] as $key) {
            $value = $this->optional($data, $key);
            if (null !== $value) {
                $params[$key] = $value;
            }
        }

        $params['slug'] = $this->slug;
        $params['plugin'] = $this->pluginFile;

        $author = $this->optional($data, 'author');
        if (null !== $author) {
            $params['author'] = $author;
        }

        $params['url'] = (string) $siteUrl;
        $params['version'] = $this->version;

        $params['php_version'] = $this->phpVersion;

        foreach ($additional as $key => $value) {
            $value = $this->optional($additional, $key);
            if (null !== $value && ! isset($params[$key])) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * @return string|null Null when the caller did not supply the value.
     */
    private function optional(array $data, $key)
    {
        $value = isset($data[$key]) ? $data[$key] : null;

        if (null === $value || false === $value) {
            return null;
        }

        return (string) $value;
    }
}
