<?php

declare(strict_types=1);

namespace Supe\Core\Support;

use Supe\Core\Contracts\AdminPageInterface;

/**
 * AbstractAdminPage
 *
 * Convenience base class for admin pages. Pages may extend this instead of
 * implementing AdminPageInterface directly. Supplies sensible defaults for
 * every method except the three every page must define for itself: slug(),
 * pageTitle(), and render().
 */
abstract class AbstractAdminPage implements AdminPageInterface {

    protected Container $container;

    public function __construct( Container $container ) {
        $this->container = $container;
    }

    // -------------------------------------------------------------------------
    // Subclasses must define slug(), pageTitle(), and render()
    // -------------------------------------------------------------------------

    abstract public static function slug(): string;
    abstract public function pageTitle(): string;
    abstract public function render(): void;

    // -------------------------------------------------------------------------
    // Sensible defaults — override any of these as needed
    // -------------------------------------------------------------------------

    public function menuTitle(): string {
        return $this->pageTitle();
    }

    public function capability(): string {
        return 'manage_options';
    }

    public function parentSlug(): ?string {
        return null; // registers a new top-level menu by default
    }

    public function icon(): string {
        return '';
    }

    public function position(): ?int {
        return null;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve a binding from the shared container.
     */
    protected function make( string $abstract ): mixed {
        return $this->container->make( $abstract );
    }

    /**
     * Render a PHP template file with $data extracted into its scope.
     * Intended to be called from render().
     */
    protected function view( string $templatePath, array $data = [] ): void {
        if ( ! file_exists( $templatePath ) ) {
            return;
        }

        extract( $data, EXTR_SKIP );
        require $templatePath;
    }
}
