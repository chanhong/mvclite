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


class CUtil
{
    public $h;

    public $retUrl;
    public $Auth;
    public $Error;
    public $db;
    public $meTable;
    public $className;
    // Static bridge — works before DI is ready
    protected static ?CUtil $_instance = null;

    protected CConfig $cfg;
    protected CSetting $stg;

    public function __construct(
        ?CConfig $cfg = null,
        ?CSetting $stg = null
    ) {
        $this->cfg = $cfg ?? new CConfig([]);
        $this->stg = $stg ?? new CSetting();
    }

    // ---------------------------------------------------------------------------
    // Facade wiring — called from index.php after container is built
    // ---------------------------------------------------------------------------

    public static function setInstance(CUtil $instance): void
    {
        self::$_instance = $instance;
    }

    public static function getInstance(): static
    {
        return self::$_instance ??= new static();  // fallback if DI not ready
    }

    // ---------------------------------------------------------------------------
    // Example: mixed static/instance method
    // Old static callers keep working, new DI callers use $this
    // ---------------------------------------------------------------------------

    public static function someMethod(): string
    {
        return self::getInstance()->_someMethod();  // static shell delegates to instance
    }

    public function _someMethod(): string
    {
        return $this->cfg->_get('app.name');        // instance uses injected $cfg
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

    public static function debug($iVar, $iStr = "", $iFormat = "")
    {
        return CDebug::debug($iVar, $iStr, $iFormat); // show if _MVCDebug == true
    }
    public static function _debug($iVar, $iStr = "", $iFormat = "")
    {
        return CDebug::_debug($iVar, $iStr, $iFormat); // show if _MVCDebug == true
    }

    public static function delete_cutil_debug($iVar, $iStr = "", $iFormat = "") // move to CDebug, not use, delete
    {
        // Check if debug is enabled via _DEBUG_ENABLED flag

        if (!defined('_DEBUG_ENABLED') || !_DEBUG_ENABLED) {
            return null; // Debug disabled, skip all logging
        }

        $str = $dTrace = "";

        (!empty($iStr) and strtolower($iStr) == "dtrace") ? $dTrace = "dtrace" : $dTrace = "";
        (!empty($iStr) and strtolower($iStr) <> "dtrace") ? $preText = "[-" . strtoupper($iStr) . "-] " : $preText = "";
        if (!empty($iVar)) {
            if (is_array($iVar) or (is_object($iVar)))
                $iVar = print_r($iVar, true);
            if (!empty($dTrace))
                $dTrace = self::dTrace();
            (empty($iFormat)) ? $str = $preText . $iVar : $str = "<pre>" . $preText . $iVar . "</pre>";
        }
        $ret = $str . $dTrace . " ";

        // Write to file log
   $logDir = (string) (CSetting::get('logdir') ?: dirname(dirname(dirname(dirname(__DIR__)))) . '/db/logs');  
   pln($logDir,'ldir');      
//        $logDir = dirname(dirname(dirname(dirname(__DIR__)))) . '/db/logs'; // 4 dirname to get to root from Lib/mvclite/src
        @mkdir($logDir, 0775, true); // Create logs directory if it doesn't exist
        $logFile = $logDir . '/debug_' . date('Y-m-d') . '.log';
        $fileHandle = @fopen($logFile, 'a');
        if ($fileHandle) {
            fwrite($fileHandle, date('Y-m-d H:i:s') . " - " . $ret . "\n");
            fclose($fileHandle);
        }

        // Limit session debug to screen-full amount to prevent clutter - reset periodically
        $screenFullLines = 20; // Reset after ~20 lines (typical screen height)
        $maxScreenSize = 25600; // ~25KB per screen for on-screen display

        if (empty($_SESSION['dmsg'])) {
            $_SESSION['dmsg'] = $ret;
            $_SESSION['debug_resets'] = 0;
            $_SESSION['debug_logs'] = [];
        } else {
            $currentSize = strlen($_SESSION['dmsg']);
            $lineCount = substr_count($_SESSION['dmsg'], "\n");

            // Reset when approaching screen-full
            if ($currentSize >= $maxScreenSize || $lineCount >= $screenFullLines) {
                // Log the debug session to array before resetting
                if (empty($_SESSION['debug_logs'])) {
                    $_SESSION['debug_logs'] = [];
                }
                $_SESSION['debug_logs'][] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'line_count' => $lineCount,
                    'size_kb' => round($currentSize / 1024, 2),
                    'reset_num' => (int) $_SESSION['debug_resets'] + 1
                ];

                // Keep only last 10 debug logs to prevent array bloat
                if (count($_SESSION['debug_logs']) > 10) {
                    array_shift($_SESSION['debug_logs']);
                }

                // Reset on-screen debug
                $_SESSION['dmsg'] = "[SCREEN RESET - " . (int) $_SESSION['debug_resets'] + 1 . " | " . $lineCount . " lines logged]\n" . $ret;
                $_SESSION['debug_resets'] = (int) $_SESSION['debug_resets'] + 1;
            } else {
                $_SESSION['dmsg'] .= $ret;
            }
        }
        return $ret;
    }

    public static function dTrace()
    {

        $str = "<br />[dTrace]";
        foreach (debug_backtrace() as $row) {
            $str .= "<br />FILE: " . $row['file'] . " FUNC: " . $row['function'] . " LINE: " . $row['line'] . " ARGS: " . print_r($row['args'], true);
        }
        return $str;
    }

    public static function getURI()
    {
        return CUrl::getURI();
    }

    public static function relRoot($adj = "")
    {
        return CUrl::relRoot($adj);
    }

    public static function rootPath($path = "")
    {
        return CUrl::rootPath($path);
    }

    public static function rootSite()
    {
        return CUrl::rootSite();
    }

    public static function siteURL()
    {
        return CUrl::siteURL();
    }

    public static function selfURL()
    {
        return CUrl::selfURL();
    }

    function array2InsStr($iArray)
    {

        $value = '"' . implode('", "', array_values($iArray)) . '"'; // must use this in case quote in the name
        $name = implode(", ", array_keys($iArray));
        // return (jvid, title, maker, explanation, acctmo, prep_date, phone ) VALUES ("1","Title","Maker","Explanation","15","2/11/2011","87878")        
        return "($name) VALUES ($value)";
    }

    function array2UptStr($iArray, $checkNumArray = array())
    {

        $str = "";
        foreach ($iArray as $key => $val) {
            if (isset($checkNumArray[$key]) and $key == $checkNumArray[$key] and empty($val)) {
                $val = "0"; // set to "0" only in the $checkNumArray and is empty
            }
            $str .= $key . ' ="' . $val . '", ';
        }
        // return maker= 'Name', acctmo= '15', prep_date= '11/2/2011', phone= '87878', explanation= 'Some Text', title='Some Title'
        return substr($str, 0, strlen($str) - 2); // take out comma and space
    }

    function array2Str($iArray)
    {

        $str = "";
        foreach ($iArray as $key => $val) {
            if (empty($val))
                $val = "0"; // set to 0 if null
            $str .= $val . ", ";
        }
        return substr($str, 0, strlen($str) - 2); // take out comma and space
    }

    function splitArray($jvArchiveDir, $type)
    {

        $fileArray = $folderArray = array();
        foreach ($jvArchiveDir as $fspec) {
            $realfile = realpath($fspec);
            if (is_dir($realfile) and file_exists($realfile)) {
                $folderArray[] = $fspec;
            } else {
                $filename = basename($fspec);
                if (strtolower(basename($filename)) <> "thumbs.db") {
                    $fileArray[] = $fspec;
                }
            }
        }
        ($type == "file") ? $ret = $fileArray : $ret = $folderArray;
        return $ret;
    }

    public static function getSafeVar($iVar, $name, $itype = "txt", $retchar = "")
    {

        $ret = "";
        if (!empty($iVar["$name"])) {
            $ret = self::clean($iVar["$name"], $itype, $retchar);
        }
        if (empty($ret) and !empty($retchar)) {
            $ret = $retchar;
        }
        return $ret;
    }


    function slug($z)
    { // remove anything that not in the list
        $z = strtolower(trim($z));
        $z = preg_replace('/[^a-z0-9 \'-]+/', '', $z);
        return $z;
    }

    public static function clean($str, $itype = "txt", $retchar = "")
    {
        return CString::Clean($str, $itype, $retchar);
    }

    /**
     * Sanitizes a value using the legacy application's named conversion modes.
     */
    public static function cleanStr($iStr, $iType = "txt", $rChar = "")
    {
        return CString::CleanStr($iStr, $iType, $rChar);
    }

    public static function evenOrOdd(int $iCnt): string
    {
        return $iCnt % 2 === 0 ? "Even" : "Odd";
    }

    private static function stripSpecChars(string $value): string
    {
        return CString::StripSpecChars($value);
    }

    private static function escQote(string $value): string
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
    }


    function cleanArray($iVar)
    {

        foreach ($iVar as $k => $v) {
            $eachpost[$k] = self::getSafeVar($iVar, $k);
        }
        return $eachpost;
    }

    function cleanAmt($amt)
    {

        $ret = sprintf("%1.2f", self::clean($amt) / 100, "num");
        if (strlen($ret) > 11)
            $ret = 0;
        return $ret;
    }

    function getParm($iVar)
    {

        return strtolower(self::getSafeVar($_GET, $iVar));
    }

    function getLdapByType($iType = 'email', $iValue = null)
    {

        if (!class_exists('CLdap'))
            include("cldap.php");
        $ldap = new CLdap();
        $retArray = $ldap->$iType($iValue);
        return $retArray;
    }

    function sendAttachment($subject, $sendto, $replyto, $message, $htmlfile)
    {

        $mimetype = "text/plain";
        $mailfile = new CMailfile($subject, $sendto, $replyto, $message, $htmlfile, $mimetype);
        $mailfile->sendfile();
    }

    function trimSpaces($str)
    {

        while (sizeof($array = explode("  ", $str)) != 1) { // trim any where not just begin or ending
            $str = implode(" ", $array);
        }
        return $str;
    }

    function expiredCookie()
    {

        setcookie('id_hash', '', (time() - 3600), '/', '', 0); // 1 hr ago, expired cookie
        session_unset(); // move here from logout.php
    }

    function user_getname()
    {

        if (!empty($_SESSION['LOGGED_IN'])) {
            return $GLOBALS['user_name'];
        } else {
            //look up the user some day when we need it
            return ' ERROR - Not Logged In ';
        }
    }

    function fwriteStream($fp, $string)
    {

        for ($written = 0; $written < strlen($string); $written += $fwrite) {
            $fwrite = fwrite($fp, substr($string, $written));
            if ($fwrite === false) {
                return $written;
            }
        }
        return $written;
    }

    function YN($str)
    {

        ($str == 'Y') ? $str = 'N' : $str = 'Y';
        return $str;
    }

    function properName($str)
    {

        (!empty($str)) ? $ret = ucwords(trim(strtolower($str))) : $ret = "";
        return $ret;
    }

    function padHtml($strInput = "", $strLength = 0, $padStr = "&nbsp;", $padType = STR_PAD_RIGHT)
    {

        $return = trim(strip_tags($strInput));
        if (strlen($return) < intval($strLength)) {
            switch ($padType) {
                case 0: // LEFT
                    $offsetLeft = intval($strLength - strlen($return));
                    $offsetRight = 0;
                    break;
                case 2: // BOTH
                    $offsetLeft = intval(($strLength - strlen($return)) / 2);
                    $offsetRight = round(($strLength - strlen($return)) / 2, 0);
                    break;
                default:
                case 1: // RIGHT
                    $offsetLeft = 0;
                    $offsetRight = intval($strLength - strlen($return));
                    break;
            }
            $return = str_repeat($padStr, $offsetLeft) . $return . str_repeat($padStr, $offsetRight);
        }
        return $return;
    }

    public static function fDirName($fspec)
    {
        return CFiles::fDirName((string) $fspec);
    }

    public static function fName($fspec)
    {
        return CFiles::fName((string) $fspec);
    }

    public static function fExt($fspec)
    {
        return CFiles::fExt((string) $fspec);
    }

    function virtualFolder2RealFolder($folderPath)
    {

        if (!empty($folderPath)) {
            $chkFolderPathArray = explode("/", $folderPath);
            $folderPathSpec = implode("/", $chkFolderPathArray);
            if (!empty($folderPathSpec) and $folderPathSpec <> "/") {
                $folderPath = realpath($folderPathSpec);
            }
        }
        return $folderPath;
    }

    function realFolder2VirtualFolder($dirname, $realFolder2Hide)
    {

        $fpath = strtolower($dirname);
        $pos = strpos($fpath, $realFolder2Hide);
        $chkFolderPathArray = explode("/", substr($fpath, $pos + strlen($realFolder2Hide)));
        $return = substr(implode("/", $chkFolderPathArray), 1); // skip the first /
        return $return;
    }

    function winUser()
    {

        $amcid = "";
        $eadUserName = $_SERVER['LOGON_USER']; // get Windows User ID without domain name
        if (!empty($eadUserName)) {
            $winUser = explode("\\", $eadUserName);
            array_shift($winUser);
            list($amcid) = $winUser;
        }
        return $amcid; // get Windows User ID without domain name
    }

    function uploadFiles($files, $targetFolder, $allowedExtensions)
    {

        $msg = "";
        $i = 0;
        $asize = count($files['name']);
        while ($i <= $asize) {
            if (!empty($files['name'][$i])) {
                $file = array($files['name'][$i], $files['type'][$i], $files['tmp_name'][$i], $files['error'][$i], $files['size'][$i]);
                $msg .= self::uploadFile($file, $targetFolder, $allowedExtensions);
            }
            $i++;
        }
        return $msg;
    }

    function uploadFile($file, $targetFolder, $allowedExtensions)
    {

        list($fileName, $fileType, $fileTemp, $fileErr, $fileSize) = $file;
        if ($fileErr == UPLOAD_ERR_OK) {
            if (self::isAllowedExtension($fileName, $allowedExtensions)) {
                $target = $targetFolder . "/" . $fileName;
                if (file_exists($target)) {
                    $ret = "$fileName is already exists.<br />";
                } else {
                    move_uploaded_file($fileTemp, $target);
                    $ret = "$fileName has been uploaded!<br />";
                }
            } else {
                $ret = "Invalid file type<br />";
            }
        } else {
            $ret = "Upload failed with unexpected error!  Return Code: " . $fileErr;
        }
        return $ret;
    }

    function isAllowedExtension($fileName, $allowedExtensions)
    {

        $fArray = explode(".", $fileName);
        return in_array(end($fArray), $allowedExtensions);
    }

    public static function splitWords($string, $max = 1)
    {
        return CString::splitWords((string) $string, (int) $max);
    }

    public static function splitLongLine($string, $maxWords = 96)
    { // fit 1024 screen size
        return CString::splitLongLine((string) $string, (int) $maxWords);
    }

    public static function delTree($dir)
    {
        CFiles::delTree((string) $dir);
    }

    public static function getDateArrayFromFiles($listDir)
    {
        return CDate::getDateArrayFromFiles($listDir);
    }

    public static function getFirstLastDate($dateA, $dFormat = "Y/m/d")
    {
        return CDate::getFirstLastDate($dateA, $dFormat);
    }

    public static function GetDate(string $sDte = "")
    {
        return CDate::GetDate($sDte);
    }

    public static function GetDateExact(string $fmt = "YmdHis", string $sDte = "")
    {
        return CDate::GetDateExact($fmt, $sDte);
    }

    public static function Dte2s(string $fmt = "", string $sDte = "")
    {
        return CDate::Dte2s($fmt, $sDte);
    }

    public static function DateAfterRetention(int $retentionYears = 6)
    {
        return CDate::DateAfterRetention($retentionYears);
    }

    public static function FinYearBegDate(int $FYStart = 7): string
    {
        return CDate::FinYearBegDate($FYStart);
    }

    public static function dateAdd($iDate, $iDays, $dateFormat = 'Y/m/d')
    {
        $dt = CDate::GetDate((string) $iDate);
        return $dt->modify("+$iDays days")->format($dateFormat);
    }

    function br2nl($string)
    {

        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }

    // must test this

    public static function escapeStr($inp)
    {
        return CString::EscapeStr($inp);
    }

    // Fixes MAGIC_QUOTES
    function fixSlashes($arr = '')
    {

        if (is_null($arr) || $arr == '')
            return null;
        if (!get_magic_quotes_gpc())
            return $arr;
        return is_array($arr) ? array_map(__METHOD__, $arr) : stripslashes(trim($arr));
    }

    public static function add2SessVar($iVar, $msg) // self:: in CUtil class is fine, outsie will trigger depreciated warning
    {
        CSession::add2SessVar($iVar, $msg);
    }

    public static function dict2nv($dict)
    {
        if (is_object($dict)) {
            return (array) $dict;
        }
        return is_array($dict) ? $dict : [];
    }



    /* 
     * utilize debug default to br
     * @param $ivar $istr $iformat  
     * @return string 
     */
    public static function pln($iVar, $iStr = "", $iFormat = "br")
    {

        print self::debug($iVar, $iStr, $iFormat);
    }

    public static function aliasLookup($app, $aliases)
    {
        // aliaslookup() will produce t=users&a=logout // turn off to use router&a=logout

        //        CUtil::debug($app,'app');       
        $luArr = array();
        foreach ($aliases as $key => $aliasArray) {
            $varry = array_values($aliasArray);
            if (in_array($app, $aliasArray)) { // false if not found
                $luArr['t'] = strtolower($key);
                $luArr['a'] = strtolower($app);
                break;
            }
        }
        //        pln($luArr, 'alias');
        //exit;
        //        CUtil::debug($luArr,'alias');       
        return $luArr;
    }

    public static function methodNotParent($class_name, $method_name)
    {
        $ret = false;
        $class = new \ReflectionClass($class_name);
        if ($class->hasMethod($method_name)) {
            $m = $class->getMethod($method_name);
            // Compare short names only
            $declaredIn = (new \ReflectionClass($m->class))->getShortName(); // Get short name of the class where the method is declared
            $targetClass = (new \ReflectionClass($class_name))->getShortName(); // Get short name of the target class
            if (strtolower($declaredIn) == strtolower($targetClass)) {
                $ret = true;
            }
        }
        return $ret;
    }
    public static function not_ns_methodNotParent($class_name, $method_name)
    {

        $ret = false;
        $class = new \ReflectionClass($class_name);
        if ($class->hasMethod($method_name)) {
            $m = $class->getMethod($method_name);
            if ($m->class == ucfirst($class_name)) {
                $ret = true;
            }
        }
        return $ret;
    }

    public static function methodlist($className)
    {

        $methods = get_class_methods($className);
        print CUtil::debug($methods, $className . ':methods', 'p');
    }

    public static function parseQs($routes, $className = self::class)
    {

        $qsArr = CUtil::qsValue(); // current qs ex: t=front&a=index, bad qs and got 404 before got here
//        print CUtil::debug($qsArr, __METHOD__.':qs','p');  
//        print CUtil::debug($className,'class');      
        $args = $qsArr;
        if (!empty($args['t']) and $luArr = CUtil::aliasLookup($args['t'], $routes['alias'])) {
            $args = $luArr;
            //            pln($args, ':aft-alias');
        }
        // if not a full QS then patch it up with either default controller or this class
        $defCntl = strtolower($routes['default_controller']);
        if (empty($args['t']) or empty($args['a'])) {
            if (!empty($qsArr['t'])) {
                $args['a'] = $qsArr['t'];
                $args['t'] = strtolower($className);
            } else {
                if (empty($args['t']) and !empty($defCntl)) {
                    $args['t'] = $defCntl;
                } elseif (empty($defCntl)) {
                    $args['t'] = strtolower($className);
                }
            }
            if (empty($args['a'])) {
                $args['a'] = "index";
            }
        }
        //        print CUtil::debug($routes, 'routes','p');         
//        print CUtil::debug($args, ':args','p');         
        return $args;
    }

    public static function dir2Array($dir, $recursive = false)
    {

        $oArray = [];
        $cdir = scandir($dir);
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if ((is_dir($dir . DIRECTORY_SEPARATOR . $value)) and $recursive == true) {
                    $oArray[$value] = self::dir2Array($dir . DIRECTORY_SEPARATOR . $value, $recursive);
                } else {
                    $oArray[] = $value;
                }
            }
        }
        return $oArray;
    }

    protected static function shortClass(string $fqcn): string
    {
        return substr(strrchr($fqcn, '\\'), 1) ?: $fqcn;
    }

    public static function getClass($className)
    {

        if (class_exists($className)) {
            return new $className();
        }
    }

    public static function filesListNameOnly($dir, $ext)
    {
        return CFiles::filesListNameOnly((string) $dir, (string) $ext);
    }

    public static function filesList($dir, $ext)
    {
        return CFiles::filesList((string) $dir, (string) $ext);
    }

    public static function dirsList($dir)
    {
        return CFiles::dirsList((string) $dir);
    }

    public static function mnu_s2a($source, $s): array
    {
        if (gettype($source) <> "string" || strpos($source, ",") === FALSE)
            return []; // must be string and contain ,


        // Split the source string by comma
        $ka = explode(',', $source);

        // Initialize output array of 4 elements
        $lnka = array_fill(0, 4, '');

        // URL
        $lnka[0] = $ka[0];

        // Determine how many parts exist
        $asize = count($ka) - 1;

        // Defaults
        $lnka[1] = "";
        $lnka[2] = "";

        // target attribute
        if ($asize > 0 && strlen(trim($ka[1])) > 0) {
            $lnka[1] = " target='" . trim($ka[1]) . "'";
        }

        // image filename
        if ($asize > 1 && strlen(trim($ka[2])) > 0) {
            $lnka[2] = trim($ka[2]);
        }

        // Load config (NameValueCollection equivalent = associative array)
        $fa = self::getCfg(CCore::getSelectedViewSet(), 1);

        // Get menu name if it exists
        $lName = self::getViewMnuName($s, $fa);

        // Title or fallback to s
        if (strlen($lName) > 0) {
            $lnka[3] = $lName;
        } else {
            $lnka[3] = $s;
        }
        //        pln($lnka, "mnu_s2a lnka");

        return $lnka;
    }


    public static function getCfg(string $avar, int $rVal = 0) // WORK, need to write to CSetting->_cfg instead of CCore::or CConfig
    {
        $uInfo = [];
        $uInfoa = [];
        $gArray = [];
        //        pln($rVal, 'rVal');
//        pln($avar, 'avar');
//                      dmsg(CCore::$_cfg["$avar"],'c:avar');
        if (isset(CCore::$_cfg["$avar"]) && !empty(CCore::$_cfg["$avar"]) && $avar == "apps") { // apps as string
            $apps = CCore::$_cfg["$avar"];
            //        pln($apps, 'apps');

            $gArray = explode(',', $apps);
        } else if (isset(CCore::$_cfg[$avar]) && CCore::$_cfg[$avar] != null) {
            $gArray = CCore::$_cfg[$avar];
            //        CMsg::_msg($gArray, "getCfg-b");
        }
        //        pln($gArray, 'gArray');

        foreach ($gArray as $s => $value) { // get individual app menu with group or title
            if (!empty($value)) {
                $title = "";
                $uInfo = explode(',', $value);  // group,title 
                if ($rVal < count($uInfo) && $uInfo[$rVal] != "") { // 0 = group name, 1 = title
                    $title = $uInfo[$rVal];
                }
                $uInfoa[$s] = $title; // add s even the val is null
            }
        }
        //        pln($uInfoa, "getCfg-a");
        return $uInfoa;

    }


    public static function s2nv(string $delimiter, string $txt)
    {
        // Converts CSV lines to assoc array: ["key"=>"value1,value2,..."]
        $lines = explode("\n", $txt);
        $result = [];
        foreach ($lines as $line) {
            $parts = explode($delimiter, $line);
            if (count($parts) > 1) {
                $key = array_shift($parts);
                $result[$key] = implode($delimiter, $parts);
            }
        }
        return $result;
    }


    public static function getReturnViewFileFromSess()
    {
        $vFile = "";
        $returl = CUtil::getSessTxt("retUrl"); // set returl from timeout session
        //      CMsg::_pdmsg(returl, "getReturnViewFile");
        if (CString::IsEmpty($returl) == false) {
            $qsa = CUtil::qs2nv($returl);
            $_Session["retUrl"] = "";  // clear redirect MUST DO THIS
            $vFile = CCore::SetView($qsa[1], $qsa[0]); // get redirect returl view
            //        CMsg::_pdmsg(vFile, "retfile");
        }
        return $vFile;
    }
    public static function getSessTxt(string $fb = "feedback")
    {
        return CSession::getSessTxt($fb);
    }
    public static function qsValue() // good only if $_SERVER['QUERY_STRING'] has value
    {
        return CUrl::qsValue();
    }


    public static function qs2nv($url = "") // qs2nv replace qsvalue()
    {
        return CUrl::qs2nv($url);
    }
    public static function GetLayout(string $ilayout = "")
    {
        $sLayout = (CString::IsEmpty($ilayout)) ? CSetting::get("deflayout") : $ilayout; // if empty, use default layout
        return CSetting::get("layoutpath") . "/" . $sLayout . CSetting::get("viewext");
    }

    // ivar /task/action/p1/p2 cmd = delete (optional)
    public static function Tap2Qs(string $iVar, string $cmd = "")
    {
        return CUrl::Tap2Qs($iVar, $cmd);
    }

    public static function setActiveCtrl_original($qsa = []) // work with users,books,authors, KEEP for reference
    {
        $tsk = "";
        //        pln($qsa, "setActiveCtrl:qsa");
//        pln(CSetting::get('selctrl'), "setActiveCtrl-IN");
        if ($qsa != null && isset($qsa['t']) && !empty($qsa['t'])) { // should be t instead of 0
            $tsk = $qsa['t'];
            CSetting::set('selctrl', $tsk);         // set selected active controller   
            $sel = CSetting::get('selctrl');
            //            pln($sel, "B-setActiveCtrl-IN s:$sel=t:$tsk");

        } else {
            //            pln($qsa, "ELSE setActiveCtrl");
            if (CSetting::get('selctrl') == '') {
                CSetting::set('selctrl', CSetting::get('defctrl'));         // set defctrl as active controller       
            }
        }
        $sel = CSetting::get('selctrl');
        //        pln($sel, "A-setActiveCtrl-IN s:$sel=t:$tsk");
        //        pln(CSetting::get('selctrl'), "setActiveCtrl");
        //        pln(CSetting::get('takey'), "setActiveCtrl-takey");
        self::setMenu($sel);
        //        pln(CSetting::$_stg['tg'],'setActiveCtrl-tg');
    }

    public static function setActiveCtrl($qsa = []) // use view folder to validate select task, SAFER
    {
        $tsk = "";
        //pln($qsa,'qsa=');
        if ($qsa != null && isset($qsa['t']) && !empty($qsa['t'])) {
            $tsk = $qsa['t'];
            //pln($tsk,'t=');

            // validate directly against the controller's own view path,
            // not against apps.list (which is a taskgroup subset, not a nav registry)
            $viewPath = CFiles::getRealViewPath($tsk);
            if (is_dir($viewPath) && substr($tsk, 0, 1) <> "_") {
                CSetting::set('selctrl', $tsk);         // set selected active controller
            } else {
                CSetting::set('selctrl', CSetting::get('defctrl'));  // bad/unknown $tsk — reset to a known-good default
            }
        } else {
            if (CSetting::get('selctrl') == '') {
                CSetting::set('selctrl', CSetting::get('defctrl'));         // set defctrl as active controller
            }
        }
        $sel = CSetting::get('selctrl');
        //pln($tsk,'t=');
//pln($sel,'s=');

        self::setMenu($sel);
    }


    public static function captureLastUrl($qsa)
    {
        // Skip requests with no real querystring at all (e.g. /favicon.ico, robots.txt,
        // or any other bare-path request where parseQs() silently defaulted t/a).
        if (empty($_SERVER['QUERY_STRING'])) {
            return;
        }

        $t = strtolower($qsa['t'] ?? CSetting::get('selctrl') ?? '');
        $a = strtolower($qsa['a'] ?? '');

        $excludedT = ['action', 'odata', 'udata'];
        $excludedA = ['login', '_login', 'logout', '_logout'];

        if (!in_array($t, $excludedT) && !in_array($a, $excludedA)) {
            $_SESSION['lastUrl'] = self::MyUrl();
        }
    }




    public static function sName2Mnu($fa) // a from apps list (no title) or the menu array with title
    {
        $nvList = [];
        //        pln($fa, "sName2Mnu");
        foreach ($fa as $f) {
            //            pln($f, "f");
            $viewPath = CFiles::getRealViewPath($f);
            if (is_dir($viewPath) && (substr($f, 0, 1) <> "_")) { // exclude _app
                $a = CSetting::get('defview');
                $nvList[$f] = ['title' => ucfirst($f), 'path' => "/$f/$a"]; // it expect to be path
            }
        }
        //        pln($nvList, "sName2Mnu");
        return $nvList;
    }

    public static function tap_selfurl(string $iVar, string $cmd = "")
    {
        return CUrl::tap_selfurl($iVar, $cmd);
    }
    public static function tap(string $iVar, string $cmd = "")
    {
        return CUrl::tap($iVar, $cmd);
    }

    public static function path2qs(string $iPath, string $cmd = "")
    {
        return CUrl::path2qs($iPath, $cmd);
    }
    public static function path2a(string $iPath)
    {
        return CUrl::path2a($iPath);
    }
    public static function nv2kps($source, $delim = "&")
    {
        return CUrl::nv2kps($source, $delim);
    }

    public static function sb2s($sb, string $separator)
    {
        return CUrl::sb2s($sb, $separator);
    }
    public static function cTsk()
    {
        $qsa = CUtil::qsValue();     // ?=t or defctlr or default_controller or ''
        return $qsa['t'] ?? CCore::$_cfg["defctrl"] ?? CCore::$_cfg["default_controller"] ?? '';
    }
    public static function TaskGroup(string $task = "_cfgtg"): string
    {
        if (empty($tg)) {
            //            pln($task, 'TaskGroup-lookup-tg-empty');
        }

        if ($task === "_cfgtg") {
            return ""; // legacy boot-time build call, now a no-op — CMenu::setMenu() owns this
        }
        $tg = CSetting::get('tg') ?? [];
        return $tg[strtolower($task)]['group'] ?? '';
    }
    public static function TaskGroup_old(string $task = "_cfgtg"): string
    {
        $group = "";
        $uInfoa = [];

        //    $apps  = CCore::$_cfg['app']['list'];
//        $apps = CCore::$_stg['apps'] ?? CCore::$_cfg['app']['list'] ?? '';   // direct, no helper needed    
        $apps = CCore::$_stg['apps']['list'] ?? CCore::$_cfg['apps']['list'] ?? '';   // direct, no helper needed    
        $appsA = explode(',', $apps);

        foreach ($appsA as $val) {
            //            pln($val,'v');
            $tArray = CCore::$_cfg['app'][$val] ?? [];
            //            pln($tArray,'t');

            foreach ($tArray as $entry) {

                $name = $entry['name'];
                $tInfo = explode(',', $entry['info']);

                if (count($tInfo) > 0) {
                    $grp = trim($tInfo[0]);

                    if ($task === $name) {
                        $group = $grp;
                        break 2;
                    } else {
                        $uInfoa[$name] = $grp;
                    }
                }
            }
        }

        if ($task === "_cfgtg") {
            CCore::$_tg = $uInfoa;
            CCore::$_cfg['tg'] = $uInfoa;
            /*
pln(CCore::$_tg,'tg');
pln(CCore::$_cfg['tg'],'tg');
pln( $uInfoa,'_cfgtg');
*/
        }
        return $group;
    }

    /**
     * Splits a string into an array using a delimiter.
     *
     * @param string $delim The delimiter character.
     * @param string $iStr The string to split.
     * @return array An array of strings.
     */
    public static function str2a(string $delim, string $iStr): array
    {
        return explode($delim, $iStr); // split into array
    }
    public static function Cookies_Get(string $key = "")
    {
        return CSession::Cookies_Get($key);
    }

    public static function Cookie_Usr($uinfo = null)
    {
        CSession::Cookie_Usr($uinfo);
    }

    public static function Cookie_Hash(string $user_name)
    {
        $ret = "";
        //        $mach_name = Environment . MachineName;
        $mach_name = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        //        CMsg::. _pdmsg($mach_name, "mach_name");
        $source = strtolower($user_name) . CSetting::get("hash") . $mach_name;
        $ret = CSecs::GetMd5Hash($source);
        return $ret;
    }

    public static function Cookies_Set(string $key, string $value, $expires)
    {
        CSession::Cookies_Set($key, $value, $expires);
    }
    public static function getReturnUrl()
    {
        $returl = "?";

        $frm = $_POST;

        $qsa = self::qs2nv();
        $selectctrl = CCore::getSelectedViewSet();
        if ($qsa != null) {
            if (isset($qsa["rurl"]) && !CString::IsEmpty($qsa["rurl"])) {
                $returl = $qsa["rurl"]; // from QS of the url of redirected
            } else if (isset($frm["rurl"]) && !CString::IsEmpty($frm["rurl"])) {
                $returl = $frm["rurl"]; // from the form of redirected
            } else if (!empty($_SESSION['lastUrl'])) {        // NEW
                $returl = $_SESSION['lastUrl'];               // NEW
//        } else if (!CString::IsEmpty($selectctrl) && $selectctrl != "action") {
            } else if (!CString::IsEmpty($selectctrl) && $selectctrl != "action" && $selectctrl != "router") {
                $returl = self::tap("/" . $selectctrl . '/index'); // if not action then use the selected controller for redirect
            }
        }
        //        pln($returl, "getReturnUrl");
//        pln($_SESSION['lastUrl'] ?? 'NOT SET');
        //        exit;
        return $returl;
    }

    public static function getLogoutReturnUrl(): string
    {
        $returnUrl = self::getReturnUrl();
        $query = parse_url($returnUrl, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return $returnUrl;
        }

        parse_str($query, $returnArgs);
        $task = strtolower((string) ($returnArgs['t'] ?? ''));
        $taskGroup = self::TaskGroup($task);
        if ($task === '' || $taskGroup === '' || strtolower($taskGroup) === 'guest') {
            return $returnUrl;
        }

        $apps = CSetting::get('apps', []);
        if (is_array($apps)) {
            foreach ($apps as $parent => $children) {
                if (!is_array($children) || !array_key_exists($task, $children)) {
                    continue;
                }

                $parentGroup = self::TaskGroup((string) $parent);
                $parentActions = CSetting::get('tg.' . $parent . '.actions', []);
                if (
                    ($parentGroup === '' || strtolower($parentGroup) === 'guest')
                    && is_array($parentActions)
                    && in_array('index', $parentActions, true)
                ) {
                    return self::tap('/' . $parent . '/index');
                }
            }
        }

        return '?';
    }

    public static function k09092026_getReturnUrl()
    {
        $returl = "?";
        /*
        // don't want to go back to login page
        $myUrl = self::MyUrl();
        pln($myUrl, "getReturnUrl-my");
*/
        //      $frm = HttpContext.Current.Request.Form;
        $frm = $_POST;

        $qsa = self::qs2nv();
        $selectctrl = CCore::getSelectedViewSet();
        if ($qsa != null) {
            if (isset($qsa["rurl"]) && !CString::IsEmpty($qsa["rurl"])) {
                $returl = $qsa["rurl"]; // from QS of the url of redirected
            } else if (isset($frm["rurl"]) && !CString::IsEmpty($frm["rurl"])) {
                $returl = $frm["rurl"]; // from the form of redirected
/*            }  
            else if ($qsa["t"] != "action" && (($qsa["a"] != "login")||($qsa["a"] != "_login")) && !CString::IsEmpty($myUrl)) {
                $returl = $myUrl; // fix bug on timeout redirect login use case
                */
            } else if (!CString::IsEmpty($selectctrl) && $selectctrl != "action") {

                $returl = self::tap("/" . $selectctrl . '/index'); // if not action then use the selected controller for redirect
            }
        }
        /*   
           if (CUtil::getSessTxt("retUrl")!="") // this redirect work! 
           {
             $returl = CUtil::getSessTxt("retUrl"); // set returl from session, this redirect work! 
           }
          */
        pln($returl, "getReturnUrl");
        return $returl;
    }
    public static function Redirect($url = "?", bool $endResponse = true, $statusCode = 303)
    {
        header('Location: ' . $url, $endResponse, $statusCode);
        exit();
    }
    // Safely build the full current URL
    public static function MyUrl(): string
    {
        return CUrl::MyUrl();
    }


    // Assuming CUtil:: CSecs, and a way to retrieve NameValueCollection equivalent (e.g., an array of strings for keys and their associated values)
// You'll need to implement these helper functions and data structures in PHP based on your C# implementations.

    /**
     * Converts a directory name to a submenu string.
     *
     * @param array $fa A NameValueCollection equivalent, likely an associative array where keys are directory names and values are associated data (if any).
     * @return string The generated submenu HTML.
     */
    public static function viewNameDir2Submnu(array $fa): string // WORK
    {
        $sb = '';
        //        pln($fa, "viewNameDir2Submnu");
        foreach ($fa as $key => $value) { // Assuming $fa is now an associative array
            pln($key, "key");
            // Check perm here before building the menu
            $mnu = self::viewDir2Nv4Mnu($key); // All menu links from the view folder
            pln($mnu, "viewNameDir2Submnu");
            if ($mnu !== null && count($mnu) > 0) {
                $buff = "";
                /*
                [SMnu]:(jendo)
                [mnu]:(autocomplete=[http://localhost:83/ejvnetdev/?t=jendo&a=autocomplete],createselectpdf=[http://localhost:83/ejvnetdev/?t=jendo&a=createselectpdf],datepicker=[http://localhost:83/ejvnetdev/?t=jendo&a=datepicker],ldapUW=[http://localhost:83/ejvnetdev/?t=jendo&a=ldapUW],upload=[http://localhost:83/ejvnetdev/?t=jendo&a=upload])
                */
                // echo CMsg::_msg($mnu, "vfl"); // for each view folder for debugging

                $lnka = null;
                $sbx = '';
                foreach ($mnu as $s => $mnuValue) { // Assuming $mnu is an associative array from viewDir2Nv4Mnu
                    // echo CMsg::_msg($mnuType . "-" . $s, "m-i"); // for debugging
                    if ($s !== null && strlen($s) > 0) {
                        // CUtil:::mnu_s2a needs to be implemented in PHP
                        // It should take the string from $mnu[$s] and the key $s, and return an array of strings (menu link parts)
                        $lnka = CUtil::mnu_s2a($mnuValue, $s);

                        // CSecs::isPublicAccess4Mnu and CSecs::isUsrHasAccess2Mnu need to be implemented in PHP
                        // They should take the $lnka array and the current directory key $key
                        if (
                            CSecs::isPublicAccess4Mnu($lnka, $key) === true ||
                            CSecs::isUsrHasAccess2Mnu($lnka, $key) === true
                        ) {
                            // CUtil:::a2Li needs to be implemented in PHP
                            // It should take the $lnka array and return an HTML list item string (e.g., <li><a href="...">...</a></li>)
                            $sbx .= CUtil::a2Li($lnka);
                        }
                    }
                }
                $buff = $sbx;
                if ($buff !== null && strlen($buff) > 0) {
                    $sb .= $buff;
                }
            }
        }
        return $sb;
    }
    /**
     * Build submenu <li> HTML from a name=>title map.
     * Mirrors C#: CUtil::viewNameDir2Submnu()
     */
    public static function ai_viewNameDir2Submnu(array $fa): string // not working
    {
        $html = '';
        $selctrl = self::ai_getSelectedViewSet();
        //        pln($selctrl, "selctrl");

        foreach ($fa as $name => $title) {
            $name = trim($name);
            if ($name === '')
                continue;
            $label = !empty($title) ? htmlspecialchars($title) : htmlspecialchars(ucfirst($name));
            $url = '/?t=' . $selctrl . '&a=' . $name;
            $html .= '<li class="nav-item">'
                . '<a class="nav-link" href="' . $url . '">' . $label . '</a>'
                . '</li>';
        }
        return $html;
    }
    /**
     * Return the current active controller name (lowercased).
     * Reads from CConfig 'info.selctl'.
     * Mirrors C#: CCore::getSelectedViewSet()
     */
    public static function ai_getSelectedViewSet(): string
    {
        return strtolower(CConfig::get('selctl')) ?? strtolower(CConfig::get('info.selctl')) ?? '';
    }
    public static function getViewMnuName(string $f, array $fa = [])
    {
        $lName = "";
        if (isset($fa[$f]) && !empty($fa[$f])) {
            $lName = $fa[$f]; // Use custom view name
        } else {
            $lName = ucfirst($f); // Capitalize only first character
        }
        return $lName;
    }
    public static function setLoginUrl()
    {
        CCore::$_stg["urllogin"] = "/" . CCore::getSelectedViewSet() . "/" . CSetting::get("login"); // set login url based on selected controller
//        pln(CCore::$_stg["urllogin"], "urllogin");
    }
    /**
     * Get a session variable as an array.
     * Mirrors C#: CUtil::getSessNv()
     */
    public static function getSessNv(string $key): array
    {
        return CSession::getSessNv($key);
    }
    public static function isEqInList(string $iVar, string $iList, $delim = ',')
    {

        $iArray = CUtil::Str2a($delim, $iList); // if in list of secure
        $isInTheList = false;
        //      $isInTheList = $iArray.Any(needle => $iVar.Equals(needle.ToLower())); // LINQ
        $isInTheList = in_array(strtolower($iVar), array_map('strtolower', $iArray), true);

        return $isInTheList;
    }
    public static function v2BasePath(?string $vPath): string
    {
        if ($vPath === null || trim($vPath) === '') {
            return '';
        }
        if (substr($vPath, 0, 1) == "~") {
            $vPath = self::basePath() . substr($vPath, 1);
        }
        return $vPath;
    }
    public static function basePath()
    {
        return self::rootSite();
    }
    public static function a2Li($lnka)
    {
        $retStr = "";
        //        pln($lnka, 'lnka');
        if ($lnka != null) {
            $retStr = "<li>" . self::a2ahref($lnka) . "</li>";
        }
        return $retStr;
    }
    public static function a2ahref($lnka)
    {
        $img = "";
        $retStr = "";
        //        pln($lnka, 'lnka');
        if ($lnka != null) {
            if (strlen($lnka[2]) > 0) {
                $iPath = self::v2BasePath(CSetting::get("imgpath"));
                $img = "<img src=\"" . $iPath . "/" . $lnka[2] . "\">";
            }
            $retStr = "<a href=\"" . $lnka[0] . "\"" . $lnka[1] . ">" . $img . $lnka[3] . "</a>";
        }
        return $retStr;
    }
    public static function viewDir2Nv4Mnu(string $dPath = ".")
    {
        $nvList = [];
        $files = self::getViewNameArray($dPath);
        if ($files != null) {
            foreach ($files as $f) {
                $nvList[$f] = self::tap("/" . $dPath . "/" . $f);
            }
        }
        return $nvList;
    }

    public static function getViewNameArray(string $dPath = ".", $excl = ["index", "_*"]): ?array
    {
        $files = CFiles::getViewFilesExcl($dPath, $excl);
        if ($files !== null) {
            $ofiles = [];
            foreach ($files as $f) {
                $fileNameWithoutExtension = pathinfo($f, PATHINFO_FILENAME);
                $ofiles[] = $fileNameWithoutExtension;
            }
            $ret = $ofiles;
        } else {
            $ret = null;
        }
        return $ret;
    }

    /**
     * Gets an array of filenames (without extensions) from files in a directory,
     * excluding specific files based on CFiles::getViewFilesExcl results.
     * Mimics C# getViewNameArray functionality.
     *
     * @param string $dPath The directory path. Defaults to the current directory.
     * @param string $excl The filename (without extension) to exclude. Defaults to "index".
     * @return array<string>|null An array of filenames (without extensions), or null if no files were found by getViewFilesExcl.
     */
    public static function NO_Exclu_getViewNameArray(string $dPath = ".", string $excl = "index"): ?array
    {
        // C# `FileInfo[] files = CFiles.getViewFilesExcl(dPath, excl);`
        // PHP equivalent: Call the static method. Result could be null or an array.
        $files = CFiles::getViewFilesExcl($dPath, $excl);
        //pln($files,'getViewNameArray');
        // C# `if (files != null)`
        // PHP equivalent: Check if the result is not null.
        if ($files !== null) {
            // C# `var ofiles = new List<string>();`
            // PHP equivalent: Initialize an empty array for strings.
            $ofiles = [];

            // C# `foreach (var f in files)`
            // PHP equivalent: Iterate through the array of FileInfo objects.
            // Add the PHPDoc type hint here:
            /** @var FileInfo $f MUST be here for $f-Name to work */
            foreach ($files as $f) {
                // C# `f.Name` -> Access the Name property of the FileInfo object.
                // C# `Path.GetFileNameWithoutExtension(f.Name)` -> PHP `pathinfo($f->Name, PATHINFO_FILENAME)`
                // The comment "// do not change to lowercase" means we respect the original casing.
                $fileNameWithoutExtension = pathinfo($f, PATHINFO_FILENAME);

                // C# `ofiles.Add(...)`
                // PHP equivalent: Add the filename to the array.
                $ofiles[] = $fileNameWithoutExtension;
            }

            // C# `ret = ofiles.ToArray();`
            // PHP equivalent: In this case, $ofiles is already a PHP array, so no conversion needed.
            // We just assign it to the return variable.
            $ret = $ofiles;
        } else {
            // If files was null, ret should also be null as per C# code.
            $ret = null;
        }

        // C# `return ret;`
        return $ret;
    }

    // --- Example Usage ---
/*
// Make sure the FileInfo class and CFiles { getViewFilesExcl } are defined and accessible.

// Create dummy files for testing (ensure '/path/to/test_dir' is writable)
// $testDir = __DIR__ . '/test_dir';
// if (!is_dir($testDir)) mkdir($testDir, 0777, true);
// file_put_contents($testDir . '/index.php', '<?php ?>'); // Excluded by default $excl
// file_put_contents($testDir . '/about.php', '<?php ?>');
// file_put_contents($testDir . '/_private.php', '<?php ?>'); // Excluded by substr($f->Name, 0, 1) != "_" logic in getViewFilesExcl
// file_put_contents($testDir . '/contact.txt', '<?php ?>'); // Included if not excluded by pattern

// Set up a dummy POST data or system state if needed for CFiles::getViewFilesExcl
// $_POST['DdlPageSize'] = 'A4'; // ... etc.

// Call the function
// Adjust the path as needed, e.g., use __DIR__ for current script directory
$directoryToScan = $testDir; // Use the dummy test directory
$excludedName = "index";    // Exclude 'index'

$namesArray = CFiles::getViewNameArray($directoryToScan, $excludedName);

if ($namesArray === null) {
    echo "CFiles::getViewFilesExcl returned null (e.g., directory invalid).\n";
} elseif (empty($namesArray)) {
    echo "No files found matching the criteria.\n";
} else {
    echo "Array of filenames (without extension):\n";
    print_r($namesArray);
}

// Expected Output (based on dummy files and logic):
// Array
// (
//     [0] => about
//     [1] => contact
// )

// Cleanup dummy files/directory
// unlink($testDir . '/index.php');
// unlink($testDir . '/about.php');
// unlink($testDir . '/_private.php');
// unlink($testDir . '/contact.txt');
// rmdir($testDir);
*/
    static function getLiMenu($iMenu) // menu is [[]] nested array
    {
        return CMenu::getLiMenu($iMenu);
    }

    public static function nestedLowercase($value)
    {
        if (is_array($value)) {
            $callable = self::nestedLowercase($value);
            // Apply the function to each element recursively
            return array_map($callable, $value);
        }
        // Only lowercase strings; leave other types unchanged
        return is_string($value) ? strtolower($value) : $value;
    }

    public static function rmArr1D($av, $toberm) // remove item from 1D array
    {
        $ret = $av;
        if ($ret <> null) {
            unset($ret[array_search($toberm, $ret)]);      // search to remove array value    
        }
        return $ret;
    }

    public static function isAllowGrp($tgroup = '')
    {
        return CMenu::isAllowGrp($tgroup);
    }


    // the group that lists $ctrl as a member win; own group; else []
    private static function gwin_resolveAppGroup(string $ctrl): array
    {
        $apps = CSetting::get('apps') ?? [];

        // 1. a group that lists $ctrl as a member (parent menu)
        foreach ($apps as $group => $items) {
            if (is_array($items) && array_key_exists($ctrl, $items)) {
                return $items;
            }
        }
        // 2. optional alias: 'x' => 'front'
        $own = $apps[$ctrl] ?? null;
        if (is_string($own) && is_array($apps[$own] ?? null)) {
            return $apps[$own];
        }
        // 3. own group
        return is_array($own) ? $own : [];
    }

    public static function setMenu($selctrl)
    {
        CMenu::setMenu($selctrl);
    }

    public static function getMenu($mnu = "main")
    {
        return CMenu::getMenu($mnu);
    }

    public static function getCommonMenu()
    {
        return CMenu::getCommonMenu();
    }

    public static function getTopMenu()
    {
        return CMenu::getTopMenu();
    }

    public static function getSubMenu()
    {
        return CMenu::getSubMenu();
    }


    public static function mnu2Li($mnu)
    {
        $lnka = null;
        $sb = "";
        //        pln($mnu, "mnu2Li"); // for each top menu
        // [mnu]: (front =[http:\//localhost:83/ejvnetdev/?t=front],admin=[http:\//localhost:83/ejvnetdev/?t=admin])
        foreach ($mnu as $s => $v) {
            //            pln($s, "mnu2Li-s");
//            pln($v, "mnu2Li-v");
            if (CString::IsEmpty($s) == false) {
                $lnka = self::mnu_nv2a($mnu, $s);
                $sb .= self::a2Li($lnka);
            }
        }
        return $sb;
    }
    public static function mnu_nv2a(array $source, string $s): array
    {
        $value = isset($source[$s]) ? $source[$s] : '';
        $lnka = self::mnu_s2a($value, $s);
        return $lnka;
    }

    public static function Mnu2LiSec($mnu, $mnuType)
    {
        pln($mnu, "Mnu2LiSec");
        $lnka = null;
        $sb = "";
        // process each view file and link
        //      CMsg::_pdmsg(mnu, "mnu");
        //[mnu]:(jv=[http://localhost:83/ejvnetdev/?t=jv],jvadm=[http://localhost:83/ejvnetdev/?t=jvadm],jvapv=[http://localhost:83/ejvnetdev/?t=jvapv],jvtpl=[http://localhost:83/ejvnetdev/?t=jvtpl],jvinq=[http://localhost:83/ejvnetdev/?t=jvinq],jendo=[http://localhost:83/ejvnetdev/?t=jendo],jsgrid=[http://localhost:83/ejvnetdev/?t=jsgrid],ko=[http://localhost:83/ejvnetdev/?t=ko])
        foreach ($mnu as $s) {
            //                    pln($s, "Mnu2LiSec-s");
/*
            if ($s != $mnuType &&  isset($mnuType)) // exclude the default controller in the submenu
            {
                $lnka = self::mnu_s2a($mnu[$s], $s); // http:\//localhost:83/ejvnetdev/test, test
                //[lnka]:(0=[http://localhost:83/ejvnetdev/?t=jvadm],1=[],2=[],3=[JV Admin]) 
                //      CMsg::_pdmsg(lnka, "lnka");
                if (
                    CSecs::isPublicAccess4Mnu($lnka, $mnuType) == true
                    || CSecs::isUsrHasAccess2Mnu($lnka, $mnuType) == true
                ) {
                    $sb .= self::a2Li($lnka);
                }
            }
                */
        }
        return $sb;
    }
    /**
     * Utility class that mirrors the C# HttpResponse helper.
     *
     * $data = ['status' => 'ok', 'message' => 'All good!'];
     * outJson(json_encode($data));
     * 
     * Usage:
     *   JsonHelper::outJson($jsonString);
     */
    /**
     * Sends a JSON response and stops further output.
     *
     * @param string $json  The JSON payload you want to return.
     * @return void
     */
    public static function outJson(string $json): void
    {
        // Remove any previously‑set headers (if output buffering is used)
        // This mirrors HttpResponse.ClearHeaders() in ASP.NET.
        if (headers_sent() === false) {
            // Clear all existing headers
            header_remove();
        }

        // Tell the client we are returning JSON
        header('Content-Type: application/json; charset=utf-8');

        // Optionally disable caching (helps during development / APIs)
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Output the JSON string
        echo $json;

        // Flush the output buffer and terminate the script
        // (similar to ending the ASP.NET request after Write())
        if (ob_get_level()) {
            ob_end_flush();
        }
        exit; // ensures nothing else is sent after the JSON payload
    }
    public static function imgPath()
    {
        return self::v2BasePath(CSetting::get("imgpath"));
    }

    /**
     * Ported from CUtils.CnvDate(). Wraps a column in CONVERT(DATETIME, col); when $value is
     * non-empty, the FIRST argument is treated as the date literal and quoted (as in the C#,
     * GetFOVDate passes the date as $iVar). Single quotes are doubled, which the C# did not do.
     */
    public static function CnvDate($iVar, $value = "")
    {
        return CDbHelper::CnvDate($iVar, $value);
    }

    /**
     * Ported from CUtils.GetFOVDate(): "CONVERT(DATETIME, field) <op> CONVERT(DATETIME, 'date')".
     * NOTE: CJvAdm uses CJv::GetConvertedDate() instead, which normalises to Y-m-d.
     */
    public static function GetFOVDate($fieldName, $opr, $strDateValue)
    {
        return CDbHelper::GetFOVDate($fieldName, $opr, $strDateValue);
    }

    /**
     * Ported from CUtils.FOV() (field / operator / value). "?" keeps the placeholder form;
     * "like" wraps the value in %...%; a non-empty $ilFunc means the value is an inline
     * SQL function and is emitted unquoted.
     */
    public static function FOV($source, $op, $value = "?", $ilFunc = "")
    {
        return CDbHelper::FOV($source, $op, $value, $ilFunc);
    }

    /**
     * Ported from CUtils.Validate() in the C# source. Only the "password"
     * case is currently exercised (MJvAdm::UpdateUser / CreateUser); the
     * "empty"/"email"/"full_email" cases are kept for parity with the C#
     * version but are effectively unused since CJv::JvValidate("email", ...)
     * already covers email checks elsewhere in this codebase.
     */
    public static function Validate($iType, $iStr)
    {
        return CString::Validate($iType, $iStr);
    }
}
