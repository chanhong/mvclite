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

class CCore
{

    // -------------------------------------------------------------------------
    // Static (shared) properties — unchanged
    // -------------------------------------------------------------------------

    public static $_usr;
    public static $_usrs;
    public static $_uprf;
    public static $_Err;
    public static $_tg;
    public static $_cfg;
    public static $_stg;
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
    public $cfg;
    public $stg;

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
    protected $meta = [];
    protected $arr = [];
    protected $javascripts = [];
    protected $stylesheets = [];
    protected $styleless = [];
    public $className;

    // -------------------------------------------------------------------------
    // Constructor — dependencies are now injected; container is the fallback;
    // raw "new" instantiation is the last resort so nothing breaks today.
    // -------------------------------------------------------------------------
    /*
    $ref = new ReflectionClass($className);
$params = $ref->getConstructor()->getParameters();

$args = [];
foreach ($params as $param) {
    $type = $param->getType()->getName(); // e.g. "PdoLite", "CUtil"
    $args[] = $this->get($type);          // resolve from bindings
}

return $ref->newInstanceArgs($args);

see public\index.php step 5-8
              */
    public function __construct(
        ?PdoLite $db = null,
        ?CUtil $ut = null,
        ?CHelper $h = null,
        mixed $auth = null,
        mixed $error = null,
        mixed $cfg = null,
        mixed $stg = null
    ) {
        CHelper::$_lineBreak = true;

        $this->db = $db ?? $this->resolve('db', fn() => new PdoLite());
        $this->ut = $ut ?? $this->resolve('util', fn() => new CUtil());
        $this->h = $h ?? $this->resolve('helper', fn() => new CHelper());
        $this->Auth = $auth ?? $this->resolve('auth', fn() => CAuth::getAuth('MvcLiteSALT'));
        $this->Error = $error ?? $this->resolve('error', fn() => CError::getError());

        $this->cfg = $cfg ?? $this->resolve('cfg', fn() => new CConfig());
        $this->stg = $stg ?? $this->resolve('stg', fn() => new CSetting());


        $this->get = $_GET;
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

    public function dbIsAuthorized($validate, $where, $meTable)
    {
        if (!empty($validate) and !empty($where) and !empty($meTable)) {
            $this->Auth->logout();
            $userRow = $this->db->dbRow($meTable, ['where' => $where]);
            if ($this->Auth->login($validate, $userRow) <> null) {
                return $userRow;
            }
        }
    }
    public static function SetView(string $sView, string $sTask = "")
    {
        $ret = "";
        $vPath = CSetting::get(("viewpath"));
        if ($sTask != "") {
            $vPath = $vPath . "/" . $sTask;
        }

        $fname = $vPath . "/" . $sView . CSetting::get(("viewext"));
        $fullFilePath = CFiles::RealFilePath($fname);
        if (file_exists($fullFilePath)) {
            $ret = $fname;
        }
        return $ret;
    }

    public static function getAppTxt(string $fb = "fbdmsg", string $lower = ""): string
    {
        $ret = CCore::$_cfg['app'][$fb]
            ?? CCore::$_cfg['info'][$fb]
            ?? CCore::$_cfg[$fb]          // top-level cfg key
            ?? CCore::$_stg[$fb]          // top-level stg key (logintype, weblogin etc.)
            ?? "";

        // unwrap single-element arrays from legacy format ["web"] → "web"
        if (is_array($ret)) {
            $ret = $ret[0] ?? "";
        }

        if (!CString::IsEmpty($lower)) {
            $ret = strtolower($ret);
        }
        //pln($ret, "getAppTxt-$fb");
        return $ret;
    }
    public static function qs2nvWithDefaultValue()
    {
        $tak = CSetting::get('takey'); // split , to array // Passing null to parameter #1 ($string)
        $kArray = explode(',',$tak) ?? []; // split , to array
        $defCtrl = strtolower(CSetting::get('defctrl')); //Passing null to parameter #1 ($string)
        $defView = strtolower(CSetting::get('defview')); //Passing null to parameter #1 ($string)
        $qsa = CUtil::qs2nv();

        if ($qsa != null) {
            if ($qsa[$kArray[0]] != null) // task
            {
                $qsa[$kArray[0]] = strtolower($qsa[$kArray[0]]);
            }
            if (isset($qsa[$kArray[1]])) // action
            {
                $qsa[$kArray[1]] = strtolower($qsa[$kArray[1]]);
            }
        } else {
            $nv = [];
            // set default controller and view
            $nv[$kArray[0]] = $defCtrl; // task
            $nv[$kArray[1]] = $defView; // action
            $qsa = $nv;
        }

        // if no value set the default 
        if ($qsa[$kArray[0]] == null) // task
        {
            $qsa[$kArray[0]] = $defCtrl;
        }
        if (isset($qsa[$kArray[1]]) && $qsa[$kArray[1]] == null) // action
        {
            $qsa[$kArray[1]] = $defView;
        }
//        pln($qsa, "qs2nvWithDefaultValue");
        return $qsa;
    }
    public static function getSelectedViewSet()
    {
        $selCtrl = strtolower(CSetting::get(("selctrl")));
        //                pln($selCtrl, "getSelectedViewSet-selctrl-return");
        return $selCtrl;
    }

    public static function not_used_SetMenuTop(string $task = "") // need to write to CSetting->_cfg instead of CCore or CConfig
    {
        $fa = [];
        $mnuLinks = $mnu_apps = [];
        if ($task == "") {
            $task = CSetting::get("selctrl");
        }
        $mnuHome = CCore::$_cfg["mnuhome"] ?? [];
        $mnuCommon = CCore::$_cfg["mnucommon"] ?? [];
        //        $mnu_apps = CCore::$_cfg["mnu_" . $task];
        $fa = explode(',', CSetting::get('apps') );       
        if ($fa != null) { // exclude _app
            //        fa.Remove(getAppTxt("defctrl")); // remove default controller topmenu, later?
            $mnu_apps = CUtil::sName2Mnu($fa); // app top submenu
            $mnuLinks = array_merge($mnuHome, $mnuCommon, $mnu_apps);
        }
        CSetting::set('mnutop', $mnuLinks);
        //        pln(CSetting::get("mnutop"), "mnutop");
        return CSetting::$_stg["mnutop"];
    }

    public static function IsLoginedUser()
    {
        pln("IsLoginedUser", "called");
        $ret = "";
        $usrname = CUtil::getSessTxt("usrname");
        $usrHash = "";
        $app = strtolower(CSetting::get(("defctrl")));
        $sapp = CSetting::get("urlsite") . CSetting::get("siteroot") . "_" . $app;

        $usrCookie = CUtil::Cookies_Get($sapp . "_id_hash");
        $usrnameCookie = CUtil::Cookies_Get($sapp . "_usrname");
        $usrpwCookie = CUtil::Cookies_Get($sapp . "_usrpw");
        /*
        CMsg::_msg($usrCookie, "usrCookie");
        CMsg::_msg($usrname, "usrname");
        CMsg::_msg($usrnameCookie, "usrnameCookie");
        CMsg::_msg($usrpwCookie, "usrpwCookie");
        CMsg::_msg($sapp, "sapp");
  */
        if (!CString::IsEmpty($usrname)) {
            $ret = $usrname; // usrname from session, already login, skip cookie
        } else if (
            !CString::IsEmpty($usrnameCookie) && !CString::IsEmpty($usrCookie)
        ) // if cookie??
        {
            $appCookie = strtolower(CUtil::Cookies_Get("appid"));
            $usrHash = CUtil::Cookie_Hash($usrnameCookie);
            CMsg::_msg($usrHash, "usrHash");
            CMsg::_msg($appCookie, "appCookie");
            if (!CString::IsEmpty($usrHash) && $usrHash == $usrCookie) // compare to usrname from cookie = hashed cookie, restore cookie
            {
                // restore credential from cookie, not all env are restored, need more thinking?????
                $entityCookie = CUtil::Cookies_Get($sapp + "_usrentity");
                CMsg::_msg($entityCookie, "entityCookie");
                CSecs::setUserLoginInfo($app, $usrnameCookie, $entityCookie); // set _usr from cookie
                $usr = CUtil::getSessNv("uinfo");
                $loggedin = CSecs::setUsrInfoText();
                if (!CString::IsEmpty($loggedin)) {
                    $ret = $usrCookie; // restore usrname from session or cookie
                    $msg = "You are login as " + $loggedin; // this set the variable for a successful login
                    static::$_usr["loggedin"] = $loggedin; // set loggedin for the current user session
                    $_SESSION["loggedin"] = $loggedin; // also set loggedin in session for consistency and future checks
                    //        pln(static::$_usr["loggedin"], "IsLoginedUser-loggedin");
                    CUtil::Add2SessVar("feedback", $msg);
                    //        pln($msg, "IsLoginedUser-msg");
//                    die;
                    CUtil::Redirect(CUtil::getReturnUrl());   // redirect from the login screen             
                } else {
                    CMsg::_msg($usr, "IsLoginedUser-loggedin-else");
                }
            } else {
                CMsg::_msg($usrHash, "IsLoginedUser-usrHash-else");
                CMsg::_msg($appCookie, "IsLoginedUser-appCookie-else");
            }
        }
        //        pln($ret, "IsLoginedUser-return");
        return $ret;
    }
    public static function GetUsrName()
    {
        $ret = CUtil::getSessTxt("usrname"); // use session instead of static _usr
        //      CMsg::_msg($ret, "GetUsrName-b");

        // _usr set at login old code, for ref
        static::$_usr = CUtil::getSessNv("uinfo"); // use session instead of static _usr
        if (isset(CCore::$_usr["usrname"]) && CCore::$_usr != null && !CString::IsEmpty(CCore::$_usr["usrname"])) {
            $ret = CCore::$_usr["usrname"];
        }
        ;
        //     CMsg::_msg($ret, "GetUsrName-a");
        return $ret;
    }
    // =============================================
// ClearUser
// =============================================
    public static function ClearUser()
    {
        // Do NOT use session_destroy() or session_unset() if you still need debug messages
        unset($_SESSION['usrname']);
        unset($_SESSION['uinfo']);
        // unset($_SESSION['_uprf']);   // commented in original
        unset($_SESSION['loggedin']);
        unset($_SESSION['uinfotxt']);

        CCore::$_cfg['logoff'] = '';   // reset
        CCore::$_uprf = null;
        CCore::$_usr = null;
    }
    public static function CLogin($frm)
    {
        $ret = false;
        $msg = "Please login!";
        $app = "";
        $usrname = "";
        $usrentity = "";
        $loginType = "";
        $pw = "";
        //      CMsg._dmsg($frm, "_Login-frm");
        CUtil::Cookie_Usr(); // clear cookie

        if ($frm != null && count($frm) > 0 && CString::IsEmpty($frm["logintype"]) == false) {
            //        CUtil::Add2SessVar("feedback", "");
            $app = $frm["appid"];
            $usrname = $frm["user_name"];
            $usrentity = (isset($frm["uentity"]) && $frm["uentity"] != null && strlen($frm["uentity"]) > 0) ? $frm["uentity"] : "";
            $pw = $frm["password"];
            $loginType = strtolower($frm["logintype"]);
            CSecs::setUserLoginInfo($app, $usrname, $usrentity); // set _usr 
            $_usr = CUtil::getSessNv("uinfo"); // use session instead

            CMsg::_dmsg($_usr, "_Login");
            if (
                $_usr != null && CString::IsEmpty($_usr["usrname"]) == false
                //          && $usrname == $_usr["usrname"] // why?
            ) {
                if ($loginType == "web" && CString::IsEmpty($pw) == false) {
                    $hash = CSecs::GetMd5Hash($pw);
                    /*
                    CMsg::_dprt($hash, "hash");
                    CMsg::_dprt($_usr["usrpw"], "usrhash");
                    CMsg::_dmsg($pw, "pw");
                    CMsg::_dmsg($hash, "hash");
                    CMsg::_dmsg($_usr["usrpw"], "usrpw");
         */
                    if ($hash == $_usr["usrpw"]) {
                        $_usr["litype"] = $loginType;
                        //              CMsg::_dmsg($_usr, "login-web");
                        $ret = true;
                    } else {
                        //              CMsg::_dprt((NameValueCollection)_usr, "_Login-_usrFailed");
                        CMsg::_dmsg($_usr, "login-web-hash failed");
                        CUtil::Add2SessVar("alert", "Failed password validation!");
                    }
                } else if ($loginType == "win" && CString::IsEmpty(pw)) {
                    if ($usrname == CSecs::winUser()) {
                        $_usr["litype"] = $loginType;
                        //              CMsg::_dmsg($_usr, "login-win");
                        $ret = true;
                    }
                }
            }

            if ($ret == true) {
                $loggedin = CSecs::setUsrInfoText();
                $msg = "You are login as " . $loggedin; // this set the variable for a successful login
                $_usr['level'] = $_usr['usrgroup'];
                $_SESSION["uinfo"] = $_usr; // get litype into session
                $_SESSION['uinfo']['level'] = $_usr['usrgroup'];   // patch for PHP users login access       
                $_SESSION["loggedin"] = $loggedin; // also set loggedin in session for consistency and future checks
                static::$_usr["loggedin"] = $loggedin; // set loggedin for the current user session
                CMsg::_dmsg($_usr, $msg);
                CUtil::Cookie_Usr($_usr);
                CUtil::Add2SessVar("feedback", $msg);
            }
        } else {
            CUtil::Add2SessVar("alert", $msg);
        }
        return $ret;
    }

}
