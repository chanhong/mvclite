<?php
namespace MvcLite;

use MvcLite\Interfaces\IPdoConnectionFactory;
use MvcLite\Interfaces\IRowSchema;

/**
 * Reads typed column metadata through the injected, database-specific PDO connection.
 *
 * This is separate from PdoLite::schema()/fieldsKey(), which provide column names
 * through PdoLite's static/default connection but do not return column types. The
 * AJAX data gateway needs those types to clean submitted values and must inspect
 * the requested database, so this adapter uses the same connection factory as the
 * gateway's query and command services.
 */
final class PdoRowSchema implements IRowSchema
{
    public function __construct(private IPdoConnectionFactory $connections)
    {
    }

    public function columns(string $table, string $database = ''): array
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $table)) {
            throw new \InvalidArgumentException('Invalid database table name.');
        }

        $query = 'SELECT * FROM [' . $table . '] WHERE 1 = 0';
        try {
            $statement = $this->connections->connect($database)->prepare($query);
            $statement->execute();
        } catch (\PDOException $exception) {
            throw CDbPdo::queryException($exception, $query);
        }

        $columns = [];
        for ($index = 0; $index < $statement->columnCount(); $index++) {
            $metadata = $statement->getColumnMeta($index);
            if ($metadata === false || !isset($metadata['name'])) {
                throw new \RuntimeException('Unable to read database column metadata.');
            }

            $columns[(string) $metadata['name']] = $this->columnType($metadata);
        }

        return $columns;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function columnType(array $metadata): string
    {
        $declaredType = (string) (
            $metadata['sqlsrv:decl_type']
            ?? $metadata['sqlite:decl_type']
            ?? $metadata['native_type']
            ?? ''
        );
        $declaredType = strtolower(trim($declaredType));
        if (preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)\b/', $declaredType)) {
            return 'int';
        }
        if (preg_match('/^(real|float|double|decimal|numeric)\b/', $declaredType)) {
            return 'float';
        }

        return $declaredType;
    }
}
