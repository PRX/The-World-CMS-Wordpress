<?php

namespace Soflyy\WpAllExportPro\Vendor\Soflyy\Updater\Cache;

/**
 * The record written when a lookup produced no usable version record.
 *
 * The record is a plain stdClass carrying one marker key, not an instance of
 * this class: a transient outlives the code that wrote it, and a package class
 * in there unserialises to __PHP_Incomplete_Class on any request that has not
 * autoloaded this file, where reading the marker warns on PHP 8 and answers as
 * a miss on every version.
 */
final class Unresolved
{
    const MARKER = 'soflyy_updater_unresolved';

    /**
     * @return object
     */
    public static function record()
    {
        return (object) [self::MARKER => true];
    }

    /**
     * @param  mixed  $value
     * @return bool
     */
    public static function is($value)
    {
        return is_object($value) && ! empty($value->{self::MARKER});
    }
}
