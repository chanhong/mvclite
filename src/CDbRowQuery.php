<?php
namespace MvcLite;

class CDbRowQuery implements IRowQuery
{
    public function fetchAll(string $query, array $parameters = [], string $database = ''): array
    {
        if ($parameters === []) {
            return CDbPdo::oleGetRows($query, $database);
        }

        return CDbPdo::oleGetRowsParam($query, $parameters, $database);
    }
}
