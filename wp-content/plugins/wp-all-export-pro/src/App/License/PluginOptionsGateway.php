<?php

namespace Wpae\App\License;

use PMXE_Plugin;
use Throwable;

final class PluginOptionsGateway implements OptionsGateway
{
    /**
     * @var bool  the store, not a key, was unreadable earlier in this request;
     *            readable() re-probes, so nothing else carries that forward
     */
    private $storeFailed = false;

    /**
     * Throwable, not Exception: the public wp_all_export_config_options filter
     * runs inside getOption(), and a callback that reaches an undefined function
     * raises an Error, which in PHP 7 is not an Exception.
     */
    public function get($key, $default = null)
    {
        try {
            return PMXE_Plugin::getInstance()->getOption($key);
        } catch (Throwable $e) {
            // An undeclared key and an unreadable store raise alike, so which one
            // this was is settled by probing rather than by inspecting $e.
            if (! $this->probe()) {
                $this->storeFailed = true;
            }

            return $default;
        }
    }

    /** @return array<string,mixed> */
    public function all()
    {
        try {
            $options = PMXE_Plugin::getInstance()->getOption();
        } catch (Throwable $e) {
            $this->storeFailed = true;

            return [];
        }

        if (! is_array($options)) {
            $this->storeFailed = true;

            return [];
        }

        return $options;
    }

    /** @return bool  does the store answer, and with the shape it should */
    private function probe()
    {
        try {
            return is_array(PMXE_Plugin::getInstance()->getOption());
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * getOption() with no argument fails exactly when the store is unreadable,
     * where a merely undeclared key succeeds. The shape is asserted as well: a
     * filter that forgets to return yields null without raising anything.
     */
    public function readable()
    {
        if ($this->storeFailed) {
            return false;
        }

        if ($this->probe()) {
            return true;
        }

        $this->storeFailed = true;

        return false;
    }

    public function update(array $values)
    {
        // Writing from an unreadable store would persist an empty licence over a real one.
        if (! $this->readable()) {
            return;
        }

        try {
            PMXE_Plugin::getInstance()->updateOption($values);
        } catch (Throwable $e) {
            // A key not already in the stored array; a skipped write beats a fatal.
        }
    }
}
