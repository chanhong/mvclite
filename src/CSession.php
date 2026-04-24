<?php

namespace MvcLite;

/**
 * Dependency simulations for System.Web equivalents in PHP
 */

class HttpContext
{
    /** @var array */
    public $Session;

    /** @var HttpContext */
    public static $Current;

    public function __construct()
    {
        // In PHP, we link the Session property to the global $_SESSION superglobal
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $this->Session = &$_SESSION;
    }

    /**
     * Static initializer to mimic HttpContext.Current
     */
    public static function init()
    {
        if (self::$Current === null) {
            self::$Current = new self();
        }
        return self::$Current;
    }
}

/**
 * Base class for session state
 */
class HttpSessionStateBase implements \ArrayAccess
{
    protected $data;

    public function __construct(&$data)
    {
        $this->data = &$data;
    }

    public function offsetExists($offset): bool { return isset($this->data[$offset]); }
    public function offsetGet($offset): mixed { return $this->data[$offset] ?? null; }
    public function offsetSet($offset, $value): void { $this->data[$offset] = $value; }
    public function offsetUnset($offset): void { unset($this->data[$offset]); }
}

/**
 * Encapsulates the session state
 */
final class CSession
{
  public static $_ctx_s;

  private $_session;

  public function __construct($session)
  {
    $this->_session = $session;
  }

  public static function clearSession()
  {
// Start the session (required before modifying it)
//session_start();
if (session_status() == PHP_SESSION_NONE) {
session_start();
}
// 1. Unset all session variables
$_SESSION = array();

// 2. If using cookies, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000, // Expire in the past
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. Destroy the session data on the server
session_destroy();

// Optional: Redirect or confirm
//echo "Session cleared successfully.";
}
  /**
   * Static property LoggedInID
   */
  public static function getLoggedInID()
  {
    //                if (HttpContext.Current.Session["LoggedInID"] != null && HttpContext.Current.Session["LoggedInID"].ToString().Length > 0)
    if (isset(self::$_ctx_s->Session["LoggedInID"]) && strlen((string)self::$_ctx_s->Session["LoggedInID"]) > 0)
    {
      //                    return Convert.ToInt32(HttpContext.Current.Session["LoggedInID"]);
      return (int)self::$_ctx_s->Session["LoggedInID"];
    } else
    {
      return 0;
    }
  }

  public static function setLoggedInID($value)
  {
    //                HttpContext.Current.Session["LoggedInID"] = value;
    self::$_ctx_s->Session["LoggedInID"] = $value;
  }

  /**
   * Property Username
   */
  public function getUsername()
  {
    return (string)($this->_session["usrname"] ?? "");
  }

  public function setUsername($value)
  {
    $this->_session["usrname"] = $value;
  }

  /**
   * Property FullName
   */
  public function getFullName()
  {
    return (string)($this->_session["name"] ?? "");
  }

  public function setFullName($value)
  {
    $this->_session["name"] = $value;
  }

  /**
   * Property ID
   */
  public function getID()
  {
    return (int)($this->_session["UID"] ?? -1);
  }

  public function setID($value)
  {
    $this->_session["UID"] = $value;
  }

  /**
   * Property DMsg
   */
  public function getDMsg()
  {
    // not test, appstate is better
    return (string)($this->_session["fbdmsg"] ?? "");
  }

  public function setDMsg($value)
  {
    $this->_session["fbdmsg"] = $value;
  }

  /**
   * Magic method to allow property-like access for instance members as in C#
   */
  public function __get($name)
  {
    $method = "get" . $name;
    if (method_exists($this, $method)) {
      return $this->$method();
    }
    return null;
  }

  public function __set($name, $value)
  {
    $method = "set" . $name;
    if (method_exists($this, $method)) {
      $this->$method($value);
    }
  }
}

// Static initialization equivalent to: public static HttpContext _ctx_s = HttpContext.Current;
CSession::$_ctx_s = HttpContext::init();

