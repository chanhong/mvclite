<?php

/**
 * CConfig — Application configuration container.
 *
 * Holds the merged configuration array built by cfg.php and provides
 * dot-notation access so callers never need to reach into nested arrays
 * directly.
 *
 * Usage:
 *   $cfg->get('info.emailfrom');          // 'email@email.com'
 *   $cfg->get('levels.admin');             // '90'
 *   $cfg->get('missing.key', 'fallback'); // 'fallback'
 *   $cfg->set('info.sitename', 'MvcLite');
 *   $cfg->has('folder.app');               // true
 *
 * @author chanhong
 */

namespace MvcLite;

defined('_MVCLite') or die('Direct Access to this location is not allowed.');

class CConfig
{
    // ------------------------------------------------------------------
    // Static bridge — kept ONLY for legacy code that reads CConfig::$_cfg
    // directly.  New code should always go through the DI container and
    // call $cfg->get('key').  Once all call-sites are migrated this
    // property and the sync call in __construct can be removed.
    // ------------------------------------------------------------------
// CConfig.php
public array $path = [];


    public static array $_cfg = [];

    protected array $data = [];

    // ------------------------------------------------------------------
    // Construction
    // ------------------------------------------------------------------

    /**
     * @param array|null $config  Pass the array returned by cfg.php.
     *                            Falls back to the static $_cfg bridge so
     *                            that code still using CConfig::$_cfg is
     *                            not broken during the migration period.
     */
    public function __construct(?array $config = null)
    {
        $this->data = $config ?? static::$_cfg ?? [];
    }

    // ------------------------------------------------------------------
    // Core read / write
    // ------------------------------------------------------------------

    /**
     * Retrieve a value using dot-notation.
     *
     * @param string $key     e.g. 'info.emailfrom' or a plain top-level key
     * @param mixed  $default Returned when the key does not exist
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Fast path — exact top-level key
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        // Dot-notation traversal
        $segments = explode('.', $key);
        $value    = $this->data;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Store a value using dot-notation.
     * Intermediate arrays are created automatically.
     *
     *   $cfg->set('info.sitename', 'MvcLite');
     */
    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target   = &$this->data;

        foreach ($segments as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }

        $target = $value;
    }

    /**
     * Check whether a dot-notation key exists (and is not null).
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    // ------------------------------------------------------------------
    // Bulk helpers
    // ------------------------------------------------------------------

    /** Replace the entire configuration array at once. */
    public function setAll(array $data): void
    {
        $this->data = $data;
    }

    /** Return the entire configuration array. */
    public function getAll(): array
    {
        return $this->data;
    }

    /**
     * Merge an array of overrides into the current configuration.
     * Top-level keys are merged; nested arrays are replaced, not deep-merged,
     * which keeps the behaviour predictable.
     */
    public function merge(array $overrides): void
    {
        $this->data = array_merge($this->data, $overrides);
    }
}
