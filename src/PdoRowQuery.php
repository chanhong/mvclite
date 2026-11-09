<?php
namespace MvcLite;

use MvcLite\Interfaces\IPdoConnectionFactory;
use MvcLite\Interfaces\IRowQuery;

class PdoRowQuery implements IRowQuery
{
    public function __construct(private IPdoConnectionFactory $connections)
    {
    }

    public function fetchAll(string $query, array $parameters = [], string $database = ''): array
    {
        try {
            $statement = $this->connections->connect($database)->prepare($query);
            $statement->execute($parameters);
        } catch (\PDOException $exception) {
            throw CDbPdo::queryException($exception, $query);
        }

        $rows = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $normalized = [];
            foreach ($row as $column => $value) {
                $normalized[strtolower((string) $column)] = $value !== null ? (string) $value : '';
            }
            $rows[] = $normalized;
        }

        return $rows;
    }
}
