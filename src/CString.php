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
                // CMsg::_msg("rt:".$spos);
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
                // CMsg::_msg("lt:".$spos);
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

    public static function EscapeStr($inp)
    {
        if (is_array($inp)) {
            return array_map([self::class, 'EscapeStr'], $inp);
        }
        if (!empty($inp) && is_string($inp)) {
            $inp = strip_tags($inp);
            $inp = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $inp) ?? $inp;
            $inp = preg_replace('/[\r\n\t]+/', ' ', $inp) ?? $inp;
            $inp = preg_replace('/[\x{2018}\x{2019}\x{201C}\x{201D}]/u', "'", $inp) ?? $inp;
            $inp = preg_replace('/[\'"<>`]+/', '', $inp) ?? $inp;
            $inp = str_replace(['--', ';--'], ['', ';'], $inp);
        }
        return $inp;
    }

    public static function StripSpecChars(string $value): string
    {
        return self::EscapeStr($value);
    }

    public static function Clean($str, $itype = "txt", $retchar = "")
    {
        switch ($itype) {
            case "quote":
                $ret = !empty($str) ? str_replace("'", "''", str_replace("\\'", "'", $str)) : "";
                break;
            case "email":
                $ret = filter_var($str, FILTER_SANITIZE_EMAIL);
                $ret = htmlspecialchars(strip_tags(trim($ret)), ENT_QUOTES, 'UTF-8');
                break;
            case "num":
                $ret = filter_var($str, FILTER_SANITIZE_NUMBER_INT);
                break;
            case "txt":
                $ret = htmlspecialchars(strip_tags(trim((string) $str)), ENT_QUOTES, 'UTF-8');
                $ret = self::EscapeStr(htmlspecialchars($ret, ENT_QUOTES, 'UTF-8'));
                break;
            case "amt":
                $patterns = ['/[^0-9.]/'];
                $ret = sprintf("%1.2f", preg_replace($patterns, '', (string) $str));
                if (strlen($ret) > 10) {
                    $ret = 0;
                }
                break;
            default:
            case "raw":
                $ret = filter_var($str, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_HIGH);
                break;
        }

        if (empty($ret) && $retchar !== "") {
            $ret = $retchar;
        }
        return $ret;
    }

    public static function CleanStr($iStr, $iType = "txt", $rChar = "")
    {
        $value = is_scalar($iStr) ? (string) $iStr : "";
        $ret = "";

        if ($value !== "") {
            $value = strip_tags($value);
            $type = strtolower((string) $iType);

            switch ($type) {
                case "float":
                    $ret = number_format((float) preg_replace('/[^0-9.\-]/', '', $value), 4, ".", "");
                    break;
                case "amt":
                    $ret = number_format((float) preg_replace('/[^0-9.\-]/', '', $value), 2, ".", ",");
                    break;
                case "int":
                case "int32":
                case "int64":
                    $ret = (string) (int) preg_replace('/[^0-9\-]/', '', $value);
                    break;
                case "enc4db":
                    $ret = htmlspecialchars(
                        self::StripSpecChars($value),
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        "UTF-8"
                    );
                    break;
                case "dec4vw":
                    $ret = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, "UTF-8");
                    $ret = strip_tags($ret);
                    break;
                case "html":
                    $ret = strip_tags($value);
                    break;
                case "url":
                    $ret = rawurldecode($value);
                    break;
                case "asci":
                    $ret = preg_replace('/[^\x00-\x7F]/', "", $value) ?? "";
                    break;
                case "raw":
                    $ret = str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
                    break;
                case "string":
                case "txt":
                default:
                    $ret = self::StripSpecChars($value);
                    break;
            }
        }

        return ($ret === "" && $rChar !== "") ? $rChar : $ret;
    }

    public static function Validate($iType, $iStr)
    {
        switch ($iType) {
            case 'empty':
                return !self::IsEmpty((string) $iStr);
            case 'full_email':
            case 'email':
                $ok = (bool) preg_match(
                    '/^[^0-9][A-Za-z0-9_]+([.][A-Za-z0-9_]+)*[@][A-Za-z0-9_]+([.][A-Za-z0-9_]+)*[.][A-Za-z]{2,4}/',
                    (string) $iStr
                );
                if (
                    self::IsEmpty((string) $iStr)
                    || preg_match('/fullemail|nobody|somebody|no-?exist/i', (string) $iStr)
                ) {
                    $ok = false;
                }
                return $ok;
            case 'password':
                if (!self::IsEmpty((string) $iStr) && strlen((string) $iStr) >= 6) {
                    return true;
                }
                CUtil::Add2SessVar('feedback', '  Sorry -- the password must be at least 6 characters. ');
                return false;
            default:
                return false;
        }
    }

    public static function splitWords($string, $max = 1)
    {
        $words = preg_split('/\s/', $string);
        $lines = [];
        $line = '';
        foreach ($words as $k => $word) {
            $length = strlen($line . ' ' . $word);
            if ($length <= $max) {
                $line .= ' ' . $word;
            } elseif ($length > $max) {
                if (!empty($line)) {
                    $lines[] = trim($line);
                }
                $line = $word;
            } else {
                $lines[] = trim($line) . ' ' . $word;
                $line = '';
            }
        }
        $lines[] = ($line = trim($line)) ? $line : $word;
        return $lines;
    }

    public static function splitLongLine($string, $maxWords = 96)
    {
        $oline = "";
        $strArry = explode("\n\r", $string);
        foreach ($strArry as $line) {
            (strlen($line) > $maxWords) ? $strArray = self::splitWords($line, $maxWords) : $strArray = [];
            (!empty($strArray)) ? $oline .= implode("\n\r", $strArray) : $oline .= $line;
        }
        return $oline;
    }

    public static function br2nl($string)
    {
        return preg_replace('/\<br(\s*)?\/?>/i', "\n", $string);
    }
}
