<?php

namespace MvcLite;

// use SelectPdf;
use Exception;
use stdClass;

/**
 * CSecs class translated from C#
 */
class CSecs extends CCore
{
    /**
     * @var string
     */
    public $Title;

    /**
     * Preserves logic for checking if web login is allowed
     * @return bool
     */
    public static function IsWebloginAllowed()
    {
        $ret = false;
        $webloginList = self::getAppTxt("weblogin", "L");
        // CMsg::_pdmsg($webloginList, "web");
        $usrname = self::winUser();
        if (CUtil::isEqInList($usrname, $webloginList, '|') == true || self::getAppTxt("logintype", "L") == "web") {
            // CMsg::_pdmsg($usrname, "webusr");
            $ret = true; // is winuser also allow weblogin
        }
        return $ret;
    }

    /**
     * Checks if current user is an integrity user
     * @return bool
     */
    public static function isIntrgUser()
    {
        $ret = false;
        $usrname = self::winUser();
        $usrgrp = self::getUserGroup($usrname);

        CMsg::_pdmsg($usrname . "-" . $usrgrp, "isIntrgUser");
        // CMsg::_pdmsg(static::$_usr, "_usr");

        if ($usrname != "" && $usrgrp != null && strlen($usrgrp) > 0) {
            CMsg::_pdmsg(static::$_usr, "isIntrgUser");
            $ret = true;
        }
        return $ret;
    }

    /**
     * Checks if user is not authorized
     * @return bool
     */
    public static function IsNotAuthorized()
    {
        $ret = false;
        CMsg::_pdmsg(static::$_usr, "IsNotAuthorized");
        if (self::IsWebloginAllowed() == false && self::isIntrgUser() == false) {
            $ret = true;
        }
        return $ret;
    }

    /**
     * Checks if user is authorized
     * @return bool
     */
    public static function IsAuthorized()
    {
        $ret = false;
        CMsg::_pdmsg(static::$_usr, "IsAuthorized");
        if (self::IsWebloginAllowed() == true || self::isIntrgUser() == true) {
            $ret = true;
        }
        return $ret;
    }

    /**
     * Gets Windows User identity
     * @return string
     */
    public static function winUser_good()
    {
        $wUser = strtolower(HttpContext::Current()->User()->Identity()->Name());
        $ret = CString::subStrRt($wUser, "\\");
        return $ret;
    }

    /**
     * Gets Windows User or NetID
     * @return string
     */
    public static function winUser()
    {
        $wUser = strtolower(HttpContext::Current()->User()->Identity()->Name()); // get AMC\userid
        // bad? crash in isDebug() CMsg::_pdmsg($wUser, "wUser-b");
        $ret = CString::subStrRt($wUser, "\\"); // AMC\userid or netid@washington.edu if using Shibboleth
        $ret = (CString::IsEmpty($ret)) ? self::uwNetID($wUser) : $ret; // if NOT AMC then get the email from string
        return $ret;
    }

    /**
     * Extracts NetID from email
     * @param string $email
     * @return string
     */
    public static function uwNetID($email) // Shibboleth2 Request["eppn"] = uwnetid@washington.edu
    {
        $wUser = strtolower($email);
        $ret = CString::subStrLt($wUser, "@");
        return $ret;
    }

    /**
     * Gets users from configuration
     * @return array
     */
    public static function getUsers()
    {
        $infoa = [];
        if (static::$_cfg != null) {
            if (isset(static::$_cfg["users"])) {
                $infoa = static::$_cfg["users"];
            }
        }
        return $infoa;
    }

    /**
     * Gets user info/passwords from configuration
     * @return array
     */
    public static function getPasswords()
    {
        $infoa = [];
        if (static::$_cfg != null) {
            if (isset(static::$_cfg["uinfo"])) {
                $infoa = static::$_cfg["uinfo"];
            }
        }
        return $infoa;
    }

    /**
     * Gets groups from configuration
     * @return array
     */
    public static function getGroups()
    {
        $infoa = [];
        if (static::$_cfg != null) {
            if (isset(static::$_cfg["groups"])) {
                $infoa = static::$_cfg["groups"];
            }
        }
        return $infoa;
    }

    /**
     * Maps groups info to users
     * @return array
     */
    public static function groupsInfo2Users()
    {
        $usr = "";
        $gArray = [];
        $infoa = [];
        $wusr = self::winUser(); // Call once outside the loop for performance

        if (static::$_cfg != null) {
            if (isset(static::$_cfg["defgrpswusers"])) {
                // load default groups with users into an array
                $gArray = static::$_cfg["defgrpswusers"];
                foreach ($gArray as $grp => $value) {
                    $uInfo = CUtil::Str2a(',', $gArray[$grp]); // userlist to user array
                    foreach ($uInfo as $s) {
                        $wusr = self::winUser();
                        if ($s != "") {
                            $usr = ($s == "*") ? $wusr : $s;
                            // CMsg::_pdmsg($usr . ":" . $grp, "u:g");
                            // CMsg::_pdmsg($infoa[$usr], "infoa");

                            if ((!isset($infoa[$usr]) || $infoa[$usr] != $grp) // add group if not exist or greater
                                && self::getGroupNo($grp) >= self::getGroupNo(isset($infoa[$usr]) ? $infoa[$usr] : null) // if usr has more than one group take the higher group
                            ) {
                                $infoa[$usr] = $grp; // add user highest group info
                                /*
                                CMsg::_pdmsg($usr+":"+$grp, "u:g");
                                CMsg::_pdmsg($infoa[$usr], "infoa[usr]");
                                */
                            }
                        }
                    }
                }
                // user should only has one highest group
                // CMsg::_pdmsg($infoa, "gInfoa");
            }
        }
        return $infoa;
    }

    /**
     * Gets group for a specific user
     * @param string $usrname
     * @return string
     */
    public static function getUserGroup($usrname)
    {
        $grp = "";
        $infoa = [];
        if (static::$_cfg != null) {
            if (!isset(static::$_cfg["users"])) {
                self::setUsersInfo(); // make sure _cfg["users"] is set
            }
            if (isset(static::$_cfg["users"])) {
                $infoa = static::$_cfg["users"]; // the final users with group
                // CMsg::_pdmsg($infoa, "getUserGroup"); // the final users with group
            } else {
                CMsg::_pdmsg(static::$_cfg["users"] ?? null, "getUserGroup-else"); // the final users with group
            }
        }
        // need to get the highest group from this??
        foreach ($infoa as $s => $value) {
            if ($usrname != "" && $s == $usrname) {
                $grp = $infoa[$s];
                /*         
                _pln($s);
                _pln($grp);
                */
                CMsg::_pdmsg($s . ":" . $grp, "getUserGroup-u:g");
            }
        }
        return $grp;
    }

    /**
     * Gets password for a specific user
     * @param string $usrname
     * @return mixed
     */
    public static function getUserPassword($usrname)
    {
        $infoa = [];
        if (static::$_cfg != null) {
            if (isset(static::$_cfg["uinfo"])) {
                $infoa = static::$_cfg["uinfo"];
            }
        }
        return isset($infoa[$usrname]) ? $infoa[$usrname] : null;
    }

    /**
     * Gets password for a specific user (alias)
     * @param string $usrname
     * @return mixed
     */
    public static function getPassword($usrname)
    {
        $pInfoa = self::getPasswords();
        return isset($pInfoa[$usrname]) ? $pInfoa[$usrname] : null;
    }

    /**
     * Gets info for a group
     * @param string $grpname
     * @return mixed
     */
    public static function getGroup($grpname)
    {
        $pInfoa = self::getGroups();
        return isset($pInfoa[$grpname]) ? $pInfoa[$grpname] : null;
    }

    /**
     * Gets numeric value for a group
     * @param string $grpname
     * @return int
     */
    public static function getGroupNo($grpname)
    {
        return (int)(self::getGroup($grpname));
    }

    /**
     * Prints group info
     */
    public function prtGroups()
    {
        CMsg::_dprt(self::getGroups(), "ga");
    }

    /**
     * Sets user information list
     * @param array $UsrNv
     */
    public static function setUsrsInfo($UsrNv)
    {
        if (count(static::$_usrs) > 40) {
            static::$_usrs = []; // reset
        }
        $UsrInfo = [];
        if ($UsrNv != null && count($UsrNv) > 0) {
            // Iterate through the collection.
            foreach (array_keys($UsrNv) as $s) {
                if (CString::IsEmpty($s) == false && CString::IsEmpty($UsrNv[$s]) == false) {
                    $UsrInfo[$s] = $UsrNv[$s]; // add each appid, usrname, usrgroup, usrgrpno, usrentity
                }
            }
            static::$_usrs[] = $UsrInfo; // set static _usrs
        }
    }

    /**
     * Sets current login info for user
     * @param string $app
     * @param string $usrname
     * @param string $usrentity
     */
    public static function setUserLoginInfo($app, $usrname, $usrentity = "")
    {
        CMsg::_dmsg($usrname, "setUserLoginInfo-usrname");
        $UsrNv = [];
        if (CString::IsEmpty($usrname) == false) {
            $UsrNv = self::getUserInfo($usrname);
            $UsrNv["appid"] = $app;
            $UsrNv["usrentity"] = $usrentity;
            CMsg::_dmsg($UsrNv, "setUserLoginInfo");
            if (isset($UsrNv["usrname"]) && CString::IsEmpty((string)$UsrNv["usrname"]) == false) {
                static::$_usr = $UsrNv; // set static _usr
                self::setUsrsInfo($UsrNv); // for showing in UInfo view
                $_SESSION["usrname"] = $UsrNv["usrname"];
                $_SESSION["usrgroup"] = $UsrNv["usrgroup"];
                $_SESSION["usrentity"] = $UsrNv["usrentity"];
                $_SESSION["uinfo"] = $UsrNv;
                CMsg::_dmsg($UsrNv, "setUserLoginInfo-usr");
            }
        } else {
            CMsg::_dmsg($usrname, "setUserLoginInfo-else");
        }
    }

    /**
     * Formats user info text for display
     * @return string
     */
    public static function setUsrInfoText()
    {
        $usrInfoText = "";
        static::$_usr = CUtil::getSessNv("uinfo"); // use session instead
        CMsg::_dmsg(static::$_usr, "setUsrInfoText");
        if (is_array(static::$_usr)
            && !empty(static::$_usr["usrname"])
            && !empty(static::$_usr["usrgroup"])
            && !empty(static::$_usr["usrgrpno"])
        ) // return if null
        {
            /*
            $usrname = static::$_usr["usrname"];
            $usrgrp = static::$_usr["usrgroup"];
            $usrgrpno = static::$_usr["usrgrpno"];
            if ($usrname != "" && $usrgrp != "" && $usrgrpno != "")
            {
            */
            $usrentity = isset(static::$_usr["usrentity"]) ? static::$_usr["usrentity"] : "";
            $sentity = ($usrentity != "") ? ":" . $usrentity : "";
            $loggedIn = "[" . static::$_usr["usrname"] . $sentity . "]";
            $usrInfoText = $loggedIn . " (" . static::$_usr["usrgroup"] . ":" . static::$_usr["usrgrpno"] . ")"; // setUsrInfoText
            static::$_usr["loggedin"] = $usrInfoText; // IMPORTANT need this for the login to work

            // $_SESSION["entity"] = $usrentity;
            $_SESSION["loggedin"] = $loggedIn;
            $_SESSION["uinfotxt"] = $usrInfoText;
            // }
        }
        return $usrInfoText;
    }

    /**
     * Gets user information bundle
     * @param string $usrname
     * @return array
     */
    public static function getUserInfo($usrname)
    {
        $UsrInfo = [];
        $UsrInfo["usrname"] = $usrname;
        $UsrInfo["usrgroup"] = self::getUserGroup($usrname);
        $UsrInfo["usrgrpno"] = self::getGroup($UsrInfo["usrgroup"]);
        $UsrInfo["usrpw"] = self::getUserPassword($usrname);
        CMsg::_dmsg($UsrInfo, "getUserInfo");

        return $UsrInfo;
    }

    /**
     * Converts login info to list item HTML
     * @return string
     */
    public static function loginInfo2Li()
    {
        $loggedIn = "";
        $Link = array_fill(0, 2, null);
        if (self::IntgOrWeb() === true) {
            static::$_usr = CUtil::getSessNv("uinfo"); // use session instead
            CMsg::_pdmsg(static::$_usr, "loginInfo2Li");

            if (static::$_usr != null && !CString::IsEmpty(isset(static::$_usr["loggedin"]) ? static::$_usr["loggedin"] : null)) { // need to change this to session
                $loggedIn = static::$_usr["loggedin"];
            }
            if (CString::IsEmpty($loggedIn) == false) {
                $Link[0] = "Logout";
                $Link[1] = CUtil::tap(self::getAppTxt("urllogout"));
            } else {
                $Link[0] = "Login";
                $Link[1] = CUtil::tap(self::getAppTxt("urllogin"));
            }
        }
        CMsg::_pdmsg($loggedIn, "loginInfo2Li");
        return "</a ></li><li><a href=\"" . $Link[1] . "\">" . $Link[0] . "</a></li>";
    }

    /**
     * Checks login type
     * @return bool
     */
    public static function IntgOrWeb()
    {
        $ret = false;
        if (self::isIntrgUser() == true || self::getAppTxt("logintype", "L") == "web") {
            $ret = true;
        }
        return $ret;
    }

    /**
     * Validates if user has access to task
     * @param string $task
     * @param string $usrname
     * @return bool
     */
    public static function IsTaskUsrOk($task, $usrname)
    {
        $allowAccess = false;
        if (CString::IsEmpty($task) == false && CString::IsEmpty($usrname) == false) {
            $tgrp = CUtil::TaskGroup($task);
            static::$_usr = CUtil::getSessNv("uinfo"); // use session instead
            if (self::IsUsrGrpComp(static::$_usr["usrgroup"] ?? null, $tgrp, ">=") == true) {
                // CMsg::_pdmsg($tgrp, "IsTaskUsrOk");
                $allowAccess = true;
            }
        }
        return $allowAccess;
    }

    /**
     * Validates menu access
     * @param array $mnua
     * @param string $mnuType
     * @return bool
     */
    public static function isUsrHasAccess2Mnu($mnua, $mnuType)
    {
        $allowAccess = false;
        $usrname = "";
        static::$_usr = CUtil::getSessNv("uinfo"); // use session instead

        if (static::$_usr != null && !CString::IsEmpty(isset(static::$_usr["usrname"]) ? static::$_usr["usrname"] : null)) {
            $usrname = static::$_usr["usrname"];
        }

        $sCtl = "";
        $qsa = CUtil::qs2nv();
        if ($qsa != null && count($qsa) > 0) {
            $keys = array_keys($qsa);
            $sCtl = strtolower((string)$qsa[$keys[0]]);
        }
        $mnuUrl = CUtil::qs2nv($mnua[0]); // get the url from menu array
        $mnuKeys = array_keys($mnuUrl);
        $task = strtolower((string)$mnuUrl[$mnuKeys[0]]);
        if (
            self::IsTaskUsrOk($task, $usrname) == true
            && ($mnuType == $sCtl || $mnuType == CCore::getSelectedViewSet()) // only show menu items and submenu items of the current view
        ) {
            // CMsg::_pdmsg($task, "mtask");
            // CMsg::_pdmsg($usrname, "usrname");

            $allowAccess = true;
        }
        return $allowAccess;
    }

    /**
     * Checks if menu has public access
     * @param array $mnua
     * @param string $mnuType
     * @return bool
     */
    public static function isPublicAccess4Mnu($mnua, $mnuType)
    {
        $allowAccess = false;
        $sCtl = "";
        $qsa = CUtil::qs2nv();
        if ($qsa != null && count($qsa) > 0) {
            $keys = array_keys($qsa);
            $sCtl = strtolower((string)$qsa[$keys[0]]);
        }
        $mnuNameReqGrp = CUtil::TaskGroup($sCtl);
        $mnuGrp = CUtil::TaskGroup($mnuType);
        $mnuUrl = CUtil::qs2nv($mnua[0]); // get the url from menu array
        $mnuUrlKeys = array_keys($mnuUrl);
        $mUrlGrp = CUtil::TaskGroup($mnuUrl[$mnuUrlKeys[0]]);
        $selview = CCore::getSelectedViewSet();
        if (
            CString::IsEmpty($mnuGrp) // when url at home no controller
            // && CString::IsEmpty($mnuNameReqGrp) // mnu not require login or public, comment out since it might not be empty on the sCtrl
            && CString::IsEmpty($mUrlGrp)
            && ($mnuType == $sCtl || $mnuType == $selview) // DON'T TOUCH, only show menu items and submenu items of the current view
        ) {
            $allowAccess = true;
        }
        /*
        else
        {
          CMsg::_pdmsg($mnuNameReqGrp, "mnuNameReqGrp");
          CMsg::_pdmsg($mUrlGrp, "mUrlGrp-else");
          CMsg::_pdmsg($mnuUrl, "mnuUrl-lnka");
          CMsg::_pdmsg($mnuGrp, "mnuGrp");
          CMsg::_pdmsg($sCtl, "sCtl");
          CMsg::_pdmsg($selview, "selview");
          CMsg::_pdmsg($mnuType, "mnuType");
        }
        */
        return $allowAccess;
    }

    /**
     * Computes MD5 hash
     * @param string $input
     * @return string
     */
    public static function GetMd5Hash($input)
    {
        // MD5 md5Hash = MD5.Create();
        // Convert the input string to a byte array and compute the hash.
        $data = unpack('C*', md5($input, true));

        // Create a new Stringbuilder to collect the bytes
        // and create a string.
        $sBuilder = "";

        // Loop through each byte of the hashed data 
        // and format each one as a hexadecimal string.
        foreach ($data as $byte) {
            $sBuilder .= sprintf("%02x", $byte);
        }

        // Return the hexadecimal string.
        return $sBuilder;
    }

    // string source = "Hello World!";
    // Verify a hash against a string.
    // VerifyMd5Hash(md5Hash, source, hash))
    /**
     * Verifies MD5 hash
     * @param string $input
     * @param string $hash
     * @return bool
     */
    public static function VerifyMd5Hash($input, $hash)
    {
        // Hash the input.
        $hashOfInput = self::GetMd5Hash($input);

        // Create a StringComparer and compare the hashes.
        if (strcasecmp($hashOfInput, $hash) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Initializes user info from configuration
     */
    public static function setUsersInfo()
    {
        // set users and uinfo from default
        // static::$_cfg["users"] = static::$_cfg["defusers"];
        static::$_cfg["uinfo"] = static::$_cfg["defuinfo"];

        $gInfoa = [];
        $infoa = [];

        // infoa = static::$_cfg["users"];
        // CMsg::_pdmsg($infoa, "infoa-b");
        $gInfoa = self::groupsInfo2Users(); // load group info into _cfg users array

        // CMsg::_pdmsg($gInfoa, "gInfoa");

        if ($gInfoa != null) {
            $infoa = array_merge($infoa, $gInfoa);
        }
        static::$_cfg["users"] = $infoa;
        CCore::IsLoginedUser(); // restore from cookie?, need to think more about this

        // CMsg::_pdmsg($infoa, "setUsersInfo");
    }

    /**
     * Placeholder check for approver logic
     * @param string $usrname
     * @return bool
     */
    public static function NOT_USE_IsApprover($usrname)
    {
        $allowAccess = false;

        // CMsg::_pdmsg($usrname, "usrname");
        if (CString::IsEmpty($usrname) == false) {
            static::$_usr = CUtil::getSessNv("uinfo"); // use session instead

            $iusrgrpno = self::getGroupNo(isset(static::$_usr["usrgroup"]) ? static::$_usr["usrgroup"] : null);
            // int iusrgrpno = (int) static::$_usr["usrgrpno"];
            $imnureqgrpno = self::getGroupNo("user");
            /*
            CMsg::_pdmsg($iusrgrpno, "iusrgrpno");
            CMsg::_pdmsg($imnureqgrpno, "imnureqgrpno");
            */
            // is usr login, usrgrp above "user" group number
            if ($iusrgrpno > 0 && $iusrgrpno >= $imnureqgrpno) {
                $allowAccess = true;
            }
        }
        return $allowAccess;
    }

    /**
     * Compares user group levels
     * @param string $usrgrp
     * @param string $grp
     * @param string $opr
     * @return bool
     */
    public static function IsUsrGrpComp($usrgrp, $grp, $opr = ">")
    {
        $allowAccess = false;
        if ((CString::IsEmpty($usrgrp) == false) && CString::IsEmpty($grp) == false) {
            $iusrgrpno = self::getGroupNo($usrgrp);
            $imnureqgrpno = self::getGroupNo($grp);
            /*
            CMsg::_pdmsg($iusrgrpno, "iusrgrpno");
            CMsg::_pdmsg($imnureqgrpno, "imnureqgrpno");
            */
            // is usr login, usrgrp above "user" group number
            if ($iusrgrpno > 0) {
                switch ($opr) {
                    case ">=":
                        if ($iusrgrpno >= $imnureqgrpno) {
                            // CMsg::_pdmsg($opr, "opr");
                            $allowAccess = true;
                        }
                        break;
                    case "<=":
                        if ($iusrgrpno <= $imnureqgrpno) {
                            // CMsg::_pdmsg($opr, "opr");
                            $allowAccess = true;
                        }
                        break;
                    case "==":
                        if ($iusrgrpno == $imnureqgrpno) {
                            // CMsg::_pdmsg($opr, "opr");
                            $allowAccess = true;
                        }
                        break;
                    case ">":
                        if ($iusrgrpno > $imnureqgrpno) {
                            // CMsg::_pdmsg($opr, "opr");
                            $allowAccess = true;
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return $allowAccess;
    }

    /**
     * Validates user level based on session
     * @param string $grp
     * @param string $opr
     * @return bool
     */
    public static function IsUsrLevel($grp, $opr = ">=")
    {
        static::$_usr = CUtil::getSessNv("uinfo"); // use session instead

        return self::IsUsrGrpComp(isset(static::$_usr["usrgroup"]) ? static::$_usr["usrgroup"] : null, $grp, $opr);
    }
}
