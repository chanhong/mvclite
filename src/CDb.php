<?php

namespace MvcLite;

defined('_MVCLite') or die('Direct Access to this location is not allowed.');

class CDb extends CCore
{
    protected string $_ConnectionString = "";
    public static string $_defaultConnectionString = "";
    public static string $_connectionName = "";

    public static function getOleDsn(string $dbInfo = ""): string
    {
        return self::getDsn("ole", $dbInfo);
    }

    public static function getSqlDsn(string $dbInfo = ""): string
    {
        return self::getDsn("sql", $dbInfo);
    }

    public static function getDsn(string $sType, string $dbInfo = ""): string
    {
                $connectionString = "";
        $credential = "";

//        echo "getDsn";
            $dbInfoNv = CConfig::get('db')??[]; // defined in local.php
                    if (!empty($dbInfoNv["dsn"])) { // defined in local.php
            $connectionString=$dbInfoNv["dsn"];
                    };
//pln($dbInfoNv);
        $dbEnv = self::getAppTxt("dbenv");
        if (!CString::IsEmpty($dbInfo)) {
            $dbInfoNv = CCore::$_cfg[$dbInfo] ?? [];
        } elseif (!CString::IsEmpty($dbEnv) && isset(CCore::$_cfg[$dbEnv])) {
            $dbInfoNv = CCore::$_cfg[$dbEnv] ?? [];
        } else {
            $dbInfoNv = CConfig::get('db')??[]; // defined in local.php
        }

        if (!empty($dbInfoNv["dsn"])) { // defined in local.php
            $connectionString = $dbInfoNv["dsn"];
            if (isset($dbInfoNv["connection"]) && strtolower($dbInfoNv["connection"]) === "default") {
                self::$_defaultConnectionString = $connectionString;
            }
//            pln($connectionString,'dsn');
            return $connectionString;
        }


        if (!empty($dbInfoNv["host"]) && !empty($dbInfoNv["db"])) {
            // Unify to PDO sqlsrv DSN format: sqlsrv:server=host;Database=db
            $connectionString = "sqlsrv:server=" . $dbInfoNv["host"] . ";Database=" . $dbInfoNv["db"];

            if (isset($dbInfoNv["connection"]) && strtolower($dbInfoNv["connection"]) === "default") {
                self::$_defaultConnectionString = $connectionString;
            }
        }
        return $connectionString;
    }

    public static function nvSchemaByType(array $schema, string $opt = "name"): array
    {
        $oSchema = [];
        $opt = strtolower($opt);
        if ($opt === "name") {
            $oSchema = CUtil::s2nv(',', $schema["ColumnName"] ?? "");
        } elseif ($opt === "size") {
            $nvName = CUtil::str2a(',', $schema["ColumnName"] ?? "");
            $nvValue = CUtil::str2a(',', $schema["ColumnSize"] ?? "");
            for ($j = 0; $j < count($nvName); $j++) {
                if (isset($nvName[$j])) {
                    $oSchema[$nvName[$j]] = $nvValue[$j] ?? "";
                }
            }
        } elseif ($opt === "type") {
            $nvName = CUtil::str2a(',', $schema["ColumnName"] ?? "");
            $nvValue = CUtil::str2a(',', $schema["DataType"] ?? "");
            for ($j = 0; $j < count($nvName); $j++) {
                if (isset($nvName[$j])) {
                    $val = $nvValue[$j] ?? "";
                    $parts = explode('.', $val);
                    $lastPart = end($parts);
                    $oSchema[$nvName[$j]] = $lastPart;
                }
            }
        }
        return $oSchema;
    }

    public static function not_use_nv2sUFL(array $nvValue, string $delim = ","): string
    {
        $sb = "";
        if (!empty($nvValue)) {
            foreach ($nvValue as $s => $val) {
                $sb .= $s . "='" . str_replace("'", "''", $val) . "'" . $delim;
            }
        }
        return CUtil::sb2s($sb, $delim);
    }

    public static function nv2sInsert(string $tName, ?array $nvValue, ?array $nvParm = null): string
    {
        $delim = ",";
        $strQry = "";
        $table = "";
        $fl = "";
        $values = "";
        $param = "";

        if (!empty($tName)) {
            $table = strtolower($tName);
        }
        if ($nvParm !== null) {
            if ($nvValue !== null && !empty($nvParm["param"])) {
                $param = "?";
            }
        }
        if (!empty($nvValue)) {
            $sb = "";
            $vb = "";
            foreach ($nvValue as $s => $val) {
                $sb .= $s . $delim;
                if ($param !== "") {
                    $vb .= $param . $delim;
                } else {
                    $vb .= "'" . \PdoLite\PdoLite::escapeQuote($val) . "'" . $delim;
                }
            }
            $fl = CUtil::sb2s($sb, $delim);
            $values = CUtil::sb2s($vb, $delim);
        }
        if ($table !== "" && $fl !== "" && $values !== "") {
            $strQry = sprintf("insert INTO %s (%s) VALUES (%s)", $table, $fl, $values);
        }
        return $strQry;
    }

    public static function nv2sDelete(string $tName, ?array $nvParm): string
    {
        $strQry = "";
        $table = "";
        $where = "";

        if (!empty($tName)) {
            $table = strtolower($tName);
        }
        if ($nvParm !== null) {
            if (!empty($nvParm["where"])) {
                $where = " where " . $nvParm["where"];
            }
        }
        if ($table !== "" && $where !== "") {
            $strQry = sprintf("delete from %s %s", $table, $where);
        }
        return $strQry;
    }

    public static function nv2sUpdate(string $tName, ?array $nvValue, ?array $nvParm = null): string
    {
        $param = "";
        $delim = ",";
        $strQry = "";
        $table = "";
        $fl = "";
        $where = "";

        if (!empty($tName)) {
            $table = strtolower($tName);
        }
        if ($nvParm !== null) {
            if (!empty($nvParm["where"])) {
                $where = " where " . $nvParm["where"];
            }
            if (!empty($nvParm["param"])) {
                $param = "?";
            }
        }
        if (!empty($nvValue)) {
            $sb = "";
            foreach ($nvValue as $s => $val) {
                if ($param !== "") {
                    $sb .= $s . "=" . $param . $delim;
                } else {
                    $sb .= $s . "='" . \PdoLite\PdoLite::escapeQuote($val) . "'" . $delim;
                }
            }
            $fl = CUtil::sb2s($sb, $delim);
        }

        if ($table !== "" && $fl !== "" && $where !== "") {
            $strQry = sprintf("update %s SET %s %s", $table, $fl, $where);
        }
        return $strQry;
    }

    public static function nv2sSelect(string $tName, ?array $nvValue, ?array $nvParm = null): string
    {
        $strQry = "";
        $table = "";
        $top = "";
        $fl = "";
        $where = "";
        if (!empty($tName)) {
            $table = strtolower($tName);
        }
        if ($nvParm !== null) {
            if (!empty($nvParm["top"])) {
                $top = " top " . $nvParm["top"];
            }
            if (!empty($nvParm["fl"])) {
                $fl = $nvParm["fl"];
            }
            if (!empty($nvParm["where"])) {
                $where = " where " . $nvParm["where"];
            } else {
                $where = " where 1=1 ";
            }
        }
        if ($fl === "" && !empty($nvValue)) {
            $fl = implode(",", array_keys($nvValue));
        }
        if ($table !== "" && $fl !== "") {
            $strQry = sprintf("select%s %s from %s%s", $top, $fl, $table, $where);
        }
        return $strQry;
    }

    public static function nv2sSelect_old(string $tName, ?array $nvValue, ?array $nvParm = null): string
    {
        $strQry = "";
        $table = "";
        $top = "";
        $fl = "";
        $where = "";
        if (!empty($tName)) {
            $table = strtolower($tName);
        }
        if ($nvParm !== null) {
            if (!empty($nvParm["top"])) {
                $top = " top " . $nvParm["top"];
            }
            if (!empty($nvParm["fl"])) {
                $fl = $nvParm["fl"];
            }
            if (!empty($nvParm["where"])) {
                $where = " where 1=1" . $nvParm["where"];
            } else {
                $where = " where 1=1 ";
            }
        }
        if ($fl === "" && !empty($nvValue)) {
            $fl = implode(",", array_keys($nvValue));
        }
        if ($table !== "" && $fl !== "") {
            $strQry = sprintf("select%s %s from %s%s", $top, $fl, $table, $where);
        }
        return $strQry;
    }

    public static function dt2List(array $dt): array
    {
        $rows = [];
        foreach ($dt as $dr) {
            $row = [];
            foreach ($dr as $colName => $value) {
                $row[strtolower($colName)] = $value !== null ? (string)$value : '';
            }
            $rows[] = $row;
        }
        return $rows;
    }

    public static function dt2Rows(array $dt): array
    {
        $rows = [];
        foreach ($dt as $dr) {
            $row = [];
            foreach ($dr as $colName => $value) {
                $row[strtolower($colName)] = $value !== null ? (string)$value : '';
            }
            $rows[] = $row;
        }
        return $rows;
    }

    public static function not_use_da2Rows($da): array
    {
        if (is_array($da)) {
            return self::dt2Rows($da);
        }
        return [];
    }
}
