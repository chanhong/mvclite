<?php
namespace MvcLite;

class CDbRowQuery implements IRowQuery
{
    public function __construct(private IPdoConnectionFactory $connections)
    {
    }

    public function fetchAll(string $query, array $parameters = [], string $database = ''): array
    {
        $statement = $this->connections->connect($database)->prepare($query);
        $statement->execute($parameters);

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
