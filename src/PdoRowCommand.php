<?php
namespace MvcLite;

use MvcLite\Interfaces\IPdoConnectionFactory;
use MvcLite\Interfaces\IRowCommand;

final class PdoRowCommand implements IRowCommand
{
    public function __construct(private IPdoConnectionFactory $connections)
    {
    }

    public function execute(string $query, array $parameters = [], string $database = ''): int
    {
        try {
            $statement = $this->connections->connect($database)->prepare($query);
            $statement->execute($parameters);
        } catch (\PDOException $exception) {
            throw CDbPdo::queryException($exception, $query);
        }

        return $statement->rowCount();
    }
}
