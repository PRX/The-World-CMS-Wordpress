<?php

namespace Wpae\App\Gate;

// The interface must exist before this class compiles; not every loader here autoloads.
require_once __DIR__ . '/LicenceStateSource.php';

/** The production licence source. It asks the state's owner rather than reading its options. */
final class ClientState implements LicenceStateSource
{
    const OWNER = '\\Wpae\\App\\License\\Licensing';

    /** @return bool */
    public function available()
    {
        return class_exists(self::OWNER);
    }

    /** @return object|null */
    private function owner()
    {
        if (! $this->available()) {
            return null;
        }

        return call_user_func([self::OWNER, 'instance']);
    }

    /** @return bool */
    public function determinable()
    {
        $owner = $this->owner();

        return $owner === null ? false : (bool) $owner->determinable();
    }

    /** @return bool  An unavailable source answers true; it must never be what refuses. */
    public function licensed()
    {
        $owner = $this->owner();

        return $owner === null ? true : (bool) $owner->isLicensed();
    }

    /** @return string */
    public function state()
    {
        $owner = $this->owner();

        return $owner === null ? '' : (string) $owner->licenseState()['state'];
    }

    /** @return string */
    public function stateReason()
    {
        $owner = $this->owner();

        return $owner === null ? '' : (string) $owner->licenseState()['reason'];
    }

    /** @return int */
    public function graceUntil()
    {
        $owner = $this->owner();

        return $owner === null ? 0 : (int) $owner->licenseDeadline();
    }

    /** @return string */
    public function status()
    {
        $owner = $this->owner();

        return $owner === null ? '' : (string) $owner->status();
    }

    /** @return bool */
    public function recordsExist()
    {
        $owner = $this->owner();

        return $owner === null ? false : (bool) $owner->recordsExist();
    }

    /** @return bool */
    public function awaitingFirstAnswer()
    {
        $owner = $this->owner();

        return $owner === null ? false : (bool) $owner->awaitingFirstAnswer();
    }

    /** @return string */
    public function name()
    {
        return 'client-licence-state';
    }
}
