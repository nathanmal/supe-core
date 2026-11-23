<?php

declare(strict_types=1);

namespace Supe\Core\Support;

use Supe\Core\Admin\AdminMenuRegistry;
use Supe\Core\Contracts\AdminPageInterface;
use Supe\Core\Exceptions\ContainerException;

/**
 * AbstractAdminPage
 *
 * Convenience base class for admin pages. Pages may extend this instead of
 * implementing AdminPageInterface directly. Supplies sensible defaults for
 * every method except slug() and pageTitle().
 *
 * render() outputs the shared Supe page shell — branded header with the
 * page title, description() and version(), a tab bar linking every page in
 * the same Supe menu, then the page's own renderContent() below it. Pages
 * implement renderContent() (using AdminUi helpers for cards, tables, etc.)
 * rather than render(). Overriding render() still works, but opts the page
 * out of the shared shell.
 */
abstract class AbstractAdminPage implements AdminPageInterface {

    protected Container $container;

    public function __construct( Container $container ) {
        $this->container = $container;
    }

    // -------------------------------------------------------------------------
    // Subclasses must define slug() and pageTitle()
    // -------------------------------------------------------------------------

    abstract public static function slug(): string;
    abstract public function pageTitle(): string;

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

    /**
     * One-line summary shown under the title in the page header.
     */
    public function description(): string {
        return '';
    }

    /**
     * Version shown as a pill in the page header (typically the owning
     * module's Module::version()). Empty hides the pill.
     */
    public function version(): string {
        return '';
    }

    /**
     * Label for this page's tab in the shared Supe tab bar. Defaults to
     * menuTitle(); override when the sidebar label doesn't read well as a
     * tab (e.g. a top-level page whose menu title is the brand name).
     */
    public function tabTitle(): string {
        return $this->menuTitle();
    }

    // -------------------------------------------------------------------------
    // Output
    // -------------------------------------------------------------------------

    /**
     * Render the shared Supe page shell around renderContent().
     */
    public function render(): void {
        if ( ! current_user_can( $this->capability() ) ) {
            return;
        }

        echo '<div class="wrap supe-admin">';
        $this->renderHeader();

        // WordPress moves admin notices to just after this marker, keeping
        // them out of the branded header.
        echo '<hr class="wp-header-end">';

        echo '<div class="supe-admin__content">';
        $this->renderContent();
        echo '</div></div>';
    }

    /**
     * The page's own content, rendered inside the shared shell.
     */
    protected function renderContent(): void {}

    protected function renderHeader(): void {
        $tabs = $this->navigation();

        echo '<header class="supe-admin__hero' . ( count( $tabs ) > 1 ? ' supe-admin__hero--tabbed' : '' ) . '">';

        echo '<div class="supe-admin__brand">';
        echo AdminUi::logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG.
        echo '<span class="supe-admin__wordmark">Supe</span>';

        if ( $this->version() !== '' ) {
            echo '<span class="supe-admin__version">v' . esc_html( $this->version() ) . '</span>';
        }

        echo '</div>';

        echo '<h1 class="supe-admin__title">' . esc_html( $this->pageTitle() ) . '</h1>';

        if ( $this->description() !== '' ) {
            echo '<p class="supe-admin__description">' . esc_html( $this->description() ) . '</p>';
        }

        // A single page has nothing to switch between.
        if ( count( $tabs ) > 1 ) {
            echo '<nav class="supe-admin__tabs" aria-label="' . esc_attr__( 'Supe pages', 'supe' ) . '">';

            foreach ( $tabs as $tab ) {
                printf(
                    '<a class="supe-admin__tab%s" href="%s"%s>%s</a>',
                    $tab['current'] ? ' is-current' : '',
                    esc_url( $tab['url'] ),
                    $tab['current'] ? ' aria-current="page"' : '',
                    esc_html( $tab['title'] )
                );
            }

            echo '</nav>';
        }

        echo '</header>';
    }

    /**
     * Tabs for every page in this page's Supe menu (its top-level parent plus
     * that parent's submenu pages) that the current user can access.
     *
     * @return list<array{slug: string, title: string, url: string, current: bool}>
     */
    protected function navigation(): array {
        $registry = $this->menuRegistry();

        if ( $registry === null ) {
            return [];
        }

        $tabs = [];

        foreach ( $registry->familyOf( static::slug() ) as $slug => $page ) {
            if ( ! current_user_can( $page->capability() ) ) {
                continue;
            }

            $tabs[] = [
                'slug'    => $slug,
                'title'   => $page instanceof self ? $page->tabTitle() : $page->menuTitle(),
                'url'     => admin_url( 'admin.php?page=' . rawurlencode( $slug ) ),
                'current' => $slug === static::slug(),
            ];
        }

        return $tabs;
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
     * Intended to be called from renderContent().
     */
    protected function view( string $templatePath, array $data = [] ): void {
        if ( ! file_exists( $templatePath ) ) {
            return;
        }

        extract( $data, EXTR_SKIP );
        require $templatePath;
    }

    /**
     * The shared AdminMenuRegistry, or null when it isn't bound (e.g. a page
     * constructed outside Core, as in unit tests).
     */
    private function menuRegistry(): ?AdminMenuRegistry {
        try {
            $registry = $this->container->make( AdminMenuRegistry::class );
        } catch ( ContainerException | \ArgumentCountError ) {
            // Unbound: the container's auto-wiring can't construct it.
            return null;
        }

        return $registry instanceof AdminMenuRegistry ? $registry : null;
    }
}
