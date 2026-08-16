<?php

/**
 * @author Chanh Ong
 * @package eJV
 * @since 2.0
 */
namespace MvcLite;

class CModel extends CCore {

    public function __construct($tname, $id = null) {
        
        parent::__construct();
        // get table name from controller
        $this->meTable = $tname;         
    }

    public function _dbt($opr, $text_or_array) {
        if (!empty($opr) and !empty($text_or_array)) {
            $allowArray = ['select', 'dbrow', 'update', 'delete', 'insert', 'getnextid'];
            if (in_array($opr, $allowArray) and !empty($text_or_array)) { // false if not found
                return $this->db->$opr($this->meTable, $text_or_array);
            }
        }
    }


        /**
         * Retrieves schema information from the database for a given table.
         *
         * @param string $tName The name of the table.
         * @param string $appstring Optional application string for database connection.
         * @return array Associative array representing the schema. Returns an empty array if table name is invalid or schema cannot be retrieved.
         */
        public static function gSchemaFromDbain(string $tName, string $appstring = ""): array
        {
            $nvSchema = []; // PHP associative array equivalent to NameValueCollection

            if (!empty($tName)) {
                // Assuming CDb::getOleDsn returns the DSN string
                $jsgoledsn = \CDb::getOleDsn($appstring); // Assuming static method call

                // Safety: Ensure $tName is a valid identifier to prevent SQL injection if not already sanitized.
                // For simplicity here, assuming $tName is safe or handled by CDb::getOleDsn if it sanitizes.
                // A more robust solution might quote $tName.
                $strQry = "SELECT TOP 1 * FROM [" . str_replace("'", "''", $tName) . "]"; // Basic quoting for table name

                // Assuming CDbPdo::oleSchemaExNv returns an associative array (NameValueCollection equivalent)
                $nvSchemaEx = \CDbPdo::oleSchemaExNv($jsgoledsn, $strQry); // Assuming static method call

                // Assuming CDb::nvSchemaByType processes the schema array to extract type information
                $nvSchema = \CDb::nvSchemaByType($nvSchemaEx, "type"); // Assuming static method call
            }
            return $nvSchema;
        }

        /**
         * Filters and formats request data based on table schema.
         *
         * @param array $rForm The incoming request data (e.g., $_POST or $_GET, as an associative array).
         * @param string $tName The name of the table to get the schema from.
         * @param string $appstring Optional application string for database connection.
         * @return array Associative array containing the filtered and cleaned request values.
         */
        public static function request2Nv(array $rForm, string $tName, string $appstring = ""): array
        {
            // Default to dbname from application if not provided
            $nvSchema = self::gSchemaFromDbain($tName, $appstring); // Use self:: for static calls within the same class
            $nvValue = []; // PHP associative array equivalent to NameValueCollection

            // Iterate through the schema keys (column names)
            foreach (array_keys($nvSchema) as $ka) { // Using array_keys to iterate through schema column names
                // 'cleanStr' needs to be defined. It should take the value, schema type, and determine cleaning strategy.
                // The original C# had `cleanStr(rForm[ka], nvSchema[ka])` and a commented out `CUtils.cleanStr(..., decode)`.
                // We'll use the first version and assume 'cleanStr' is a static method available in CUtils or globally.
                // If `nvSchema[ka]` contains the data type or other cleaning hints.
                
                // Assuming CUtils::cleanStr is the target for the cleaning logic.
                // The 'decode' parameter from the commented C# code is omitted as it's not in the uncommented version.
                if (isset($rForm[$ka])) { // Only process if the key exists in the request form
                    $cleanedValue = \CUtil::cleanStr($rForm[$ka], $nvSchema[$ka] ?? null); // Pass schema type hint, default to null if not found
                    $nvValue[$ka] = $cleanedValue;
                }
            }
            return $nvValue;
        }

        // The purpose of 'decode' is unclear without context. The uncommented C# code
        // does not pass a 'decode' parameter to CUtils.cleanStr. If 'decode' was
        // meant to control encoding/decoding, it would need to be passed as an argument
        // to this PHP function as well and handled within CUtils::cleanStr.
    }

