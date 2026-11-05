<?php
namespace MvcLite;

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
