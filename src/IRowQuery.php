<?php
namespace MvcLite;

interface IRowQuery
{
    /**
     * Execute a read query and return its rows.
     *
     * @param array<int|string, mixed> $parameters
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $query, array $parameters = [], string $database = ''): array;
}
