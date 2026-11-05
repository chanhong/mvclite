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
        $statement = $this->connections->connect($database)->prepare($query);
        $statement->execute($parameters);

        return $statement->rowCount();
    }
}
