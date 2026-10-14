<?php

/**
 * CSetting — Runtime / per-request state container.
 *
 * Stores values that change on every request (current route, parsed query
 * string, active controller/action, authenticated user, etc.) as opposed to
 * the static configuration that lives in CConfig.
 *
 * INSTANCE usage (DI / new code):
 *   $stg->_get('cur.ctrl');
 *   $stg->_set('cur.ctrl', 'front');
 *   $stg->_has('auth.user');
 *
 * STATIC / FACADE usage (boot-time or legacy static code):
 *   CSetting::get('cur.ctrl');   // works before AND after DI is ready
 *   CSetting::set('cur.action', 'index');
 *   CSetting::has('auth.user');
 *
 * The facade delegates to the instance once setInstance() is called
 * (after DI container is built in index.php).  Before that it falls
 * back to the static $_stg bridge array, so boot-time code works
 * without any changes.
 *
 * @author chanhong
 */

namespace MvcLite;

defined('_MVCLite') or die('Direct Access to this location is not allowed.');

class CSetting
{
    // ------------------------------------------------------------------
    // Well-known top-level buckets (self-documenting; not enforced)
    // ------------------------------------------------------------------
    //   cur  — current route info  (ctrl, action, id, …)
    //   qs   — parsed query-string parameters
    //   auth — authenticated user info
    //   flash— one-time flash messages
    // ------------------------------------------------------------------
    public ?array $cur = [];
    public ?array $qs = [];

    public static $_profile;
    public static $_usrInfo;
    public static $uinfo;
    public static $LoggedIn;

    // ------------------------------------------------------------------
    // Facade — holds the singleton instance once DI is ready
    // ------------------------------------------------------------------
    private static ?self $instance = null;

    /**
     * Call once in index.php after initDI():
     *   CSetting::setInstance($container->make('stg'));
     */
    public static function setInstance(self $stg): void
    {
        static::$instance = $stg;
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
        $value = static::$_stg;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
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
        $target = &static::$_stg;
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
    // Static bridge — kept ONLY for legacy code that reads CSetting::$_stg
    // directly, and as the pre-DI fallback store for the facade above.
    // Remove once all call-sites are migrated.
    // ------------------------------------------------------------------
    public static array $_stg = [];

    protected array $data = [];

    // ------------------------------------------------------------------
    // Construction
    // ------------------------------------------------------------------

    public function __construct(array $stgArray = [])
    {
        $this->data = [
            '_usrinfo' => [],   // user info (old)
            'uinfo' => [],   // user info, use this going forward
            'cur' => [],   // current route / dispatch info
            'qs' => [],   // current query-string parameters
            'auth' => [],   // authenticated user snapshot
            'flash' => [],   // one-time flash messages
        ];

        if (!empty($stgArray)) {
            $this->data = array_merge($this->data, $stgArray);
        }
    }

    // ------------------------------------------------------------------
    // Instance read / write  (prefixed with _ to avoid collision with
    // the static facade methods above)
    // ------------------------------------------------------------------

    /**
     * Retrieve a value using dot-notation.
     *
     * @param string $key     e.g. 'cur.ctrl' or a plain top-level key
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

    /** Replace the entire data store at once. */
    public function setAll(array $data): void
    {
        $this->data = $data;
    }

    /** Return the entire data store. */
    public function getAll(): array
    {
        return $this->data;
    }

    // ------------------------------------------------------------------
    // Convenience shortcuts for the most common buckets
    // ------------------------------------------------------------------

    /** Read/write the current-route bucket as a whole. */
    public function getCur(): array
    {
        return $this->data['cur'] ?? [];
    }
    public function setCur(array $v): void
    {
        $this->data['cur'] = $v;
    }

    /** Read/write the query-string bucket as a whole. */
    public function getQs(): array
    {
        return $this->data['qs'] ?? [];
    }
    public function setQs(array $v): void
    {
        $this->data['qs'] = $v;
    }

    // ------------------------------------------------------------------
    // Flash message helpers
    // ------------------------------------------------------------------

    /**
     * Add a flash message.
     * @param string $type  e.g. 'success', 'error', 'info'
     */
    public function flash(string $type, string $message): void
    {
        $this->data['flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Return all flash messages and clear them (consume once).
     */
    public function pullFlash(): array
    {
        $messages = $this->data['flash'] ?? [];
        $this->data['flash'] = [];
        return $messages;
    }
}
