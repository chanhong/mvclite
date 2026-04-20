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

class CContainer
{

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
    public function make_before_autowire(string $id): mixed
    {
        if (!isset($this->bindings[$id])) {
            throw new \RuntimeException("Container: no binding registered for '$id'.");
        }
        return ($this->bindings[$id])($this);
    }

    public function make(string $id): mixed
    {
        // 1. existing binding? use it
        if (isset($this->bindings[$id])) {
            return ($this->bindings[$id])($this);
        }

        // 2. no binding — try to auto-wire if $id is a valid class
        if (class_exists($id)) {
            return $this->autoWire($id);
        }

        throw new \RuntimeException("Container: no binding registered for '$id'.");
    }

    /*
    // auto wire work in these 3 cases of classes
    // So the simple rule going forward is: if your new class only depends on other classes (not strings, arrays, or primitives), auto-wire handles it automatically. No index.php changes needed.
    1. No constructor at all
    phpclass CMyHelper {
        // nothing
    }
    2. Constructor with no parameters
    phpclass CMyHelper {
        public function __construct() { }
    }
    3. Constructor with type-hinted class parameters
    phpclass CMyService {
        public function __construct(CUtil $ut, CHelper $h) { }  // ← auto-wired recursively

    // It will NOT auto-wire when constructor has:
        public function __construct(string $salt) { }    // ← primitive, no type to reflect on
        public function __construct(array $config) { }   // ← same problem
        public function __construct(mixed $anything) { } // ← too vague
    */
    private function autoWire(string $className): mixed
    {
        $ref = new \ReflectionClass($className);
        $constructor = $ref->getConstructor();

        // no constructor or no params — just new it
        if (!$constructor || !$constructor->getParameters()) {
            return new $className();
        }

        // resolve each parameter by its type hint
        $deps = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $deps[] = $this->make($type->getName()); // recursive resolve
            } elseif ($param->isOptional()) {
                $deps[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException("Container: cannot auto-wire parameter '\${$param->getName()}' in $className.");
            }
        }

        return $ref->newInstanceArgs($deps);
    }

    /**
     * Check whether a binding exists without throwing.
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }
}
