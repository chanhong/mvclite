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

use DateTime;
use DateTimeZone;
use Exception;

class CDate
{
    public static function StartOfMonth(DateTime $date): DateTime
    {
        return (clone $date)->modify('first day of this month')->setTime(0, 0, 0);
    }

    public static function EndOfMonth(DateTime $date): DateTime
    {
        $startOfMonth = self::StartOfMonth($date);
        $startOfNextMonth = (clone $startOfMonth)->modify('+1 month');
        return $startOfNextMonth->modify('-1 second');
    }

    public static function GetDate(string $sDte = ""): DateTime
    {
        if (empty($sDte)) {
            return new DateTime();
        }
        try {
            return new DateTime($sDte, new DateTimeZone('UTC'));
        } catch (Exception $e) {
            return new DateTime();
        }
    }

    public static function GetDateExact(string $fmt = "YmdHis", string $sDte = ""): DateTime
    {
        if ($sDte === "") {
            $sDte = (new DateTime())->format($fmt);
        }
        $date = DateTime::createFromFormat($fmt, $sDte, new DateTimeZone('UTC'));
        if (!$date) {
            throw new Exception("Invalid date format or string.");
        }
        return $date;
    }

    public static function Dte2s(string $fmt = "", string $sDte = ""): string
    {
        $ret = "";
        $tDte = self::GetDate($sDte);
        switch ($fmt) {
            case "utc":
                $ret = $tDte->format("Y-m-d\TH:i:s\Z");
                break;
            case "dow":
                $ret = $tDte->format("l"); // Full textual representation of the day of the week
                break;
            case "doy":
                $ret = $tDte->format("z") + 1; // Day of the year (starting from 0, so add 1)
                break;
            case "tod":
                $ret = $tDte->format("H:i:s.u"); // Time of day with microseconds
                break;
            case "ticks":
                // .NET ticks are 100-nanosecond intervals since 0001-01-01
                // PHP DateTime does not support ticks directly, so approximate:
                $epoch = new DateTime('@0'); // Unix epoch 1970-01-01
                $interval = $epoch->diff($tDte);
                $days = $interval->days;
                $ticks = ($days + 719162) * 864000000000 + // days since 0001-01-01 * ticks per day
                    ($tDte->format('H') * 3600 + $tDte->format('i') * 60 + $tDte->format('s')) * 10000000 +
                    intval($tDte->format('u')) * 10;
                $ret = (string)$ticks;
                break;
            case "Kind":
                // PHP DateTime does not have Kind property, approximate by timezone type
                $tz = $tDte->getTimezone();
                $ret = $tz->getName() === "UTC" ? "Utc" : "Local";
                break;
            case "date":
                $ret = $tDte->format("m/d/Y"); // mm/dd/yyyy
                break;
            case "time":
                $ret = $tDte->format("H:i:s"); // hh:mm:ss
                break;
            case "y":
                $ret = $tDte->format("Y");
                break;
            case "m":
                $ret = $tDte->format("n"); // Numeric month without leading zeros
                break;
            case "d":
                $ret = $tDte->format("j"); // Day of the month without leading zeros
                break;
            case "h":
                $ret = $tDte->format("G"); // 24-hour format without leading zeros
                break;
            case "mi":
                $ret = $tDte->format("i"); // Minutes with leading zeros
                break;
            case "s":
                $ret = $tDte->format("s"); // Seconds with leading zeros
                break;
            case "ms":
                $ret = intval($tDte->format("u") / 1000); // Milliseconds
                break;
            default:
                $ret = $tDte->format("Y-m-d H:i:s");
                break;
        }
        return $ret;
    }

    public static function DateAfterRetention(int $retentionYears = 6): DateTime
    {
        $startFY = self::GetDate(self::FinYearBegDate(7)); // Fiscal month start on July
        $retentionYearsStartDate = (clone $startFY)->modify("-{$retentionYears} years");
        $dteAfterRetention = (clone $retentionYearsStartDate)->modify("-1 day");
        return $dteAfterRetention;
    }

    public static function FinYearBegDate(int $FYStart = 7): string
    {
        $now = self::GetDate();
        $currentYear = (int)$now->format("Y");
        $currentMonth = (int)$now->format("n");

        $year = ($currentMonth > $FYStart - 1) ? $currentYear : $currentYear - 1;
        $FiscalYrDate = "{$FYStart}/1/{$year}";
        return $FiscalYrDate;
    }

}