<?php

declare(strict_types=1);

namespace Supe\Core\Contracts;

use Supe\Core\Support\Container;

/**
 * ModuleInterface
 *
 * Every Supe module must implement this contract.
 *
 * Modules are instantiated by the registry with the shared container, then
 * boot() is called exactly once — for the highest registered version only.
 */
interface ModuleInterface {

    public function __construct( Container $container );

    /**
     * Globally unique module identifier.
     * Use reverse-DNS / composer-style: "vendor/module-name"
     *
     * @example "acme/gallery"
     */
    public static function id(): string;

    /**
     * Semantic version string for this release of the module.
     *
     * @example "2.1.0"
     */
    public static function version(): string;

    /**
     * Called once when this module wins the version race and should register
     * its hooks, post types, REST routes, services, etc.
     */
    public function boot(): void;
}
