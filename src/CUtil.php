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

class CUtil {

    public static function debug($iVar, $iStr = "", $iFormat = "") {
        // Check if debug is enabled via _DEBUG_ENABLED flag
        
        if (!defined('_DEBUG_ENABLED') || !_DEBUG_ENABLED) {
            return null; // Debug disabled, skip all logging
        }
        
        $str = $dTrace = "";

        (!empty($iStr) and strtolower($iStr) == "dtrace") ? $dTrace = "dtrace" : $dTrace = "";
        (!empty($iStr) and strtolower($iStr) <> "dtrace") ? $preText = "[-" . strtoupper($iStr) . "-] " : $preText = "";
        if (!empty($iVar)) {
            if (is_array($iVar) or ( is_object($iVar)))
                $iVar = print_r($iVar, true);
            if (!empty($dTrace))
                $dTrace = self::dTrace();
            (empty($iFormat)) ? $str = $preText . $iVar : $str = "<pre>" . $preText . $iVar . "</pre>";
        } 
        /*
        else {
            $str = $preText . ' Var is empty!';
        }
        */
        $ret = $str . $dTrace . " ";
        
        // Write to file log
        $logDir = dirname(dirname(dirname(__DIR__))) . '/db/logs';
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
                    'reset_num' => (int)$_SESSION['debug_resets'] + 1
                ];
                
                // Keep only last 10 debug logs to prevent array bloat
                if (count($_SESSION['debug_logs']) > 10) {
                    array_shift($_SESSION['debug_logs']);
                }
                
                // Reset on-screen debug
                $_SESSION['dmsg'] = "[SCREEN RESET - " . (int)$_SESSION['debug_resets'] + 1 . " | " . $lineCount . " lines logged]\n" . $ret;
                $_SESSION['debug_resets'] = (int)$_SESSION['debug_resets'] + 1;
            } else {
                $_SESSION['dmsg'] .= $ret;
            }
        }
        return $ret;
    }

    public static function dTrace() {

        $str = "<br />[dTrace]";
        foreach (debug_backtrace() as $row) {
            $str .= "<br />FILE: " . $row['file'] . " FUNC: " . $row['function'] . " LINE: " . $row['line'] . " ARGS: " . print_r($row['args'], true);
        }
        return $str;
    }

    function getURI() {
        
        if (empty($_SERVER['REQUEST_URI'])) {
            (!empty($_SERVER['QUERY_STRING'])) ? $qs = "?" . $_SERVER['QUERY_STRING'] : $qs = "";
            $path_parts = pathinfo($_SERVER['PHP_SELF']);
            $uri = $path_parts['basename'] . $qs;
            if (substr($uri, 0, 1) <> "/")
                $uri = "/" . $uri; // make it looks like REQUEST_URI
        } else {
            $uri = $_SERVER['REQUEST_URI'];
        }
        return $uri;
    }

    function relRoot($adj = "") {

        $levels = substr_count($_SERVER['PHP_SELF'], '/');
        $root = '';
        for ($i = 1; $i < $levels - $adj; $i++) {
            $root .= '../';
        }
        return ($root);
    }

    function rootPath($path = "") {

        $path_parts = pathinfo($_SERVER['PHP_SELF']);
        $return = realpath(dirname(realpath($path_parts['basename'])) . "/" . $path);
        return $return;
    }

      public static   function rootSite() {

        //    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        //    $dirname = preg_replace('/\\\+/', '/', dirname(realpath($uri)));
        $dirname = preg_replace('/\\\+/', '/', dirname($_SERVER['PHP_SELF']));
        if (substr($dirname, -1) == "/")
            $dirname = substr($dirname, 0, strlen($dirname) - 1); // remove / if it is there to be consistent for URL
        return $dirname;
    }

        public static function siteURL() {

        $protocol = "http://";
        if (!empty($_SERVER['HTTPS']))
            (strtolower($_SERVER['HTTPS']) == "on") ? $protocol = "https://" : $protocol = "http://";
        (!empty($_SERVER['SERVER_PORT']) and $_SERVER['SERVER_PORT'] <> "443" and $_SERVER['SERVER_PORT'] <> "80") ? $port = ":" . $_SERVER['SERVER_PORT'] : $port = "";
        return $protocol . $_SERVER['SERVER_NAME'] . $port;
    }

    public static function selfURL() {

        return self::siteURL() . self::rootSite();
    }

    function array2InsStr($iArray) {

        $value = '"' . implode('", "', array_values($iArray)) . '"'; // must use this in case quote in the name
        $name = implode(", ", array_keys($iArray));
        // return (jvid, title, maker, explanation, acctmo, prep_date, phone ) VALUES ("1","Title","Maker","Explanation","15","2/11/2011","87878")        
        return "($name) VALUES ($value)";
    }

    function array2UptStr($iArray, $checkNumArray = array()) {

        $str = "";
        while (list($key, $val) = each($iArray)) {
            if (isset($checkNumArray[$key]) and $key == $checkNumArray[$key] and empty($val)) {
                $val = "0"; // set to "0" only in the $checkNumArray and is empty
            }
            $str .= $key . ' ="' . $val . '", ';
        }
        // return maker= 'Name', acctmo= '15', prep_date= '11/2/2011', phone= '87878', explanation= 'Some Text', title='Some Title'
        return substr($str, 0, strlen($str) - 2); // take out comma and space
    }

    function array2Str($iArray) {

        $str = "";
        while (list($key, $val) = each($iArray)) {
            if (empty($val))
                $val = "0"; // set to 0 if null
            $str .= $val . ", ";
        }
        return substr($str, 0, strlen($str) - 2); // take out comma and space
    }

    function splitArray($jvArchiveDir, $type) {

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

    public static function getSafeVar($iVar, $name, $itype = "txt", $retchar = "") {

        $ret = "";
        if (!empty($iVar["$name"])) {
            $ret = self::clean($iVar["$name"], $itype, $retchar);
        } 
        if (empty($ret) and ! empty($retchar)) {
            $ret = $retchar;
        }
        return $ret;
    }  
    

    function slug($z) { // remove anything that not in the list
        $z = strtolower(trim($z));
        $z = preg_replace('/[^a-z0-9 \'-]+/', '', $z);
        return $z;
    }

    public static function clean($str, $itype = "txt", $retchar = "") {

        switch ($itype) {
            case "quote":
                (!empty($str)) ? $ret = str_replace("'", "''", str_replace("\'", "'", $str)) : $ret = "";
                break;
            case "email":
                $ret = filter_var($str, FILTER_SANITIZE_EMAIL);
                $ret = @filter_var($ret, FILTER_SANITIZE_STRING); // try to catch single quote
                break;
            case "num":
                $ret = filter_var($str, FILTER_SANITIZE_NUMBER_INT);
                break;
            case "txt":
                $ret = @filter_var($str, FILTER_SANITIZE_STRING);
                $ret = self::escapeStr($ret); // strip out none ascii chars
// gemini change, review later                $ret = self::escapeStr(htmlspecialchars($str, ENT_QUOTES, 'UTF-8'));
                break;
            case "amt":
                $patterns = array('/[^0-9.]/');
                $ret = sprintf("%1.2f", preg_replace($patterns, '', $str));
                if (strlen($ret) > 10) {
                    $ret = 0;
                }
                break;
            default:
            case "raw":
                $ret = filter_var($str, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH); // keep tab and return
                break;
        }
        if (empty($ret) and ! empty($retchar)) {
            $ret = $retchar;
        }
        return $ret;
    }


    function cleanArray($iVar) {

        foreach ($iVar as $k => $v) {
            $eachpost[$k] = self::getSafeVar($iVar, $k);
        }
        return $eachpost;
    }

    function cleanAmt($amt) {

        $ret = sprintf("%1.2f", self::clean($amt) / 100, "num");
        if (strlen($ret) > 11)
            $ret = 0;
        return $ret;
    }

    function getParm($iVar) {

        return strtolower(self::getSafeVar($_GET, $iVar));
    }

    function getLdapByType($iType = 'email', $iValue = null) {

        if (!class_exists('CLdap'))
            include("cldap.php");
        $ldap = new CLdap();
        $retArray = $ldap->$iType($iValue);
        return $retArray;
    }

    function sendAttachment($subject, $sendto, $replyto, $message, $htmlfile) {

        $mimetype = "text/plain";
        $mailfile = new CMailfile($subject, $sendto, $replyto, $message, $htmlfile, $mimetype);
        $mailfile->sendfile();
    }

    function trimSpaces($str) {

        while (sizeof($array = explode("  ", $str)) != 1) { // trim any where not just begin or ending
            $str = implode(" ", $array);
        }
        return $str;
    }

    function expiredCookie() {

        setcookie('id_hash', '', (time() - 3600), '/', '', 0); // 1 hr ago, expired cookie
        session_unset(); // move here from logout.php
    }

    function user_getname() {

        if (!empty($_SESSION['LOGGED_IN'])) {
            return $GLOBALS['user_name'];
        } else {
            //look up the user some day when we need it
            return ' ERROR - Not Logged In ';
        }
    }

    function fwriteStream($fp, $string) {

        for ($written = 0; $written < strlen($string); $written += $fwrite) {
            $fwrite = fwrite($fp, substr($string, $written));
            if ($fwrite === false) {
                return $written;
            }
        }
        return $written;
    }

    function YN($str) {

        ($str == 'Y') ? $str = 'N' : $str = 'Y';
        return $str;
    }

    function properName($str) {
        
        (!empty($str)) ? $ret = ucwords(trim(strtolower($str))) : $ret = "";
        return $ret;
    }

    function padHtml($strInput = "", $strLength = 0, $padStr = "&nbsp;", $padType = STR_PAD_RIGHT) {
    
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

    function fDirName($fspec) {

        $path_parts = pathinfo($fspec);
        (!empty($path_parts['dirname'])) ? $ret = $path_parts['dirname'] : $ret = "";
        return $ret;
    }

    static function fName($fspec) {

        $path_parts = pathinfo($fspec);
        (!empty($path_parts['filename'])) ? $ret = $path_parts['filename'] : $ret = "";
        return $ret;
    }

    function fExt($fspec) {

        $path_parts = pathinfo($fspec);
        (!empty($path_parts['extension'])) ? $ret = $path_parts['extension'] : $ret = "";
        return $ret;
    }

    function virtualFolder2RealFolder($folderPath) {

        if (!empty($folderPath)) {
            $chkFolderPathArray = explode("/", $folderPath);
            $folderPathSpec = implode("/", $chkFolderPathArray);
            if (!empty($folderPathSpec) and $folderPathSpec <> "/") {
                $folderPath = realpath($folderPathSpec);
            }
        }
        return $folderPath;
    }

    function realFolder2VirtualFolder($dirname, $realFolder2Hide) { 

        $fpath = strtolower($dirname);
        $pos = strpos($fpath, $realFolder2Hide);
        $chkFolderPathArray = explode("/", substr($fpath, $pos + strlen($realFolder2Hide)));
        $return = substr(implode("/", $chkFolderPathArray), 1); // skip the first /
        return $return;
    }

    function winUser() {

        $amcid = "";
        $eadUserName = $_SERVER['LOGON_USER']; // get Windows User ID without domain name
        if (!empty($eadUserName)) {
            $winUser = explode("\\", $eadUserName);
            array_shift($winUser);
            list($amcid) = $winUser;
        }
        return $amcid; // get Windows User ID without domain name
    }

    function uploadFiles($files, $targetFolder, $allowedExtensions) {

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

    function uploadFile($file, $targetFolder, $allowedExtensions) {

        list($fileName, $fileType, $fileTemp, $fileErr, $fileSize ) = $file;
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

    function isAllowedExtension($fileName, $allowedExtensions) {

        $fArray = explode(".", $fileName);
        return in_array(end($fArray), $allowedExtensions);
    }

    function splitWords($string, $max = 1) {

        $words = preg_split('/\s/', $string);
        $lines = array();
        $line = '';
        foreach ($words as $k => $word) {
            $length = strlen($line . ' ' . $word);
            if ($length <= $max) {
                $line .= ' ' . $word;
            } else if ($length > $max) {
                if (!empty($line))
                    $lines[] = trim($line);
                $line = $word;
            } else {
                $lines[] = trim($line) . ' ' . $word;
                $line = '';
            }
        }
        $lines[] = ($line = trim($line)) ? $line : $word;
        return $lines;
    }

    function splitLongLine($string, $maxWords = 96) { // fit 1024 screen size

        $oline = "";
        $strArry = explode("\n\r", $string);
        foreach ($strArry as $line) {
            (strlen($line) > $maxWords) ? $strArray = self::splitWords($line, $maxWords) : $strArray = array();
            (!empty($strArray)) ? $oline .= implode("\n\r", $strArray) : $oline .= $line;
        }
        return $oline;
    }

    function delTree($dir) {

        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir")
                        self::delTree($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    function getDateArrayFromFiles($listDir) {

        $dateA = array();
        foreach ($listDir as $fspec) {
            $path_parts = pathinfo($fspec);
            $filename = $path_parts['basename'];
            $whichDate = date("Y/m/d", filemtime($fspec));
//      $dateA[$whichDate] = $filename;
            $dateA[$whichDate] = $whichDate;
        }
        return $dateA;
    }

    // TODO not working yet!  not sort correctly
//  function getFirstLastDate($dateA,$dFormat="m/d/y") {
    function getFirstLastDate($dateA, $dFormat = "Y/m/d") {

//    $fDate = $lDate = date("m/d/y",time());
        $fDate = $lDate = date($dFormat, time());
        asort($dateA); // TODO
        $i = 0;
        $maxItem = count($dateA);
        foreach ($dateA as $k => $one) {
            if ($i == 0)
                $firstDate = $k;
            if ($i == $maxItem - 1)
                $lastDate = $k;
            $i++;
        }
        $fDate = date($dFormat, strtotime($firstDate));
        $lDate = date($dFormat, strtotime($lastDate));
        return array($fDate, $lDate);
    }

    function dateAdd($iDate, $iDays, $dateFormat = 'Y/m/d') {
        return date($dateFormat, strtotime("$iDays day" . $iDate));
    }

    function br2nl($string) {

        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }

    // must test this

    public static function escapeStr($inp) {

        if (is_array($inp)) {
            return array_map(__METHOD__, $inp); // use call back to itself to replace when it is array
        }
        if (!empty($inp) && is_string($inp)) {
            $badchr = array(
                "\xc2", // prefix 1
                "\x80", // prefix 2
                "\x98", // single quote opening
                "\x99", // single quote closing
                "\x8c", // double quote opening
                "\x9d", // double quote closing
                "\x96", // En dash
                "\x97"  // Em dash
            );
            $goodchr = array('', '', '\'', '\'', '"', '"', '-', '-');
            $inp = str_replace($badchr, $goodchr, $inp);
        }
        return $inp;
    }
    
    // Fixes MAGIC_QUOTES
    function fixSlashes($arr = '') {

        if (is_null($arr) || $arr == '')
            return null;
        if (!get_magic_quotes_gpc())
            return $arr;
        return is_array($arr) ? array_map(__METHOD__, $arr) : stripslashes(trim($arr));
    }

    function add2SessVar($iVar, $msg) {
        (!empty($_SESSION[$iVar])) ? $_SESSION[$iVar] .= " $msg" : $_SESSION[$iVar] = $msg;
    }

    public static function qsValue() {

        if (empty($_SERVER['QUERY_STRING']))
            return;
        // qs: ?t=users&a=login (key paired) or ?p=/users/login (path)
        $arr = array();
        $retUrl = "";
        $qs = $_SERVER['QUERY_STRING'];
        // if not login, set return url to redirect to login screen and this code works, don't change
        $retArr = explode("&r=", $qs, 2); // if r= in qs then ensure rs= is in the last element
        if (count($retArr) > 1) { // if r= is found then set return url
            $retUrl = array_pop($retArr); // save retURL
            $qs = array_pop($retArr); // change qs to exclude retURL
        }
        // if not login, set return url to redirect to login screen and this code works, don't change
        $taskArr = explode("t=", $qs); // ensure at least there is t=
        if (count($taskArr) > 1) {
            parse_str($qs, $arr);
            $arr = array_map('strtolower', $arr);
            $arr = array_map('trim', $arr);
        } else {
            $arr = array('t' => $qs); // if no t then it is from front controller, patch in t
            if (!empty($retUrl))
                $arr['r'] = $retUrl;
        }
//        self::pln($arr, "qs");
        return $arr;
    }

    /* 
     * utilize debug default to br
     * @param $ivar $istr $iformat  
     * @return string 
     */ 
    public static function pln($iVar, $iStr = "", $iFormat = "br") {
    
        print self::debug($iVar, $iStr, $iFormat);
    }

    public static function aliasLookup($app, $aliases) {
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
            $declaredIn  = (new \ReflectionClass($m->class))->getShortName(); // Get short name of the class where the method is declared
            $targetClass = (new \ReflectionClass($class_name))->getShortName(); // Get short name of the target class
            if (strtolower($declaredIn) == strtolower($targetClass)) {
                $ret = true;
            }
        }
        return $ret;
    }    
    public static function not_ns_methodNotParent($class_name, $method_name) {   

        $ret = false;
        $class = new \ReflectionClass($class_name);
        if ($class->hasMethod($method_name)) {
            $m = $class->getMethod($method_name);
            if ($m->class == ucfirst($class_name) ){
                $ret = true;
            }
        }
        return $ret;
    }

    public static function methodlist($className) {
        
        $methods = get_class_methods($className);
        print CUtil::debug($methods, $className.':methods','p');        
    }   

    public static function parseQs($routes, $className=self::class) {

        $qsArr = CUtil::qsValue(); // current qs ex: t=front&a=index, bad qs and got 404 before got here
//        print CUtil::debug($qsArr, __METHOD__.':qs','p');  
//        print CUtil::debug($className,'class');      
        $args = $qsArr;
        if (!empty($args['t']) and $luArr = CUtil::aliasLookup($args['t'], $routes['alias'] )) {  
            $args = $luArr;
//            print CUtil::debug($args, ':aft-alias');  
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

    public static function dir2Array($dir, $recursive=false) { 

        $oArray = [];
        $cdir = scandir($dir); 
        foreach ($cdir as $key => $value) { 
            if (!in_array($value,array(".","..")))  { 
                if ((is_dir($dir . DIRECTORY_SEPARATOR . $value)) and $recursive==true) {
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
   
    public static function getClass($className) { 

        if (class_exists($className)) { 
            return new $className();
        }    
    }

    public static function filesListNameOnly($dir, $ext) { 

        $l = array();
        foreach (array_diff(scandir($dir),array('.','..')) as $f)
            if (is_file($dir.'/'.$f)
                && (($ext)?(preg_match("/$ext$/i", $f)):1))

                $l[]=self::fName($f);

        return $l;
    }

    public static function filesList($dir, $ext) { 

        $l = array();
        foreach (array_diff(scandir($dir),array('.','..')) as $f)
            if (is_file($dir.'/'.$f)
                && (($ext)?(preg_match("/$ext$/i", $f)):1))

                $l[]=$f;

        return $l;
    }

    public static function dirsList($dir) { 

        $l = array();
        foreach(array_diff(scandir($dir),array('.','..')) as $f)
            if(is_dir($dir.'/'.$f))
                $l[]=$f;

            return $l;
    }


}


