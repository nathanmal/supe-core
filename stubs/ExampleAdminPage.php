<?php

declare(strict_types=1);

namespace AcmeCorp\GalleryModule;

use Supe\Core\Admin\DashboardPage;
use Supe\Core\Support\AbstractAdminPage;
use Supe\Core\Support\AdminUi;

/**
 * Example admin page: Acme Gallery Settings
 *
 * Copy this file into your own module repository alongside your module
 * class, rename the namespace, and register it from your module's boot():
 *
 *   $this->addAdminPage( GallerySettingsPage::class );
 *
 * Only slug() and pageTitle() are required. Every other method
 * (menuTitle, capability, parentSlug, icon, position, description, version,
 * tabTitle) has a sensible default from AbstractAdminPage that you can
 * override as needed.
 *
 * Implement renderContent(), not render(): render() wraps it in the shared
 * Supe shell (branded header + tab bar). For a Settings API page, extend
 * AbstractSettingsPage instead and implement optionGroup().
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

    public function description(): string {
        return __( 'Choose how galleries look and behave across the site.', 'acme-gallery' );
    }

    public function version(): string {
        return GalleryModule::version(); // shown as a pill in the header
    }

    // -------------------------------------------------------------------------
    // Output
    // -------------------------------------------------------------------------

    protected function renderContent(): void {
        // $repo = $this->make( 'acme.gallery.repository' );

        AdminUi::cardStart( __( 'Albums', 'acme-gallery' ), __( 'Every album on this site.', 'acme-gallery' ), 'dashicons-format-gallery' );
        AdminUi::emptyState( __( 'No albums yet', 'acme-gallery' ), __( 'Albums you create will be listed here.', 'acme-gallery' ) );
        AdminUi::cardEnd();

        // Or, for anything beyond trivial markup, render a template file:
        // $this->view( __DIR__ . '/views/settings.php', [ 'repo' => $repo ] );
    }
}
