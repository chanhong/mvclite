<?php
namespace MvcLite\Interfaces;

interface ISessionStore
{
    public function get(string $key, mixed $default = null): mixed;

    public function all(): array;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;
}
