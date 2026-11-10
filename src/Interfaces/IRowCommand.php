<?php
namespace MvcLite\Interfaces;

interface IRowCommand
{
    /**
     * Execute a write statement and return the affected row count.
     *
     * @param array<int|string, mixed> $parameters
     */
    public function execute(string $query, array $parameters = [], string $database = ''): int;
}
