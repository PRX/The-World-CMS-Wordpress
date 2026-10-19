<?php

namespace Wpae\App\License;

/**
 * Read and write plugin options.
 *
 * Implementations MUST go through PMXE_Plugin::getOption()/updateOption() rather
 * than get_option(): only those apply the wp_all_export_config_options filter,
 * which is how a licence can be present without an encoded value in the row.
 */
interface OptionsGateway
{
    /**
     * @param  mixed  $default  returned both for an undeclared key and for a store
     *         that could not be read at all; readable() tells the two apart
     */
    public function get($key, $default = null);

    /** @return array<string,mixed>  every option, filtered; [] when the store could not be read */
    public function all();

    /** Whether the store can be read at all: "no licence" and "no information" differ. */
    public function readable();

    public function update(array $values);
}
