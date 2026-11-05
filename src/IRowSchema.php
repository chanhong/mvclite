<?php
namespace MvcLite;

interface IRowSchema
{
    /**
     * Return column names and database types for a table.
     *
     * @return array<string, string>
     */
    public function columns(string $table, string $database = ''): array;
}
