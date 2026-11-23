<?php

declare(strict_types=1);

namespace Supe\Core\Admin;

use Supe\Core\Contracts\AdminPageInterface;
use Supe\Core\Exceptions\InvalidAdminPageException;
use Supe\Core\Support\Container;

/**
 * AdminMenuRegistry
 *
 * Collects admin page classes registered by any module and, on the
 * `admin_menu` hook, instantiates them and registers each with WordPress.
 *
 * Unlike ModuleRegistry there is no version race here — every registered
 * page is added. Two pages registered under the same slug simply overwrite
 * one another (last registration wins), since WordPress itself has no
 * notion of "the highest version of a menu slug".
 */
final class AdminMenuRegistry {

    /** @var array<string, class-string<AdminPageInterface>> keyed by page slug */
    private array $pages = [];

    /** @var array<string, AdminPageInterface> lazily-built page instances, keyed by slug */
    private array $instances = [];

    private Container $container;

    public function __construct( Container $container ) {
        $this->container = $container;
    }

    // -------------------------------------------------------------------------
    // Registration (called by modules during their own boot())
    // -------------------------------------------------------------------------

    /**
     * Register an admin page class with Supe.
     *
     * @param  class-string<AdminPageInterface> $pageClass
     * @throws InvalidAdminPageException
     */
    public function addPage( string $pageClass ): void {
        if ( ! class_exists( $pageClass ) ) {
            throw new InvalidAdminPageException(
                "Supe: admin page class '{$pageClass}' does not exist."
            );
        }

        if ( ! is_subclass_of( $pageClass, AdminPageInterface::class ) ) {
            throw new InvalidAdminPageException(
                "Supe: '{$pageClass}' must implement AdminPageInterface."
            );
        }

        $slug = $pageClass::slug();

        $this->pages[ $slug ] = $pageClass;
        unset( $this->instances[ $slug ] );

        /**
         * Fires whenever an admin page is added to the registry.
         *
         * @param string $slug
         * @param string $pageClass
         */
        do_action( 'supe/admin_page_registered', $slug, $pageClass );
    }

    // -------------------------------------------------------------------------
    // WordPress wiring (hooked onto `admin_menu` by Core)
    // -------------------------------------------------------------------------

    /**
     * Instantiate every registered page and add it to the WordPress admin
     * menu. Top-level pages are added before submenu pages, since a submenu
     * page's parent slug must already exist in the WP admin menu.
     */
    public function registerMenus(): void {
        $topLevel = [];
        $submenu  = [];

        foreach ( array_keys( $this->pages ) as $slug ) {
            $page = $this->page( $slug );

            if ( $page->parentSlug() === null ) {
                $topLevel[ $slug ] = $page;
            } else {
                $submenu[ $slug ] = $page;
            }
        }

        foreach ( $topLevel as $slug => $page ) {
            add_menu_page(
                $page->pageTitle(),
                $page->menuTitle(),
                $page->capability(),
                $slug,
                [ $page, 'render' ],
                $page->icon(),
                $page->position()
            );

            $this->announce( $slug, $page );
        }

        foreach ( $submenu as $slug => $page ) {
            add_submenu_page(
                $page->parentSlug(),
                $page->pageTitle(),
                $page->menuTitle(),
                $page->capability(),
                $slug,
                [ $page, 'render' ],
                $page->position()
            );

            $this->announce( $slug, $page );
        }
    }

    private function announce( string $slug, AdminPageInterface $page ): void {
        /**
         * Fires after an admin page has been added to the WordPress menu.
         *
         * @param string             $slug
         * @param AdminPageInterface $page
         */
        do_action( 'supe/admin_page_added', $slug, $page );
    }

    // -------------------------------------------------------------------------
    // Introspection
    // -------------------------------------------------------------------------

    /**
     * Check if a page slug has been registered.
     */
    public function hasPage( string $slug ): bool {
        return isset( $this->pages[ $slug ] );
    }

    /**
     * Every page in the same menu as $slug: that menu's top-level page first,
     * then each of its submenu pages in registration order. Used to render
     * the shared tab bar. Empty when $slug isn't registered or is a submenu
     * of a non-Supe menu (e.g. 'options-general.php').
     *
     * @return array<string, AdminPageInterface> keyed by slug
     */
    public function familyOf( string $slug ): array {
        if ( ! isset( $this->pages[ $slug ] ) ) {
            return [];
        }

        $root = $this->page( $slug )->parentSlug() ?? $slug;

        if ( ! isset( $this->pages[ $root ] ) ) {
            return [];
        }

        $family = [ $root => $this->page( $root ) ];

        foreach ( array_keys( $this->pages ) as $candidate ) {
            if ( $candidate !== $root && $this->page( $candidate )->parentSlug() === $root ) {
                $family[ $candidate ] = $this->page( $candidate );
            }
        }

        return $family;
    }

    /**
     * Returns all registered pages, keyed by slug (useful for debugging).
     *
     * @return array<string, class-string<AdminPageInterface>>
     */
    public function getPages(): array {
        return $this->pages;
    }

    /**
     * The single shared instance of a registered page, built on first use.
     */
    private function page( string $slug ): AdminPageInterface {
        if ( ! isset( $this->instances[ $slug ] ) ) {
            $pageClass                = $this->pages[ $slug ];
            $this->instances[ $slug ] = new $pageClass( $this->container );
        }

        return $this->instances[ $slug ];
    }
}
