<?php
namespace MvcLite;

use MvcLite\Interfaces\ISessionStore;

class PhpSessionStore implements ISessionStore
{
    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $_SESSION ?? []) ? $_SESSION[$key] : $default;
    }

    public function all(): array
    {
        return $_SESSION ?? [];
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
