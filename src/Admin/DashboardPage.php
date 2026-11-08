<?php

declare(strict_types=1);

namespace Supe\Core\Admin;

use Supe\Core\Core;
use Supe\Core\Support\AbstractAdminPage;

/**
 * DashboardPage
 *
 * The shared top-level "Supe" admin menu. Every module page attaches to
 * this as a submenu by returning DashboardPage::slug() from parentSlug(),
 * so the whole ecosystem shows up under one navigation entry instead of
 * scattering itself across wp-admin.
 *
 * Registered by Core itself — not a module — since it must exist before
 * any module's own page tries to attach to it.
 */
final class DashboardPage extends AbstractAdminPage {

    public static function slug(): string {
        return 'supe';
    }

    public function pageTitle(): string {
        return __( 'Supe', 'supe' );
    }

    public function menuTitle(): string {
        return __( 'Supe', 'supe' );
    }

    public function icon(): string {
        return 'dashicons-superhero-alt';
    }

    public function position(): ?int {
        return 80;
    }

    public function render(): void {
        $loaded = Core::getInstance()->getRegistry()->getLoadedModules();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html( $this->pageTitle() ) . '</h1>';

        if ( empty( $loaded ) ) {
            echo '<p>' . esc_html__( 'No Supe modules are currently loaded.', 'supe' ) . '</p>';
            echo '</div>';
            return;
        }

        echo '<h2>' . esc_html__( 'Loaded Modules', 'supe' ) . '</h2>';
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__( 'Module', 'supe' ) . '</th>';
        echo '<th>' . esc_html__( 'Version', 'supe' ) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ( $loaded as $id => $version ) {
            echo '<tr><td><code>' . esc_html( $id ) . '</code></td><td>' . esc_html( $version ) . '</td></tr>';
        }

        echo '</tbody></table></div>';
    }
}
