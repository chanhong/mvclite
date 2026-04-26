<?php

/**
 * CSetting — Runtime / per-request state container.
 *
 * Stores values that change on every request (current route, parsed query
 * string, active controller/action, authenticated user, etc.) as opposed to
 * the static configuration that lives in CConfig.
 *
 * Supports the same dot-notation API as CConfig so call-sites are consistent:
 *
 *   $stg->set('cur.ctrl',   'front');
 *   $stg->set('cur.action', 'index');
 *   $stg->get('cur.ctrl');              // 'front'
 *   $stg->get('qs.page',  1);           // query-string param with default
 *   $stg->has('auth.user');             // bool
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

    // CSetting.php  
// CSetting.php — change these two bridge declarations
    public ?array $cur = [];
    public ?array $qs = [];

    public static $_profile;
    public static $_usrInfo;
    public static $uinfo;
    public static $LoggedIn;


    protected array $data = [];

    // ------------------------------------------------------------------
    // Construction
    // ------------------------------------------------------------------

    public function __construct()
    {
        $this->data = [
            '_usrinfo' => [],   // user info (old)
            'uinfo' => [],   // user info, use this going forward
            'cur' => [],   // current route / dispatch info
            'qs' => [],   // current query-string parameters
            'auth' => [],   // authenticated user snapshot
            'flash' => [],   // one-time flash messages
        ];
    }

    // ------------------------------------------------------------------
    // Core read / write  (mirrors CConfig API)
    // ------------------------------------------------------------------

    /**
     * Retrieve a value using dot-notation.
     *
     * @param string $key     e.g. 'cur.ctrl' or a plain top-level key
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
    public function set(string $key, mixed $value): void
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
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
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
