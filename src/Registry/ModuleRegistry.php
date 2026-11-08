<?php

declare(strict_types=1);

namespace Supe\Core\Registry;

use Supe\Core\Contracts\ModuleInterface;
use Supe\Core\Exceptions\InvalidModuleException;
use Supe\Core\Support\Container;

/**
 * ModuleRegistry
 *
 * Collects all module candidates and, when resolve() is called, picks the
 * highest-registered version of each unique module ID and boots it.
 *
 * Multiple plugins/themes may each carry their own copy of "acme/gallery".
 * Only the newest version actually boots; the rest are silently skipped.
 */
final class ModuleRegistry {

    /** @var array<string, list<ModuleCandidate>> keyed by module ID */
    private array $candidates = [];

    /** @var array<string, string> module ID → winning version */
    private array $loaded = [];

    private Container $container;

    public function __construct( Container $container ) {
        $this->container = $container;
    }

    // -------------------------------------------------------------------------
    // Registration (called by plugins/themes before plugins_loaded priority 0)
    // -------------------------------------------------------------------------

    /**
     * Register a module class with Supe.
     *
     * @param  class-string<ModuleInterface> $moduleClass
     * @throws InvalidModuleException
     */
    public function register( string $moduleClass ): void {
        if ( ! class_exists( $moduleClass ) ) {
            throw new InvalidModuleException(
                "Supe: module class '{$moduleClass}' does not exist."
            );
        }

        if ( ! is_subclass_of( $moduleClass, ModuleInterface::class ) ) {
            throw new InvalidModuleException(
                "Supe: '{$moduleClass}' must implement ModuleInterface."
            );
        }

        $id      = $moduleClass::id();
        $version = $moduleClass::version();

        $this->candidates[ $id ][] = new ModuleCandidate( $moduleClass, $id, $version );

        /**
         * Fires whenever a module candidate is added to the registry.
         *
         * @param string $id
         * @param string $version
         * @param string $moduleClass
         */
        do_action( 'supe/module_registered', $id, $version, $moduleClass );
    }

    // -------------------------------------------------------------------------
    // Resolution (called internally by Core::boot)
    // -------------------------------------------------------------------------

    /**
     * Walk every module ID, pick the newest version, and boot it.
     */
    public function resolve(): void {
        foreach ( $this->candidates as $id => $candidates ) {
            $winner = $this->pickWinner( $candidates );
            $this->bootCandidate( $winner );
        }
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * @param  list<ModuleCandidate> $candidates
     */
    private function pickWinner( array $candidates ): ModuleCandidate {
        usort( $candidates, static function ( ModuleCandidate $a, ModuleCandidate $b ): int {
            return version_compare( $b->version, $a->version ); // descending
        } );

        return $candidates[0]; // highest version wins
    }

    private function bootCandidate( ModuleCandidate $candidate ): void {
        /** @var ModuleInterface $module */
        $module = new $candidate->class( $this->container );

        /**
         * Fires just before a module boots.
         *
         * @param ModuleInterface $module
         */
        do_action( 'supe/before_module_boot', $module );

        $module->boot();

        $this->loaded[ $candidate->id ] = $candidate->version;

        /**
         * Fires after a module has successfully booted.
         *
         * @param ModuleInterface $module
         */
        do_action( 'supe/after_module_boot', $module );
        do_action( "supe/module_booted/{$candidate->id}", $module );
    }

    // -------------------------------------------------------------------------
    // Introspection
    // -------------------------------------------------------------------------

    /**
     * Returns a map of module ID → booted version.
     * Only populated after resolve() has run.
     *
     * @return array<string, string>
     */
    public function getLoadedModules(): array {
        return $this->loaded;
    }

    /**
     * Check if a module ID has been booted (and optionally meets a min version).
     */
    public function isLoaded( string $id, ?string $minVersion = null ): bool {
        if ( ! isset( $this->loaded[ $id ] ) ) {
            return false;
        }

        if ( $minVersion !== null ) {
            return version_compare( $this->loaded[ $id ], $minVersion, '>=' );
        }

        return true;
    }

    /**
     * Returns all registered candidates (useful for debugging).
     *
     * @return array<string, list<ModuleCandidate>>
     */
    public function getCandidates(): array {
        return $this->candidates;
    }
}
