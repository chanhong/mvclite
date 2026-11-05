<?php
namespace MvcLite;

interface IPdoConnectionFactory
{
    public function connect(string $database = ''): \PDO;
}
