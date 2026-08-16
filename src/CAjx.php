<?php

// Assuming CDbOle, CDb, CMsg, CUtils, and CCore are defined elsewhere

namespace Co {

    class CAjx extends \CCore // Use backslash for global namespace if CCore is not in the same namespace
    {
        /**
         * Ajax CRUD - Update (Put)
         *
         * @param string $tName Table name
         * @param array $sparm Query parameters (used for WHERE clause)
         * @param array $nvValue Key-value pairs for UPDATE
         * @param string $dbinfo Database connection information
         */
        public static function Put(string $tName, array $sparm, array $nvValue, string $dbinfo): void
        {
            // $nvValue->Remove(idKey); // If idKey needs to be removed, handle it here.
            // Example: if (isset($nvValue['idKey'])) unset($nvValue['idKey']);

            $connOlejsg = \CDbPdo::oleDbEnvConn($dbinfo); // Assuming static method call
            $strQry = \CDb::nv2sUpdate($tName, $nvValue, $sparm); // Assuming static method call

            // Assuming CMsg is available for debugging:
            // \CMsg::_pdmsg(sprintf("update: [%s]", $strQry));

            \CDbPdo::oleCmdExecParam($connOlejsg, $strQry, $nvValue); // Assuming static method call
        }

        /**
         * Ajax CRUD - Create (Add)
         *
         * @param string $tName Table name
         * @param array $sparm Query parameters (used for WHERE clause, though less common for Add)
         * @param array $nvValue Key-value pairs for INSERT
         * @param string $dbinfo Database connection information
         */
        public static function Add(string $tName, array $sparm, array $nvValue, string $dbinfo): void
        {
            // $nvValue->Remove(idKey); // If idKey needs to be removed, handle it here.

            $connOlejsg = \CDbPdo::oleDbEnvConn($dbinfo); // Assuming static method call
            $strQry = \CDb::nv2sInsert($tName, $nvValue, $sparm); // Assuming static method call

            // Assuming CMsg is available for debugging:
            // \CMsg::_pdmsg(sprintf("insert: [%s]", $strQry));

            \CDbPdo::oleCmdExecParam($connOlejsg, $strQry, $nvValue); // Assuming static method call
        }

        /**
         * Ajax CRUD - Delete (Del)
         *
         * @param string $tName Table name
         * @param array $sparm Query parameters (used for WHERE clause)
         * @param string $dbinfo Database connection information
         */
        public static function Del(string $tName, array $sparm, string $dbinfo): void
        {
            $connOlejsg = \CDbPdo::oleDbEnvConn($dbinfo); // Assuming static method call
            $strQry = \CDb::nv2sDelete($tName, $sparm); // Assuming static method call

            // Assuming CMsg is available for debugging:
            // \CMsg::_pdmsg(sprintf("delete: [%s]", $strQry));

            \CDbPdo::oleCmdExecParam($connOlejsg, $strQry, null); // Assuming static method call
        }

        /**
         * Ajax CRUD - Read (Get)
         *
         * @param string $tName Table name
         * @param array $sparm Query parameters (used for WHERE clause)
         * @param array $nvValue Values for SELECT (can be empty or contain specific column names)
         * @param string $dbinfo Database connection information
         */
        public static function Get(string $tName, array $sparm, array $nvValue, string $dbinfo): void
        {
            $connOlejsg = \CDbPdo::oleDbEnvConn($dbinfo); // Assuming static method call
            $strQry = \CDb::nv2sSelect($tName, $nvValue, $sparm); // Assuming static method call

            // Assuming CMsg is available for debugging:
            // \CMsg::_pdmsg(sprintf("select: [%s]", $strQry));

            // original C# was: nvValue = CUtils.nv2NvLike(nvValue);
            // $nvValue = \CUtils::nv2NvLike($nvValue); // Assuming static method call

            // Original C# commented out: string arrStr = CDbOle.oleRdr2Json(connOlejsg, strQry, nvValue);
            // The active line in C# was: string arrStr = CDbOle.oleRdr2Json(strQry, nvValue, dbinfo);
            // Replicating that:
            $arrStr = \CDbPdo::oleRdr2Json($strQry, $nvValue, $dbinfo); // Assuming static method call

            \CUtil::outJson($arrStr); // Assuming static method call

        }
        public static function pDoGet($me, string $tName, string $opr, string $where): void
        {
            $me->meTable = $tName;
            $me->model = new \CModel($me->meTable); // create a mini model class in model folder
            $where = "1 = 1";
            $sparm = [["where" => $where]];
            $rows = $me->model->_dbt($opr, ['where' => $where]);
            \CUtil::outJson(json_encode($rows)); // Assuming static method call   

        }

        public static function Json($ajson): void
        {
            $age = array("Peter" => 35, "Ben" => 37, "Joe" => 43);
            // Output the JSON (similar to Response.Write in ASP.NET)
//    header('Content-Type: application/json');
            \CUtil::outJson(json_encode($ajson)); // Assuming static method call
        }
        /*
// WORK, use the odata\view to query data         $rows= $this->model->_dbt("select", ['where' => $where]);   and outjson there
        public static function Sample(string $tName, array $sparm=[], array $nvValue=[], string $dbinfo = ""): void
        {
            $sql = "SELECT * FROM $tName";
            // -----------------------------------------------------------------
            // 1️⃣ Get a SQL Server connection.
            // -----------------------------------------------------------------
            // The equivalent of CDb.getSqlDsn("") would be a DSN string.
            // Adjust the server name, database, authentication method, etc.
            // -----------------------------------------------------------------
            $serverName = "localhost\\SQLEXPRESS";          // e.g. "localhost\\SQLEXPRESS"
            $connectionOptions = [
                "Database" => "coazportal_db",
                // Windows Authentication:
                "UID" => "",
                "PWD" => "",
                "TrustServerCertificate" => true,
                // OR SQL Authentication:
                //"UID" => "YOUR_USERNAME",
                //"PWD" => "YOUR_PASSWORD",
                "Encrypt" => false,
                "TrustServerCertificate" => true
            ];

            $conn = sqlsrv_connect($serverName, $connectionOptions);

            if ($conn === false) {
                // Throw an exception so the catch block below can handle it
                $errors = sqlsrv_errors();
                $msg = "Connection failed:\n";
                foreach ($errors as $err) {
                    $msg .= $err['message'] . "\n";
                }
                throw new Exception($msg);
            }

            // -----------------------------------------------------------------
            // 2️⃣ Execute the query.
            // -----------------------------------------------------------------
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                $msg = "Query failed:\n";
                foreach ($errors as $err) {
                    $msg .= $err['message'] . "\n";
                }
                throw new Exception($msg);
            }

            // -----------------------------------------------------------------
            // 3️⃣ Fetch all rows into an array (similar to a DataTable in C#).
            // -----------------------------------------------------------------
            $rows = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }

            // Free the statement and close the connection
            sqlsrv_free_stmt($stmt);
            sqlsrv_close($conn);

            // -----------------------------------------------------------------
            // 4️⃣ Convert the result set to JSON.
            // -----------------------------------------------------------------
            $jsonResult = json_encode($rows, JSON_PRETTY_PRINT);
            if ($jsonResult === false) {
                throw new Exception('JSON encoding error: ' . json_last_error_msg());
            }

            // Output the JSON (similar to Response.Write in ASP.NET)
//            header('Content-Type: application/json');
//            echo $jsonResult;
            //            $jsonResult = \CDbPdo::oleRdr2Json($sql, $nvValue, $dbinfo); // Assuming static method call
            \CUtil::outJson(json_encode($rows)); // Assuming static method call
        }
*/
    } // class
} // namespace