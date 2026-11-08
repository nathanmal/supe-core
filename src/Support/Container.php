<?php

declare(strict_types=1);

namespace Supe\Core\Support;

use Supe\Core\Exceptions\ContainerException;

/**
 * Container
 *
 * Lightweight PSR-11-inspired dependency injection container.
 * Supports bindings, singletons, and simple auto-wiring.
 *
 * This is intentionally minimal. If a project needs a full-featured
 * container (e.g. Laravel's Illuminate\Container) they can swap it out
 * by extending this class or replacing the binding in supe.php.
 */
final class Container {

    /** @var array<string, callable> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $singletons = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /**
     * Register a factory — a new instance is created on every make() call.
     */
    public function bind( string $abstract, callable $factory ): void {
        $this->bindings[ $abstract ] = $factory;
    }

    /**
     * Register a singleton — the same instance is returned on every make() call.
     */
    public function singleton( string $abstract, callable $factory ): void {
        $this->singletons[ $abstract ] = $factory;
    }

    /**
     * Register an already-constructed instance directly.
     */
    public function instance( string $abstract, mixed $concrete ): void {
        $this->instances[ $abstract ] = $concrete;
    }

    // -------------------------------------------------------------------------
    // Resolution
    // -------------------------------------------------------------------------

    /**
     * Resolve a binding from the container.
     *
     * @throws ContainerException when the abstract is unresolvable.
     */
    public function make( string $abstract ): mixed {
        // Pre-bound instances (highest priority).
        if ( isset( $this->instances[ $abstract ] ) ) {
            return $this->instances[ $abstract ];
        }

        // Singleton — instantiate once, then cache.
        if ( isset( $this->singletons[ $abstract ] ) ) {
            if ( ! array_key_exists( $abstract, $this->instances ) ) {
                $this->instances[ $abstract ] = ( $this->singletons[ $abstract ] )( $this );
            }

            return $this->instances[ $abstract ];
        }

        // Factory binding — new instance each time.
        if ( isset( $this->bindings[ $abstract ] ) ) {
            return ( $this->bindings[ $abstract ] )( $this );
        }

        // Auto-wire a concrete class with no dependencies.
        if ( class_exists( $abstract ) ) {
            return new $abstract();
        }

        throw new ContainerException(
            "Supe Container: cannot resolve '{$abstract}'. No binding registered."
        );
    }

    /**
     * Check whether an abstract is registered.
     */
    public function has( string $abstract ): bool {
        return isset( $this->instances[ $abstract ] )
            || isset( $this->singletons[ $abstract ] )
            || isset( $this->bindings[ $abstract ] )
            || class_exists( $abstract );
    }
}
