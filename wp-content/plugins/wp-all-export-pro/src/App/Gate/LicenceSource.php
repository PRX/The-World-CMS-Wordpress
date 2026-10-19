<?php

namespace Wpae\App\Gate;

/** Where the gate gets its answer. An unavailable source cannot refuse anybody. */
interface LicenceSource
{
    /** @return bool Whether this source is present and able to answer. */
    public function available();

    /** @return bool  False means "could not determine", never "this site is unlicensed". */
    public function determinable();

    /** @return bool */
    public function licensed();

    /** @return string A status vocabulary term, or '' when unavailable. */
    public function status();

    /** @return bool Whether the site has records of this plugin's own type. */
    public function recordsExist();

    /** @return string */
    public function name();
}
