<?php

/**
 * CConfig — Application configuration container.
 *
 * Holds the merged configuration array built by cfg.php and provides
 * dot-notation access so callers never need to reach into nested arrays
 * directly.
 *
 * INSTANCE usage (DI / new code):
 *   $cfg->_get('info.emailfrom');
 *   $cfg->_set('info.sitename', 'MvcLite');
 *   $cfg->_has('folder.app');
 *
 * STATIC / FACADE usage (boot-time or legacy static code):
 *   CConfig::get('info.emailfrom');   // works before AND after DI is ready
 *   CConfig::set('info.selctl', 'front');
 *   CConfig::has('folder.app');
 *
 * The facade delegates to the instance once setInstance() is called
 * (after DI container is built in index.php).  Before that it falls
 * back to the static $_cfg bridge array, so boot-time code like
 * setActiveCtrl() works without any changes.
 *
 * @author chanhong
 */

namespace MvcLite;

defined('_MVCLite') or die('Direct Access to this location is not allowed.');

class CConfig
{
    // ------------------------------------------------------------------
    // Facade — holds the singleton instance once DI is ready
    // ------------------------------------------------------------------
    private static ?self $instance = null;

    /**
     * Call once in index.php after initDI():
     *   CConfig::setInstance($container->make('cfg'));
     */
    public static function setInstance(self $cfg): void
    {
        static::$instance = $cfg;
    }

    // ------------------------------------------------------------------
    // Static facade — get/set/has work at any point in the boot sequence
    // ------------------------------------------------------------------

    public static function get(string $key, mixed $default = null): mixed
    {
        if (static::$instance !== null) {
            return static::$instance->_get($key, $default);
        }
        // Pre-DI fallback: read from static bridge
        $segments = explode('.', $key);
        $value = static::$_cfg;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        //        pln($value,'get');
        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        if (static::$instance !== null) {
            static::$instance->_set($key, $value);
            return;
        }
        // Pre-DI fallback: write to static bridge
        $segments = explode('.', $key);
        $target = &static::$_cfg;
        foreach ($segments as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }
        $target = $value;
    }

    public static function has(string $key): bool
    {
        if (static::$instance !== null) {
            return static::$instance->_has($key);
        }
        return static::get($key) !== null;
    }

    // ------------------------------------------------------------------
    // Static bridge — kept ONLY for legacy code that reads CConfig::$_cfg
    // directly.  New code should use CConfig::get() / CConfig::set().
    // Remove once all call-sites are migrated.
    // ------------------------------------------------------------------
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
    // Instance read / write  (prefixed with _ to avoid collision with
    // the static facade methods above)
    // ------------------------------------------------------------------

    /**
     * Retrieve a value using dot-notation.
     *
     * @param string $key     e.g. 'info.emailfrom' or a plain top-level key
     * @param mixed  $default Returned when the key does not exist
     */
    public function _get(string $key, mixed $default = null): mixed
    {
        // Fast path — exact top-level key
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        // Dot-notation traversal
        $segments = explode('.', $key);
        $value = $this->data;

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
     */
    public function _set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &$this->data;

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
    public function _has(string $key): bool
    {
        return $this->_get($key) !== null;
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
