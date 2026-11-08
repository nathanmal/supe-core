<?php

declare(strict_types=1);

namespace Supe\Core;

use Supe\Core\Admin\AdminMenuRegistry;
use Supe\Core\Admin\DashboardPage;
use Supe\Core\Registry\ModuleRegistry;
use Supe\Core\Support\Container;

/**
 * Supe Core
 *
 * Singleton that owns the module registry, admin menu registry, and service
 * container. Plugins and themes never instantiate this directly — they call
 * ModuleRegistry::register() from their own bootstrap files.
 */
final class Core {

    private static ?self $instance = null;

    private ModuleRegistry $registry;
    private AdminMenuRegistry $adminMenuRegistry;
    private Container $container;
    private bool $booted = false;

    private function __construct() {
        $this->container         = new Container();
        $this->registry          = new ModuleRegistry( $this->container );
        $this->adminMenuRegistry = new AdminMenuRegistry( $this->container );

        $this->container->instance( AdminMenuRegistry::class, $this->adminMenuRegistry );
        $this->adminMenuRegistry->addPage( DashboardPage::class );

        add_action( 'admin_menu', [ $this->adminMenuRegistry, 'registerMenus' ] );
    }

    // -------------------------------------------------------------------------
    // Singleton access
    // -------------------------------------------------------------------------

    public static function getInstance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // -------------------------------------------------------------------------
    // Boot
    // -------------------------------------------------------------------------

    /**
     * Called once on plugins_loaded priority 0.
     * After this point modules should not be registered (they would miss hooks).
     */
    public function boot(): void {
        if ( $this->booted ) {
            return;
        }

        $this->booted = true;

        /**
         * Fires before Supe resolves and boots modules.
         * Use this hook to register modules from a plugin or theme.
         *
         * @param ModuleRegistry $registry
         */
        do_action( 'supe/before_boot', $this->registry );

        $this->registry->resolve();

        /**
         * Fires after all winning module versions have been booted.
         *
         * @param ModuleRegistry $registry
         */
        do_action( 'supe/after_boot', $this->registry );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getRegistry(): ModuleRegistry {
        return $this->registry;
    }

    public function getAdminMenuRegistry(): AdminMenuRegistry {
        return $this->adminMenuRegistry;
    }

    public function getContainer(): Container {
        return $this->container;
    }

    public function isBooted(): bool {
        return $this->booted;
    }
}
