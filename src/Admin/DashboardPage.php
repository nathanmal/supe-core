<?php

declare(strict_types=1);

namespace Supe\Core\Admin;

use Supe\Core\Core;
use Supe\Core\Support\AbstractAdminPage;
use Supe\Core\Support\AdminUi;

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

    public function tabTitle(): string {
        return __( 'Overview', 'supe' );
    }

    public function description(): string {
        return __( 'Every Supe module running on this site, in one place.', 'supe' );
    }

    public function version(): string {
        return Core::VERSION;
    }

    public function icon(): string {
        return 'dashicons-superhero-alt';
    }

    public function position(): ?int {
        return 80;
    }

    protected function renderContent(): void {
        $core   = Core::getInstance();
        $loaded = $core->getRegistry()->getLoadedModules();

        AdminUi::stats( [
            [
                'label' => __( 'Modules loaded', 'supe' ),
                'value' => count( $loaded ),
                'icon'  => 'dashicons-screenoptions',
            ],
            [
                'label' => __( 'Admin pages', 'supe' ),
                'value' => count( $core->getAdminMenuRegistry()->getPages() ),
                'icon'  => 'dashicons-admin-page',
            ],
            [
                'label' => __( 'Supe Core', 'supe' ),
                'value' => 'v' . Core::VERSION,
                'icon'  => 'dashicons-superhero-alt',
            ],
        ] );

        AdminUi::cardStart(
            __( 'Loaded Modules', 'supe' ),
            __( 'The winning version of each registered module, as booted on this request.', 'supe' ),
            'dashicons-screenoptions'
        );

        if ( empty( $loaded ) ) {
            AdminUi::emptyState(
                __( 'No modules loaded', 'supe' ),
                __( 'No Supe modules are currently loaded.', 'supe' ),
                'dashicons-screenoptions'
            );
        } else {
            $rows = [];

            foreach ( $loaded as $id => $version ) {
                $rows[] = [
                    'module'  => AdminUi::code( (string) $id ),
                    'version' => AdminUi::badge( 'v' . $version, 'accent' ),
                    'status'  => AdminUi::badge( __( 'Active', 'supe' ), 'success' ),
                ];
            }

            AdminUi::table(
                [
                    'module'  => __( 'Module', 'supe' ),
                    'version' => __( 'Version', 'supe' ),
                    'status'  => __( 'Status', 'supe' ),
                ],
                $rows
            );
        }

        AdminUi::cardEnd();
    }
}
