<?php

namespace Wpae\App\License;

/** Whether this site has any exports, resolved at most once per request. */
final class Records
{
    /** @var callable */
    private $probe;

    /** @var bool|null */
    private $result;

    /** @param callable|null $probe  fn(): bool */
    public function __construct($probe = null)
    {
        $this->probe = is_callable($probe) ? $probe : [self::class, 'queryExports'];
    }

    /** @return bool */
    public function exist()
    {
        if ($this->result === null) {
            $this->result = (bool) call_user_func($this->probe);
        }

        return $this->result;
    }

    public function forget()
    {
        $this->result = null;
    }

    /**
     * The table name is built from $wpdb->prefix and a literal and must never be
     * interpolated from a variable: prepare() cannot parameterise an identifier.
     */
    public static function queryExports()
    {
        global $wpdb;

        if (! isset($wpdb) || ! is_object($wpdb)) {
            return false;
        }

        $table = $wpdb->prefix . 'pmxe_exports';

        // An activation that failed halfway leaves a site with no exports table, and
        // wpdb prints the resulting error into whatever page is rendering, so ask
        // first. prepare() applies here: the name is a VALUE in a LIKE pattern.
        $suppressed = $wpdb->suppress_errors(true);

        $exists = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))
        ) === $table;

        $found = $exists
            ? (bool) $wpdb->get_var('SELECT id FROM ' . $wpdb->prefix . 'pmxe_exports LIMIT 1')
            : false;

        $wpdb->suppress_errors($suppressed);

        return $found;
    }
}
