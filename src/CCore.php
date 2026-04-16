<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author chanhong
 */
namespace MvcLite;
use PdoLite\PdoLite;

defined('_MVCLite') or die('Direct Access to this location is not allowed.');

class CCore {

    // -------------------------------------------------------------------------
    // Static (shared) properties — unchanged
    // -------------------------------------------------------------------------
    public static $_Err;
    public static $_cfg;
    public static $_action;
    public static $_profile;
    public static $_usrInfo;
    public static $loginUrl = '?login'; // Where to direct users to login
    static $LoggedIn;

    // -------------------------------------------------------------------------
    // DI Container — set once at application boot via CCore::setContainer()
    // -------------------------------------------------------------------------
    private static CContainer $container;

    /**
     * Call this in your front controller / index.php before anything else.
     *
     * Example:
     *   $c = new \MvcLite\Container();
     *   $c->singleton('db',     fn() => new \PdoLite\PdoLite());
     *   $c->singleton('util',   fn() => new \MvcLite\CUtil());
     *   $c->singleton('helper', fn() => new \MvcLite\CHelper());
     *   $c->singleton('auth',   fn() => \MvcLite\CAuth::getAuth('MvcLiteSALT'));
     *   $c->singleton('error',  fn() => \MvcLite\CError::getError());
     *   \MvcLite\CCore::setContainer($c);
     */
    public static function setContainer(CContainer $c): void
    {
        self::$container = $c;
    }

    public static function getContainer(): CContainer
    {
        if (!isset(self::$container)) {
            throw new \RuntimeException('CCore: container has not been set. Call CCore::setContainer() at boot.');
        }
        return self::$container;
    }

    // -------------------------------------------------------------------------
    // Instance properties — unchanged
    // -------------------------------------------------------------------------
    public $retUrl;
    public $Auth;
    public $Error;
    public $ut;
    public $h;
    public $db;
    public $meTable;

    protected $_view_data = [];
    public $model;
    public $controller;
    public $widget;

    protected $get;
    protected $post;
    public $_request;
    public $view_ext = 'php';
    public $layout;
    public $appsFolder;
    public $viewFolder;
    public $widgetFolder;
    public $vendorFolder;
    public $publicFolder;
    public $layoutsFolder;
    protected $_class_path;
    protected $pageTitle = [];
    protected $meta      = [];
    protected $arr       = [];
    protected $javascripts  = [];
    protected $stylesheets  = [];
    protected $styleless    = [];
    public $className;

    // -------------------------------------------------------------------------
    // Constructor — dependencies are now injected; container is the fallback;
    // raw "new" instantiation is the last resort so nothing breaks today.
    // -------------------------------------------------------------------------
    public function __construct(
        ?PdoLite $db    = null,
        ?CUtil   $ut    = null,
        ?CHelper $h     = null,
        mixed    $auth  = null,
        mixed    $error = null
    ) {
        CHelper::$_lineBreak = true;

        $this->db    = $db    ?? $this->resolve('db',     fn() => new PdoLite());
        $this->ut    = $ut    ?? $this->resolve('util',   fn() => new CUtil());
        $this->h     = $h     ?? $this->resolve('helper', fn() => new CHelper());
        $this->Auth  = $auth  ?? $this->resolve('auth',   fn() => CAuth::getAuth('MvcLiteSALT'));
        $this->Error = $error ?? $this->resolve('error',  fn() => CError::getError());

        $this->get  = $_GET;
        $this->post = $_POST;

        if ($this->className === null) {
            $this->className = get_class($this);
        }

        $this->_class_path = strtolower((new \ReflectionClass($this))->getShortName()); // "ClassName" — getShortName() works in PHP 8.x

        (!empty($_REQUEST['r'])) ? $this->retUrl = $_REQUEST['r'] : $this->retUrl = '?';
    }

    /**
     * Resolve a service: try the container first, fall back to $default factory.
     * This means all existing controllers work with zero changes even before
     * the container is registered.
     */
    private function resolve(string $id, callable $default): mixed
    {
        if (isset(self::$container)) {
            try {
                return self::$container->make($id);
            } catch (\RuntimeException) {
                // binding not registered yet — fall through to default
            }
        }
        return $default();
    }

    // -------------------------------------------------------------------------
    // Static helpers — unchanged
    // -------------------------------------------------------------------------

    protected static function shortClass(string $fqcn): string
    {
        return substr(strrchr($fqcn, '\\'), 1) ?: $fqcn; // same as (new ReflectionClass(...))->getShortName()
    }

    public static function debug($iVar, $iStr = '', $iFormat = '')
    {
        return CDebug::debug($iVar, $iStr, $iFormat); // show if _MVCDebug == true
    }

    public static function redirect2Url($ret2URL = null)
    {
        if (is_null($ret2URL)) {
            $ret2URL = $_SERVER['PHP_SELF'];
        }
        header('Location: ' . $ret2URL);
        exit;
    }

    public static function pln($iVar, $iStr = '', $iFormat = 'br')
    {
        print CUtil::debug($iVar, $iStr, $iFormat);
    }

    // -------------------------------------------------------------------------
    // Instance methods — unchanged
    // -------------------------------------------------------------------------

    public function getUser($usrname, $meTable)
    {
        $u = null;
        if (!empty($usrname)) {
            $where = "username ='$usrname'";
            $u = $this->fetchRow($meTable, ['where' => $where]);
        }
        return $u;
    }

    protected function fetchRow(string $table, array $options = []): mixed
    {
        return $this->db->dbRow($table, $options);
    }

    public function isAuthorized($validate, $where, $meTable)
    {
        if (!empty($validate) and !empty($where) and !empty($meTable)) {
            $this->Auth->logout();
            $userRow = $this->db->dbRow($meTable, ['where' => $where]);
            if ($this->Auth->login($validate, $userRow) <> null) {
                return $userRow;
            }
        }
    }
}
