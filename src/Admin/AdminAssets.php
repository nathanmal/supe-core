<?php

declare(strict_types=1);

namespace Supe\Core\Admin;

use Supe\Core\Support\PackageAssets;

/**
 * AdminAssets
 *
 * Enqueues the shared Supe admin stylesheet (assets/admin.css) and script
 * (assets/admin.js) on every screen belonging to a page in the
 * AdminMenuRegistry, and nowhere else in wp-admin. The file is served from supe-core's Composer install path
 * inside the host plugin (see PackageAssets).
 */
final class AdminAssets {

    public const HANDLE = 'supe-admin';

    public const STYLESHEET = 'assets/admin.css';

    public const SCRIPT = 'assets/admin.js';

    private AdminMenuRegistry $registry;
    private PackageAssets $package;

    public function __construct( AdminMenuRegistry $registry, PackageAssets $package ) {
        $this->registry = $registry;
        $this->package  = $package;
    }

    /**
     * Hooked onto `admin_enqueue_scripts` by Core.
     */
    public function enqueue(): void {
        if ( ! $this->isSupeScreen() ) {
            return;
        }

        wp_enqueue_style(
            self::HANDLE,
            $this->package->url( self::STYLESHEET ),
            [ 'dashicons' ],
            $this->package->version( self::STYLESHEET )
        );

        wp_enqueue_script(
            self::HANDLE,
            $this->package->url( self::SCRIPT ),
            [],
            $this->package->version( self::SCRIPT ),
            [ 'in_footer' => true, 'strategy' => 'defer' ]
        );
    }

    /**
     * Whether the current admin screen is one of the registered Supe pages.
     */
    public function isSupeScreen(): bool {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing check.
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

        return $page !== '' && $this->registry->hasPage( $page );
    }
}
