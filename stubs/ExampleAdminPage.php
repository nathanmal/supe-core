<?php

declare(strict_types=1);

namespace AcmeCorp\GalleryModule;

use Supe\Core\Admin\DashboardPage;
use Supe\Core\Support\AbstractAdminPage;

/**
 * Example admin page: Acme Gallery Settings
 *
 * Copy this file into your own module repository alongside your module
 * class, rename the namespace, and register it from your module's boot():
 *
 *   $this->addAdminPage( GallerySettingsPage::class );
 *
 * Only slug(), pageTitle(), and render() are required — every other method
 * (menuTitle, capability, parentSlug, icon, position) has a sensible
 * default from AbstractAdminPage that you can override as needed.
 */
final class GallerySettingsPage extends AbstractAdminPage {

    public static function slug(): string {
        return 'acme-gallery-settings'; // unique across the whole wp-admin menu
    }

    public function pageTitle(): string {
        return __( 'Gallery Settings', 'acme-gallery' );
    }

    // -------------------------------------------------------------------------
    // Optional overrides — shown here with the values AbstractAdminPage
    // would already supply, purely for illustration.
    // -------------------------------------------------------------------------

    public function parentSlug(): ?string {
        return DashboardPage::slug(); // attach under the shared "Supe" menu as a submenu
    }

    public function capability(): string {
        return 'manage_options';
    }

    // -------------------------------------------------------------------------
    // Output
    // -------------------------------------------------------------------------

    public function render(): void {
        // $repo = $this->make( 'acme.gallery.repository' );

        echo '<div class="wrap"><h1>' . esc_html( $this->pageTitle() ) . '</h1></div>';

        // Or, for anything beyond trivial markup, render a template file:
        // $this->view( __DIR__ . '/views/settings.php', [ 'repo' => $repo ] );
    }
}
