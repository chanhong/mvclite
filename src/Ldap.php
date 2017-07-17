<?php

/**
 * @author Chanh Ong
 * @package 
 * @since 2.0
 */
/*
 * File: CLdap.php
 * By: Chanh Ong
 * Purpose: Simple class to do ldap lookup by name or by email
 * Date: 11/17/2011
 */
namespace MvcLite;

class Ldap {

    var $server;
    var $searchStr;

    function Ldap($server = "directory.washington.edu", $searchStr = "o=University of Washington, c=US") {
        $this->server = $server;
        $this->searchStr = $searchStr;
    }

    function info($searchType = "(|(sn=ong*)(givenname=ong*))") {
        $server = $this->server;
        $searchStr = $this->searchStr;
        $rets = array();
        $ds = ldap_connect($server);  // must be a valid LDAP server!
        if ($ds) {
            $r = ldap_bind($ds);     // this is an "anonymous" bind, typically read-only access
            $sr = ldap_search($ds, $searchStr, $searchType);
            $info = ldap_get_entries($ds, $sr);
            for ($i = 0; $i < $info["count"]; $i++) {
                $dn = explode(",", $info[$i]["dn"]);
                $cn = array_shift($dn); // shift off cn from dn
                (!empty($dn[0])) ? $rets[$i]["ou"] = substr($dn[0], 3) : $rets[$i]["ou"] = "";
                (!empty($info[$i]["cn"][0])) ? $rets[$i]["cn"] = $info[$i]["cn"][0] : $rets[$i]["cn"] = "";
                (!empty($info[$i]["mail"][0])) ? $rets[$i]["mail"] = $info[$i]["mail"][0] : $rets[$i]["mail"] = "";
                if (preg_match("/mail/i", $searchType)) {
                    (!empty($info[$i]["mail"][0])) ? $rets[$i]["id"] = $info[$i]["mail"][0] : $rets[$i]["id"] = ""; // needed for ajax autocomplete
                    (!empty($info[$i]["mail"][0])) ? $rets[$i]["value"] = $info[$i]["mail"][0] : $rets[$i]["value"] = ""; // needed for ajax autocomplete
                } else {
                    (!empty($info[$i]["cn"][0])) ? $rets[$i]["id"] = $info[$i]["cn"][0] : $rets[$i]["id"] = ""; // needed for ajax autocomplete
                    (!empty($info[$i]["cn"][0])) ? $rets[$i]["value"] = $info[$i]["cn"][0] : $rets[$i]["value"] = ""; // needed for ajax autocomplete
                }
                (!empty($info[$i]["telephonenumber"][0])) ? $rets[$i]["telephonenumber"] = $info[$i]["telephonenumber"][0] : $rets[$i]["telephonenumber"] = "";
                (!empty($info[$i]["mailstop"][0])) ? $rets[$i]["mailstop"] = $info[$i]["mailstop"][0] : $rets[$i]["mailstop"] = "";
            }
            ldap_close($ds);
        }
        return $rets;
    }

    function email($iEmail) {
        $ret = Array();
        $emailArray = explode("@", $iEmail);
        if (count($emailArray) > 1 and !empty($emailArray[1]) and (substr($emailArray[1], 0, 1) == "u" or substr($emailArray[1], 0, 2) == "uw")) {
            $email = $emailArray[0] . "@" . substr($emailArray[1], 0, 1);
            $return = $this->info("mail=$email*");
            if (!empty($return) && is_array($return))
                $ret = $return;
        }
        return $ret;
    }

    function name($name) {
        return $this->info("(|(sn=$name*)(givenname=$name*))");
    }

}

?> 