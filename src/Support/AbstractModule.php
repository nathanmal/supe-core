<?php

declare(strict_types=1);

namespace Supe\Core\Support;

use Supe\Core\Admin\AdminMenuRegistry;
use Supe\Core\Contracts\AdminPageInterface;
use Supe\Core\Contracts\ModuleInterface;

/**
 * AbstractModule
 *
 * Convenience base class. Modules may extend this instead of implementing
 * ModuleInterface directly. Provides access to the container and a set of
 * helper methods for common WordPress registrations.
 */
abstract class AbstractModule implements ModuleInterface {

    protected Container $container;

    public function __construct( Container $container ) {
        $this->container = $container;
    }

    // -------------------------------------------------------------------------
    // Subclasses must define id(), version(), and boot()
    // -------------------------------------------------------------------------

    abstract public static function id(): string;
    abstract public static function version(): string;
    abstract public function boot(): void;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Bind a service into the shared container.
     */
    protected function bind( string $abstract, callable $factory ): void {
        $this->container->bind( $abstract, $factory );
    }

    /**
     * Bind a singleton into the shared container.
     */
    protected function singleton( string $abstract, callable $factory ): void {
        $this->container->singleton( $abstract, $factory );
    }

    /**
     * Resolve a binding from the container.
     */
    protected function make( string $abstract ): mixed {
        return $this->container->make( $abstract );
    }

    /**
     * Register an admin page (or top-level menu) with the shared admin
     * menu registry. It's added to WordPress on the `admin_menu` hook.
     *
     * @param  class-string<AdminPageInterface> $pageClass
     */
    protected function addAdminPage( string $pageClass ): void {
        $this->make( AdminMenuRegistry::class )->addPage( $pageClass );
    }

    /**
     * Register a WordPress action with an optional priority / accepted args.
     */
    protected function addAction(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): void {
        add_action( $hook, $callback, $priority, $acceptedArgs );
    }

    /**
     * Register a WordPress filter.
     */
    protected function addFilter(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): void {
        add_filter( $hook, $callback, $priority, $acceptedArgs );
    }
}
