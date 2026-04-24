<?php

namespace MvcLite;

/**
 * Required dependencies:
 * - CCore class (must be defined in the Co namespace or imported)
 * - CUtil class (must provide static methods: getSessTxt, getAppTxt, IsDebug)
 * - CSecs class (if the commented debug logic is ever uncommented)
 */

class CMsg extends CCore
{
    /**
     * @var string
     */
    public $Title;

    /**
     * @return string
     */
    public static function FirstTask()
    {
        $Title = "Hello";
        return $Title;
    }

    /**
     * @param string $ret
     */
    public static function _appdmsg($ret)
    {
        //            self::_appdmsg2AppState($ret);
        self::_appdmsg2SessState($ret);
    }

    /**
     * @param string $ret
     */
    public static function _appdmsg2SessState($ret)
    {
        $prev = "";
        $sfbdmsg = \CUtil::getSessTxt("fbdmsg");
        if ($sfbdmsg != null && strlen($sfbdmsg) > 0) {
            $prev = $sfbdmsg . " ";
        }
        // must use this to avoid un-initialize error
        $_SESSION["fbdmsg"] = $prev . $ret;
    }

    /**
     * not use, use Session instead
     * @param string $ret
     */
    public static function _appdmsg2AppState($ret)
    {
        $prev = "";
        $fbdmsg = \CUtil::getAppTxt("fbdmsg");

        if ($fbdmsg != null && strlen($fbdmsg) > 0) {
            $prev = $fbdmsg . " ";
        }
        // Assuming self::$_ctx_c provides access to an application state object
        self::$_ctx_c->Application["fbdmsg"] = $prev . $ret;
    }

    /* 
            public static function _prt($msg) {
                echo $msg;
            }
    */

    /**
     * various overload _dprt
     * @param mixed $source
     * @param string $prefix
     */
    public static function _dprt($source, $prefix = "")
    {
        $msg = self::_msg($source, $prefix);
        self::_prt($msg);
        //            echo $msg;
    }

    /**
     * various overload _msg
     * Handles string, int, NameValueCollection, Dictionary, Array, and SessionState
     * @param mixed $source
     * @param string $prefix
     * @return string
     */
    public static function _msg($source, $prefix = "")
    {
        // Handle Array / Collection types
        if (is_array($source) || $source instanceof \Traversable) {
            $ret = "";
            $i = 0;

            // Detect if it should be treated like a Dictionary/NameValueCollection (associative) 
            // or a string[] / string[,] (indexed)
            $isAssoc = false;
            if (is_array($source)) {
                $isAssoc = (array_keys($source) !== range(0, count($source) - 1)) && !empty($source);
            }

            // Special Case: HttpSessionState (In C#, iterating Session returns keys)
            // In PHP, we simulate this if the source is specifically the session global
            if ($source === $_SESSION) {
                foreach (array_keys($source) as $s) {
                    $ret .= $i . "=[" . $s . "],";
                    $i++;
                }
            } 
            // Case: NameValueCollection / Dictionary<string, object> / Dictionary<string, string>
            elseif ($isAssoc) {
                foreach ($source as $key => $val) {
                    $valStr = (string)$val;
                    // Logic from Dictionary overloads: check if length > 0
                    // Logic from NameValueCollection: just append
                    // We apply length check to match Dictionary logic as it is more specific
                    if (strlen($valStr) > 0) {
                        $ret .= $key . "=[" . $valStr . "],";
                    } elseif ($source instanceof \System\Collections\Specialized\NameValueCollection) {
                        // Fallback for literal NameValueCollection behavior if needed
                        $ret .= $key . "=[" . $valStr . "],";
                    }
                }
            } 
            // Case: string[,] / string[]
            else {
                // To simulate C# string[,] foreach, we flatten the array
                $flat = [];
                array_walk_recursive($source, function($item) use (&$flat) {
                    $flat[] = $item;
                });

                foreach ($flat as $s) {
                    // Check for null only if it's simulating the [,] overload
                    // In PHP arrays, we just check if it's set
                    if ($s !== null) {
                        $ret .= $i . "=[" . $s . "],";
                    }
                    $i++;
                }
            }

            $ret = rtrim($ret, ',');
            return self::_msg($ret, $prefix);
        }

        // Handle Integer overload
        if (is_int($source)) {
            return self::_msg((string)$source, $prefix);
        }

        // Handle String overload (Base implementation)
        $str_pref = "";
        $ret = "";
        if ($prefix != null && strlen($prefix) > 0) {
            $str_pref = "[<b>" . $prefix . "</b>]:";
        }

        if (CDebug::IsDebug() == true) {
            $ret = $str_pref . "(" . (string)$source . ")";
        }
        return $ret;
    }

    /**
     * various overload _dmsg
     * @param mixed $source
     * @param string $prefix
     * @return string
     */
    public static function _dmsg($source, $prefix = "")
    {
        $ret = self::_msg($source, $prefix);
        self::_appdmsg($ret);
        return $ret;
    }

    /**
     * various overload _pdmsg
     * @param mixed $source
     * @param string $prefix
     * @return string
     */
    public static function _pdmsg($source, $prefix = "")
    {
        $ret = "";
        $ret = self::_dmsg($source, $prefix);
        return $ret;
    }
}
?>
