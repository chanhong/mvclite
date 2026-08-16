<?php

namespace MvcLite;

defined('_MVCLite') or die('Direct Access to this location is not allowed.');

class CDbPdo extends CDb
{
    public static function oleGetConnection(string $connectionString): \PDO
    {
        $username = null;
        $password = null;

        // Try to find the matching configuration to get the username/password
        if (isset(CCore::$_cfg) && is_array(CCore::$_cfg)) {
            foreach (CCore::$_cfg as $key => $config) {
                if (is_array($config) && isset($config['dsn']) && $config['dsn'] === $connectionString) {
                    $username = $config['username'] ?? null;
                    $password = $config['password'] ?? null;
                    break;
                }
            }
        }

        // If not found by DSN, let's check the default database config
        if ($username === null && $password === null) {
            $defaultDbKey = self::getAppTxt("dbenv");
            $defaultConfig = CCore::$_cfg[$defaultDbKey] ?? [];
            if (!empty($defaultConfig['username'])) {
                $username = $defaultConfig['username'];
                $password = $defaultConfig['password'] ?? null;
            }
        }

        try {
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ];
            $connection = \PdoLite\PdoLite::dbConnect($connectionString, $username ?? "", $password ?? "", $options);
            return $connection;
        } catch (\PDOException $e) {
            CUtil::add2SessVar("alert", "Connection FAILED: " . $e->getMessage());
            throw $e;
        }
    }

    public static function connectByCfgName(string $strConnName = "DefaultConnection"): \PDO
    {
        $config = CCore::$_cfg[$strConnName] ?? [];
        $connectionString = is_array($config) ? ($config['dsn'] ?? "") : $config;
        return self::oleGetConnection($connectionString);
    }

    public static function prtMyData(string $dsn, string $queryString): void
    {
        $stmt = self::oleReader($dsn, $queryString);
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            pln($row[0] . ", " . $row[1]);
        }
    }

    public static function oleCmd(\PDO $conn, string $commandText): \PDOStatement
    {
        return $conn->prepare($commandText);
    }

    public static function oleReader(string $dsn, string $strQry): \PDOStatement
    {
        $conn = self::oleGetConnection($dsn);
        $stmt = self::oleCmd($conn, $strQry);
        $stmt->execute();
        return $stmt;
    }

    public static function oleSchemaExNv(string $dsn, string $strQry): array
    {
        $reader = self::oleReader($dsn, $strQry);
        return self::oleRdr2NvSchemaEx($reader);
    }

    public static function oleRdr2NvSchemaEx(\PDOStatement $myReader): array
    {
        $names = [];
        $sizes = [];
        $types = [];

        $colCount = $myReader->columnCount();
        for ($i = 0; $i < $colCount; $i++) {
            $meta = $myReader->getColumnMeta($i);
            if ($meta) {
                $names[] = $meta['name'] ?? '';
                $sizes[] = $meta['len'] ?? '';
                $type = $meta['sqlsrv:decl_type'] ?? $meta['native_type'] ?? '';
                $types[] = $type;
            }
        }

        return [
            'ColumnName' => implode(',', $names),
            'ColumnSize' => implode(',', $sizes),
            'DataType' => implode(',', $types)
        ];
    }

    public static function oleDt2Json(\PDO $conn, string $strQry): string
    {
        $rows = self::oleDt2Rows($conn, $strQry);
        return json_encode($rows);
    }

    public static function oleRdr2Json_old(\PDO $conn, string $strQry, ?array $nvValue = null): string
    {
        $rows = self::oleRdr2Rows($conn, $strQry, $nvValue);
        return json_encode($rows);
    }

    public static function oleRdr2Json(string $strQry, array $nvValue, string $dbinfo = ""): string
    {
        $conn = self::oleDbEnvConn($dbinfo);
        $rows = self::oleRdr2Rows($conn, $strQry, $nvValue);
        return json_encode($rows);
    }

    public static function oleRdr2List(\PDO $conn, string $strQry, ?array $nvValue = null): array
    {
        $stmt = self::oleRdrParam($conn, $strQry, $nvValue);
        $dt = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return self::dt2List($dt);
    }

    public static function oleRdr2Rows(\PDO $conn, string $strQry, ?array $nvValue = null): array
    {
        $stmt = self::oleRdrParam($conn, $strQry, $nvValue);
        $dt = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return self::dt2Rows($dt);
    }

    public static function oleDt2Rows(\PDO $conn, string $strQry): array
    {
        $stmt = self::oleCmd($conn, $strQry);
        $stmt->execute();
        $dt = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return self::dt2Rows($dt);
    }

    public static function oleCmdParam(\PDO $conn, string $strQry, ?array $nvValue = null): \PDOStatement
    {
//        pln($strQry,'oleCmdParam');
        $stmt = $conn->prepare($strQry);
        if ($nvValue !== null && count($nvValue) > 0) {
            $i = 1;
            foreach ($nvValue as $key => $val) {
                if (strpos($strQry, '?') !== false) {
                    $stmt->bindValue($i, $val);
                } else {
                    $stmt->bindValue('@' . $key, $val);
                }
                $i++;
            }
        }
        return $stmt;
    }

    public static function oleRdrParam(\PDO $conn, string $strQry, ?array $nvValue = null): \PDOStatement
    {
        $stmt = self::oleCmdParam($conn, $strQry, $nvValue);
        $stmt->execute();
        return $stmt;
    }

    public static function oleCmdExecParam(\PDO $conn, string $strQry, ?array $nvValue = null): int
    {
        $stmt = self::oleCmdParam($conn, $strQry, $nvValue);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function oleCmdExec(\PDO $conn, string $strQry): int
    {
        $stmt = self::oleCmd($conn, $strQry);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public static function oleCmdScalar(\PDO $conn, string $strQry): mixed
    {
        $stmt = self::oleCmd($conn, $strQry);
        $stmt->execute();
        return $stmt->fetchColumn(0);
    }

    public static function oleDbEnvConn(string $dbinfo = ""): \PDO
    {
        $dsn = self::getOleDsn($dbinfo);
        return self::oleGetConnection($dsn);
    }

    public static function oleExec(string $strQry, string $dbinfo = ""): int
    {
        self::oleDbEnvConn($dbinfo);
        return \PdoLite\PdoLite::exec($strQry);
    }

    public static function oleExecParm(string $strQry, array $nvValue, string $dbinfo = ""): int
    {
        $conn = self::oleDbEnvConn($dbinfo);
        return self::oleCmdExecParam($conn, $strQry, $nvValue);
    }

    public static function oleGetNextId(string $table, string $field, string $dbinfo = ""): string
    {
        self::oleDbEnvConn($dbinfo);
        return (string)\PdoLite\PdoLite::getNextId($table, $field);
    }

    public static function oleDbField(string $table, string $field, string $where, string $dbinfo = ""): string
    {
        self::oleDbEnvConn($dbinfo);
        return (string)\PdoLite\PdoLite::dbField($table, $field, $where);
    }

    public static function oleGetScalar(string $strQry, string $dbinfo = ""): string
    {
        self::oleDbEnvConn($dbinfo);
        $row = \PdoLite\PdoLite::findRow($strQry, "num");
        return ($row !== false && isset($row[0])) ? (string)$row[0] : "";
    }

    public static function oleGetList(string $strQry, string $dbinfo = ""): array
    {
        return self::oleRdr2List(self::oleDbEnvConn($dbinfo), $strQry);
    }

    public static function oleGetRows(string $strQry, string $dbinfo = ""): array
    {
        return self::oleRdr2Rows(self::oleDbEnvConn($dbinfo), $strQry);
    }

    public static function oleGet1stRow(string $strQry, string $dbinfo = ""): ?array
    {
        $rows = self::oleGetRows($strQry, $dbinfo);
        return !empty($rows) ? $rows[0] : null;
    }

    public static function oleGetRowNv(string $strQry, string $dbinfo = ""): ?array
    {
        $row = self::oleGet1stRow($strQry, $dbinfo);
        if ($row === null) {
            return null;
        }
        if (method_exists(CUtil::class, 'dict2nv')) {
            return CUtil::dict2nv($row);
        }
        return $row;
    }

    public static function oleGetRows2Json(string $strQry, string $dbinfo = ""): string
    {
        $rows = self::oleGetRows($strQry, $dbinfo);
        return json_encode($rows);
    }

    public static function oleGetRowsParam(string $strQry, array $nvValue, string $dbinfo = ""): array
    {
        return self::oleRdr2Rows(self::oleDbEnvConn($dbinfo), $strQry, $nvValue);
    }

    public static function Update(array $sparm, array $nvValue): int
    {
        $result = -1;
        try {
            if (!isset($sparm["param"])) {
                $sparm["param"] = "yes";
            }
            $strQ = CDb::nv2sUpdate($sparm["table"] ?? "", $nvValue, $sparm);
            $result = self::oleExecParm($strQ, $nvValue, $sparm["dbinfo"] ?? "");
        } catch (\Exception $ex) {
            CUtil::add2SessVar("alert", "FAILED " . $ex->getMessage());
        }
        return $result;
    }

    public static function Insert(array $sparm, array $nvValue): int
    {
        $result = -1;
        try {
            if (!isset($sparm["param"])) {
                $sparm["param"] = "yes";
            }
            $strQ = CDb::nv2sInsert($sparm["table"] ?? "", $nvValue, $sparm);
            $result = self::oleExecParm($strQ, $nvValue, $sparm["dbinfo"] ?? "");
        } catch (\Exception $ex) {
            CUtil::add2SessVar("alert", "FAILED " . $ex->getMessage());
        }
        return $result;
    }

    public static function Delete(array $sparm, ?array $nvValue = null): int
    {
        $result = -1;
        try {
            $strQ = CDb::nv2sDelete($sparm["table"] ?? "", $sparm);
            $result = self::oleExecParm($strQ, $nvValue ?? [], $sparm["dbinfo"] ?? "");
        } catch (\Exception $ex) {
            CUtil::add2SessVar("alert", "FAILED " . $ex->getMessage());
        }
        return $result;
    }

    public static function Select(array $sparm, array $nvValue): array
    {
        $rows = [];
        try {
            if (!isset($sparm["param"])) {
                $sparm["param"] = "yes";
            }
            $strQry = CDb::nv2sSelect($sparm["table"] ?? "", $nvValue, $sparm);
            $rows = self::oleGetRowsParam($strQry, $nvValue, $sparm["dbinfo"] ?? "");
        } catch (\Exception $ex) {
            CUtil::add2SessVar("alert", "FAILED " . $ex->getMessage());
        }
        return $rows;
    }

    public static function InsNextId(array $sparm, array $nvValue): string
    {
        $idKey = $sparm["id"] ?? "";
        $nvValue[$idKey] = self::oleGetNextId($sparm["table"] ?? "", $idKey, $sparm["dbinfo"] ?? "");
        $result = self::Insert($sparm, $nvValue);
        return ($result !== -1) ? (string)$nvValue[$idKey] : "";
    }
}