<?php

namespace MvcLite;

class CUrl
{
    public static function relRoot($adj = "")
    {
        $levels = substr_count($_SERVER['PHP_SELF'], '/');
        $root = '';
        for ($i = 1; $i < $levels - $adj; $i++) {
            $root .= '../';
        }
        return $root;
    }

    public static function rootPath($path = "")
    {
        $path_parts = pathinfo($_SERVER['PHP_SELF']);
        $return = realpath(dirname(realpath($path_parts['basename'])) . "/" . $path);
        return $return;
    }

    public static function getURI()
    {
        if (empty($_SERVER['REQUEST_URI'])) {
            $qs = (!empty($_SERVER['QUERY_STRING'])) ? '?' . $_SERVER['QUERY_STRING'] : '';
            $path_parts = pathinfo($_SERVER['PHP_SELF']);
            $uri = $path_parts['basename'] . $qs;
            if (substr($uri, 0, 1) <> "/") {
                $uri = "/" . $uri;
            }
        } else {
            $uri = $_SERVER['REQUEST_URI'];
        }
        return $uri;
    }

    public static function rootSite()
    {
        $dirname = preg_replace('/\\\+/', '/', dirname($_SERVER['PHP_SELF']));
        if (substr($dirname, -1) == "/") {
            $dirname = substr($dirname, 0, strlen($dirname) - 1);
        }
        return $dirname;
    }

    public static function siteURL()
    {
        $protocol = "http://";
        if (!empty($_SERVER['HTTPS'])) {
            strtolower($_SERVER['HTTPS']) == "on" ? $protocol = "https://" : $protocol = "http://";
        }
        (!empty($_SERVER['SERVER_PORT']) and $_SERVER['SERVER_PORT'] <> "443" and $_SERVER['SERVER_PORT'] <> "80") ? $port = ":" . $_SERVER['SERVER_PORT'] : $port = "";
        return $protocol . $_SERVER['SERVER_NAME'] . $port;
    }

    public static function selfURL()
    {
        return self::siteURL() . self::rootSite();
    }

    public static function qsValue()
    {
        $arr = array();
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        if ($qs <> null && strlen($qs) > 0) {
            $retUrl = "";
            $retArr = explode("&r=", $qs, 2);
            if (count($retArr) > 1) {
                $retUrl = array_pop($retArr);
                $qs = array_pop($retArr);
            }
            $taskArr = explode("t=", $qs);
            if (count($taskArr) > 1) {
                parse_str($qs, $arr);
                $arr = array_map('strtolower', $arr);
                $arr = array_map('trim', $arr);
            } else {
                $arr = array('t' => $qs);
                if (!empty($retUrl))
                    $arr['r'] = $retUrl;
            }
        }
        return $arr;
    }

    public static function qs2nv($url = "")
    {
        $qscoll = null;
        $currurl = $url;

        if ($currurl == "" || $currurl == "?") {
            $currurl = self::getURI();
        }
        $querystring = null;
        $iqs = strpos($currurl, "?");

        if ($iqs > 0) {
            $querystring = ($iqs < strlen($currurl) - 1) ? substr($currurl, $iqs + 1) : "";
            parse_str($querystring, $qscoll);
        }

        return $qscoll;
    }

    public static function Tap2Qs(string $iVar, string $cmd = "")
    {
        $cqs = ($cmd != "") ? "&c=" . $cmd : "";
        $ret = "?";
        switch (substr($iVar, 0, 1)) {
            case "/":
                $qs = self::path2qs($iVar);
                if ($qs != null && strlen($qs) > 0) {
                    $ret = "?" . $qs;
                }
                break;
            case "?":
                $ret = $iVar;
                break;
        }
        return $ret . $cqs;
    }

    public static function tap_selfurl(string $iVar, string $cmd = "")
    {
        $urlroot = CSetting::get("urlsite") . CSetting::get("siteroot");
        return $urlroot . "/" . self::Tap2Qs($iVar, $cmd);
    }

    public static function tap(string $iVar, string $cmd = "")
    {
        return self::Tap2Qs($iVar, $cmd);
    }

    public static function path2qs(string $iPath, string $cmd = "")
    {
        $cqs = ($cmd != "") ? "&c=" . $cmd : "";
        $p2a = self::path2a($iPath);
        $ret = self::nv2kps($p2a, "&");
        return $ret . $cqs;
    }

    public static function path2a(string $iPath)
    {
        $kArray = [];
        $vArray = (isset($iPath) && substr($iPath, 0, 1) == '/') ? explode("/", substr($iPath, 1, strlen($iPath))) : null;
        $vArray = array_pad($vArray, count($kArray), '');
        $aKey = isset(CCore::$_cfg["takey"]) ? CCore::$_cfg["takey"] : 't,a,p1,p2,p3,p4,p5';
        $kArray = explode(",", $aKey);

        $qsa = [];
        $i = 0;
        foreach ($vArray as $ka) {
            if (isset($ka[0]) && isset($kArray[0])) {
                $qsa[$kArray[$i]] = $ka;
                $i++;
            }
        }
        return $qsa;
    }

    public static function nv2kps($source, $delim = "&")
    {
        $sb = "";
        if ($source != null && count($source) > 0) {
            foreach (array_keys($source) as $s) {
                $sb .= ($s . "=" . (string) $source[$s] . $delim);
            }
        }
        return self::sb2s($sb, $delim);
    }

    public static function sb2s($sb, string $separator)
    {
        $ret = "";
        if ($sb != null && strlen($sb) > 0) {
            $ret = $sb;
            $ret = substr($ret, 0, strlen($ret) - strrpos($separator, $ret) - 1);
        }
        return $ret;
    }

    public static function MyUrl(): string
    {
        return self::getURI();
    }
}
