<?php
namespace MvcLite;

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

        $statement = $this->connections->connect($database)->prepare(
            'SELECT * FROM [' . $table . '] WHERE 1 = 0'
        );
        $statement->execute();

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
