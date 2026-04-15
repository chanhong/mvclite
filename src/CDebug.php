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



class CDebug
{

    public static function IsDebug()
    {
        $ret = false;
        if (_MVCDebug == true)
        //       || CUtil::isEqInList(CSecs::winUser(), self::getAppTxt("weblogin", "L"), '|')) // selective, only allow winuser listed in weblogin to see dmsg
        {
            $ret = true;
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
    public static function debug($iVar, $iStr = "", $iFormat = null)
    {
        if (Self::IsDebug() == true) {
            return Self::_debug($iVar, $iStr, $iFormat);
        }
    }

    public static function _debug($iVar, $iStr = "", $iFormat = null)
    {
        //      print(print_r(CCore::$_cfg['dmsg'],true));    
        $sVar = "";
        (!empty($iStr) and strtolower($iStr) == "dtrace") ? $dTrace = "dtrace" : $dTrace = "";
        if (!empty($dTrace))
            $dTrace = self::dTrace();

        (!empty($iStr) and strtolower($iStr) <> "dtrace") ? $preText = "[-" . strtoupper($iStr) . "-] " : $preText = "";
        if (!empty($iVar)) {
            if (is_array($iVar) or (is_object($iVar)))
                $sVar = print_r($iVar, true);
            (!empty($_SESSION['dmsg']))
                ? $sVar .=  Self::isTruncateDebug($_SESSION['dmsg'])
                : $sVar = "";
            $str = $preText . $sVar;
            if (strlen($iFormat) > 0)
                $str = "<pre>" . $str . "</pre>";
            if (CString::IsEmpty($str) == false) {
                $sVar = $str . $dTrace . " ";
            }
        }
        (!empty($_SESSION['dmsg'])) ? $_SESSION['dmsg'] .= $sVar : $_SESSION['dmsg'] = $sVar;
    }

    public static function isTruncateDebug($iVar)
    {

        $trunSize = CCore::$_cfg['dmsg']['maxlines'] * CCore::$_cfg['dmsg']['maxscreen'];
        if (!empty($iVar) && strlen($iVar) > 0) {
            $bs = strlen($iVar);
            if ($bs >= $trunSize) {
                $iVar = self::dLogReset($iVar);
            }
        }
        return $iVar;
    }

    public static function dLogReset($iVar)
    {
        //print ($iVar);
        if (empty($iVar)) {
            CCore::$_cfg['debug']['resets'] = 0;
            CCore::$_cfg['debug']['logs'] = [];
        } else {
            // Write to file log
            $logDir = dirname(dirname(dirname(__DIR__))) . '/db/logs';
            @mkdir($logDir, 0775, true); // Create logs directory if it doesn't exist
            $logFile = $logDir . '/debug_' . date('Y-m-d') . '.log';
            $fileHandle = @fopen($logFile, 'a');
            $currentSize = strlen($iVar);
            //            $lineCount = substr_count($iVar, "<br>",0,CCore::$_cfg['dmsg']['maxscreen']); // "\n"
            $lineCount = ceil(strlen($iVar) / CCore::$_cfg['dmsg']['maxscreen']);
            print " size: " . $currentSize . " lc: " . $lineCount;

            // Reset when approaching screen-full
            // Log the debug session to array before resetting
            if (empty(CCore::$_cfg['debug']['logs'])) {
                CCore::$_cfg['debug']['logs'] = [];
            }
            // how did this get into debug dmsg?
            (!empty(CCore::$_cfg['debug']['resets']))
                ? CCore::$_cfg['debug']['resets'] = (int)CCore::$_cfg['debug']['resets'] + 1
                : CCore::$_cfg['debug']['resets'] = 1;

            CCore::$_cfg['debug']['logs'][] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'line_count' => $lineCount,
                'size_kb' => round($currentSize / 1024, 2),
                'reset_num' => (int)CCore::$_cfg['debug']['resets'] + 1
            ];
            // Keep only last 10 debug logs to prevent array bloat
            if (count(CCore::$_cfg['debug']['logs']) > 10) {
                array_shift(CCore::$_cfg['debug']['logs']);
            }
            // Reset on-screen debug
            $rbuff = "[RESET - " . (int)CCore::$_cfg['debug']['resets'] + 1
                . " | " . $lineCount . " lines logged]";
            if ($fileHandle) {
                fwrite($fileHandle, date('Y-m-d H:i:s') . ": " . $rbuff . "\n[" . $iVar . "]\n");
                fclose($fileHandle);
            }
            $iVar = ""; // Clear current debug for new session
        }
        return $iVar;
    }


    public static function dReset_log($ret)
    {
        // Write to file log
        $logDir = dirname(dirname(dirname(__DIR__))) . '/db/logs';
        @mkdir($logDir, 0775, true); // Create logs directory if it doesn't exist
        $logFile = $logDir . '/debug_' . date('Y-m-d') . '.log';
        $fileHandle = @fopen($logFile, 'a');
        if ($fileHandle) {
            fwrite($fileHandle, date('Y-m-d H:i:s') . " - " . $ret . "\n");
            fclose($fileHandle);
        }

        if (empty($_SESSION['dmsg'])) {
            //            $_SESSION['dmsg'] = $ret;
            CCore::$_cfg['debug']['resets'] = 0;
            CCore::$_cfg['debug']['logs'] = [];
        } else {
            $currentSize = strlen($_SESSION['dmsg']);
            $lineCount = substr_count($_SESSION['dmsg'], "<br>"); // "\n"
            //            print " size: " . $currentSize . " lc: " . $lineCount;

            // Reset when approaching screen-full
            if (
                $currentSize >= CCore::$_cfg['dmsg']['maxscreen']
                || $lineCount >= CCore::$_cfg['dmsg']['maxlines']
            ) {

                if ($currentSize >= CCore::$_cfg['dmsg']['maxscreen']) {
                    $trun = substr($_SESSION['dmsg'], -strlen($_SESSION['dmsg']) - substr_count($_SESSION['dmsg'], "<br>"));
                    $_SESSION['dmsg'] = $trun;

                    // Log the debug session to array before resetting

                    if (empty(CCore::$_cfg['debug']['logs'])) {
                        CCore::$_cfg['debug']['logs'] = [];
                    }
                    // how did this get into debug dmsg?
                    (!empty(CCore::$_cfg['debug']['resets']))
                        ? CCore::$_cfg['debug']['resets'] = (int)CCore::$_cfg['debug']['resets'] + 1
                        : CCore::$_cfg['debug']['resets'] = 1;

                    CCore::$_cfg['debug']['logs'][] = [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'line_count' => $lineCount,
                        'size_kb' => round($currentSize / 1024, 2),
                        'reset_num' => (int)CCore::$_cfg['debug']['resets'] + 1
                    ];


                    // Keep only last 10 debug logs to prevent array bloat
                    if (count(CCore::$_cfg['debug']['logs']) > 10) {
                        array_shift(CCore::$_cfg['debug']['logs']);
                    }

                    // Reset on-screen debug
                    $_SESSION['dmsg'] = ""; // Clear current debug for new session

                    $_SESSION['dmsg'] = "[RESET - " . (int)CCore::$_cfg['debug']['resets'] + 1
                        . " | " . $lineCount . " lines logged]\n"
                        //                   . $ret
                    ;

                    //                $ret = "";
                    $currentSize = strlen($_SESSION['dmsg']);
                    $lineCount = substr_count($_SESSION['dmsg'], "<br>"); // "\n"
                    //                print " size: " . $currentSize . " lc: " . $lineCount;
                }

                //            else {
                //                $_SESSION['dmsg'] .= $ret;
                //           $sz=strlen($_SESSION['dmsg']);                
                //           print (" sz: ".$sz);                
                //           }

            }
            return $ret;
        }
    }

    public static function dShow()
    {

        // Display debug messages if they exist
        if (!empty($_SESSION['dmsg']) || !empty(CCore::$_cfg['debug']['logs'])) {
            echo '<hr style="margin-top: 30px; border-top: 2px solid #ccc;">';
            echo '<div style="background-color: #f5f5f5; padding: 15px; margin-top: 20px; font-size: 11px; font-family: monospace; border: 1px solid #ddd;">';

            // Current debug Messages
            echo '<div style="margin-bottom: 20px;">';
            echo '<strong style="font-size: 13px; color: #333;">?? CURRENT debug LOG (On-Screen):</strong><br>';
            if (!empty($_SESSION['dmsg'])) {
                echo '<div style="max-height: 250px; overflow-y: auto; background-color: white; padding: 10px; border: 1px solid #ccc; margin-top: 5px;">';
                echo nl2br(htmlspecialchars($_SESSION['dmsg'], ENT_QUOTES, 'UTF-8'));
                echo '</div>';
                echo '<br><small style="color: #666;">Lines: ' . count(explode("\n", $_SESSION['dmsg'])) . ' | Size: ' . round(strlen($_SESSION['dmsg']) / 1024, 2) . ' KB</small>';
            } else {
                echo '<em style="color: #999;">No debug messages yet</em>';
            }
            echo '</div>';

            // Session Memory Info
            echo '<div style="margin-bottom: 20px;">';
            echo '<strong style="font-size: 13px; color: #333;">?? SESSION MEMORY:</strong><br>';
            echo 'Debug Resets: <strong>' . (isset(CCore::$_cfg['debug']['resets']) ? CCore::$_cfg['debug']['resets'] : 0) . '</strong> | ';
            echo 'Session Size: <strong>' . round(strlen(serialize($_SESSION)) / 1024, 2) . ' KB</strong><br>';
            echo '</div>';

            // Debug Sessions History
            if (!empty(CCore::$_cfg['debug']['logs'])) {
                echo '<div style="margin-bottom: 20px;">';
                echo '<strong style="font-size: 13px; color: #333;">?? Debug SESSIONS HISTORY:</strong><br>';
                echo '<table style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 11px;">';
                echo '<tr style="background-color: #e8e8e8; border-bottom: 1px solid #ccc;">';
                echo '<th style="padding: 5px; text-align: left; border-right: 1px solid #ccc;">Session #</th>';
                echo '<th style="padding: 5px; text-align: left; border-right: 1px solid #ccc;">Lines</th>';
                echo '<th style="padding: 5px; text-align: left; border-right: 1px solid #ccc;">Size</th>';
                echo '<th style="padding: 5px; text-align: left;">Timestamp</th>';
                echo '</tr>';
                foreach (array_reverse(CCore::$_cfg['debug']['logs']) as $index => $log) {
                    $bgColor = ($index % 2 === 0) ? 'white' : '#f9f9f9';
                    echo "<tr style=\"background-color: {$bgColor}; border-bottom: 1px solid #ddd;\">";
                    echo "<td style=\"padding: 5px; border-right: 1px solid #ccc;\">#{$log['reset_num']}</td>";
                    echo "<td style=\"padding: 5px; border-right: 1px solid #ccc;\">{$log['line_count']}</td>";
                    echo "<td style=\"padding: 5px; border-right: 1px solid #ccc;\">{$log['size_kb']} KB</td>";
                    echo "<td style=\"padding: 5px;\">{$log['timestamp']}</td>";
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            }

            // Log File Info
            echo '<div style="margin-bottom: 10px;">';
            echo '<strong style="font-size: 13px; color: #333;">?? debug LOG FILE:</strong><br>';
            $logDir = dirname(dirname(dirname(__DIR__))) . '/db/logs';
            $logFile = $logDir . '/debug_' . date('Y-m-d') . '.log';
            if (file_exists($logFile)) {
                $size = filesize($logFile);
                $sizeKb = round($size / 1024, 2);
                $lines = count(file($logFile, FILE_SKIP_EMPTY_LINES));
                echo 'Location: <code style="background: white; padding: 2px 5px; border: 1px solid #ccc;">' . htmlspecialchars($logFile, ENT_QUOTES, 'UTF-8') . '</code><br>';
                echo 'Size: <strong>' . $sizeKb . ' KB</strong> | Lines: <strong>' . $lines . '</strong> | ';
                echo 'Last Modified: <strong>' . date('Y-m-d H:i:s', filemtime($logFile)) . '</strong><br>';
            } else {
                echo 'Log file will be created at: <code style="background: white; padding: 2px 5px; border: 1px solid #ccc;">' . htmlspecialchars($logFile, ENT_QUOTES, 'UTF-8') . '</code>';
            }
            echo '</div>';

            echo '</div>';
        }
    }
}
