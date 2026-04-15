<?php

namespace MvcLite;

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


class CString extends CCore
{
    public static function is_multiple_of(string $str, int $iLen = 80): bool
    {
        //$result = wordwrap($str, 80, "\n", true);    
        return mb_strlen($str, "UTF-8") % $iLen === 0;
    }


    /**
     * Replaces &amp; with & in the provided string.
     * 
     * @param string|null $iStr
     * @return string|null
     */
    public static function FixAmp(?string $iStr): ?string
    {
        if (self::IsEmpty($iStr)) return $iStr;
        $ret = str_replace("&amp;", "&", $iStr);
        return $ret;
    }

    /**
     * Custom substring implementation with length logic.
     * 
     * @param string|null $istr
     * @param int $iStart
     * @param int $iLen
     * @return string
     */
    public static function SubStr(?string $istr, int $iStart, int $iLen): string
    {
        $ret = "";
        $size = 0;
        if (!self::IsEmpty($istr)) {
            $len = mb_strlen($istr, "UTF-8");
            if ($len > $iLen) {
                $size = $iLen; // ensure it is not over iLen
            } else {
                $size = $len;
            }
            if ($iStart > 0) {
                $size = $size - $iStart; // adjust size by istart value
            }
            $ret = mb_substr($istr, $iStart, $size, "UTF-8");
        }
        return $ret;
    }

    /**
     * Gets substring to the right of the needle.
     * 
     * @param string|null $istr
     * @param string $iNeedle
     * @return string
     */
    public static function subStrRt(?string $istr, string $iNeedle): string
    {
        $ret = "";
        if ($istr !== null) {
            $spos = mb_strrpos($istr, $iNeedle, 0, "UTF-8");
            if ($spos !== false) // if found
            {
                $spos = $spos + mb_strlen($iNeedle, "UTF-8"); // skip the needle 
                $ret = mb_substr($istr, $spos, null, "UTF-8"); // get substr right of char
                // CMsg::_pdmsg("rt:".$spos);
            }
        }
        return $ret;
    }

    /**
     * Gets substring to the left of the needle.
     * 
     * @param string|null $istr
     * @param string $iNeedle
     * @return string
     */
    public static function subStrLt(?string $istr, string $iNeedle): string
    {
        $ret = "";
        if ($istr !== null) {
            $spos = mb_strrpos($istr, $iNeedle, 0, "UTF-8");
            if ($spos !== false) // if found
            {
                $ret = mb_substr($istr, 0, $spos, "UTF-8"); // get substr left of char
                // CMsg::_pdmsg("lt:".$spos);
            }
        }
        return $ret;
    }

    /**
     * Checks if a string is null or empty.
     * 
     * @param string|null $iVar
     * @return bool
     */
    public static function IsEmpty(?string $iVar): bool
    {
        return $iVar === null || $iVar === "";
    }

    /**
     * Converts string to Title Case.
     * 
     * @param string $Input
     * @return string
     */
    public static function ProperCase(string $Input): string
    {
        // Equivalent to CurrentCulture.TextInfo.ToTitleCase
        return mb_convert_case($Input, MB_CASE_TITLE, "UTF-8");
    }

    /**
     * Converts backslashes to forward slashes and trims trailing.
     * 
     * @param string|null $spath
     * @return string|null
     */
    public static function FixBackSlash(?string $spath): ?string
    {
        if (self::IsEmpty($spath)) return $spath;
        $ret = rtrim(str_replace("\\", "/", $spath), '/');
        return $ret;
    }

    /**
     * Converts forward slashes to backslashes and trims trailing.
     * 
     * @param string|null $spath
     * @return string|null
     */
    public static function FixForwSlash(?string $spath): ?string
    {
        if (self::IsEmpty($spath)) return $spath;
        $ret = rtrim(str_replace("/", "\\", $spath), '\\');
        return $ret;
    }

    /**
     * Gets the next alphanumeric character from the sequence.
     * 
     * @param string $letter
     * @return string
     */
    public static function NextLetter(string $letter): string
    {
        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $idx = mb_strpos($alphabet, $letter, 0, "UTF-8");
        if ($idx === false || $idx >= (mb_strlen($alphabet, "UTF-8") - 1)) {
            return $alphabet[0]; // Wrap around or handle end of sequence
        }
        return mb_substr($alphabet, $idx + 1, 1, "UTF-8");
    }
}
