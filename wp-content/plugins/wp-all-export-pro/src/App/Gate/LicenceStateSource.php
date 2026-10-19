<?php

namespace Wpae\App\Gate;

/** Separate from LicenceSource because licensed() is true throughout grace, so nothing keyed on it can warn during one. */
interface LicenceStateSource extends LicenceSource
{
    /**
     * @return string  One of valid, no_key, grace, blocked_unverified,
     *                 blocked_invalid, blocked_expired, blocked_limit, or ''.
     */
    public function state();

    /** @return string  unverified, invalid, expired or limit; '' when not in grace. */
    public function stateReason();

    /**
     * @return int  Unix timestamp the grace period ends, or 0, which a source that
     *              cannot read its own state also answers, so check it is in the future.
     */
    public function graceUntil();
}
