<?php

/**
 * Minimal DI Container for MvcLite.
 *
 * Usage — in your front controller / index.php:
 *
 *   $container = new \MvcLite\Container();
 *
 *   $container->singleton('db',     fn() => new \PdoLite\PdoLite());
 *   $container->singleton('util',   fn() => new \MvcLite\CUtil());
 *   $container->singleton('helper', fn() => new \MvcLite\CHelper());
 *   $container->singleton('auth',   fn() => \MvcLite\CAuth::getAuth('MvcLiteSALT'));
 *   $container->singleton('error',  fn() => \MvcLite\CError::getError());
 *
 *   \MvcLite\CCore::setContainer($container);
 */

namespace MvcLite;

defined('_MVCLite') or die('Direct Access to this location is not allowed.');

class CContainer {

    /** @var array<string, callable> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /**
     * Register a factory that is called every time make() is called.
     */
    public function bind(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    /**
     * Register a factory whose result is cached after the first call —
     * every make() for this $id returns the same instance.
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->bindings[$id] = function () use ($id, $factory) {
            if (!array_key_exists($id, $this->instances)) {
                $this->instances[$id] = $factory($this);
            }
            return $this->instances[$id];
        };
    }

    // -------------------------------------------------------------------------
    // Resolution
    // -------------------------------------------------------------------------

    /**
     * Build (or return a cached singleton for) the given $id.
     *
     * @throws \RuntimeException if no binding was registered for $id
     */
    public function make(string $id): mixed
    {
        if (!isset($this->bindings[$id])) {
            throw new \RuntimeException("Container: no binding registered for '$id'.");
        }
        return ($this->bindings[$id])($this);
    }

    /**
     * Check whether a binding exists without throwing.
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }
}
