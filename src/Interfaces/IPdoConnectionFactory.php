<?php
namespace MvcLite\Interfaces;

interface IPdoConnectionFactory
{
    public function connect(string $database = ''): \PDO;
}
