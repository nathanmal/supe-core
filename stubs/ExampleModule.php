<?php

declare(strict_types=1);

namespace AcmeCorp\GalleryModule;

use Supe\Core\Support\AbstractModule;

/**
 * Example module: Acme Gallery
 *
 * Copy this file into your own module repository, rename the namespace,
 * and fill in boot() with your plugin logic.
 *
 * Your module's composer.json should declare:
 *
 *   "require": {
 *       "nathanmal/supe-core": "^1.0"
 *   }
 *
 * And register itself via a guarded add_action() call — the guard matters
 * because this file (if autoloaded via Composer's "files" key, e.g. from
 * bootstrap.php) runs the instant vendor/autoload.php is required, which
 * happens outside WordPress too (tests, WP-CLI, static analysis):
 *
 *   if ( function_exists( 'add_action' ) ) {
 *       add_action( 'supe/before_boot', function ( $registry ) {
 *           $registry->register( \AcmeCorp\GalleryModule\GalleryModule::class );
 *       } );
 *   }
 *
 * Place that registration call in your plugin's main file or a file
 * autoloaded via Composer's "files" key (conventionally named bootstrap.php).
 */
final class GalleryModule extends AbstractModule {

    // -------------------------------------------------------------------------
    // Identity — these two methods determine version-race deduplication.
    // -------------------------------------------------------------------------

    public static function id(): string {
        return 'acme/gallery'; // unique across all modules; use vendor/name format
    }

    public static function version(): string {
        return '1.0.0'; // bump this with every release
    }

    // -------------------------------------------------------------------------
    // Boot — called once, only for the winning version.
    // -------------------------------------------------------------------------

    public function boot(): void {
        // 1. Bind services into the shared container.
        $this->singleton( 'acme.gallery.repository', fn ( $c ) => new GalleryRepository() );

        // 2. Register WordPress hooks.
        $this->addAction( 'init',             [ $this, 'registerPostTypes' ] );
        $this->addAction( 'rest_api_init',    [ $this, 'registerRoutes' ] );
        $this->addAction( 'wp_enqueue_scripts', [ $this, 'enqueueAssets' ] );

        // 3. Register admin pages (see ExampleAdminPage.php). Added to
        //    WordPress on `admin_menu`, so this is safe to call any time
        //    during boot().
        $this->addAdminPage( GallerySettingsPage::class );
    }

    // -------------------------------------------------------------------------
    // Callbacks
    // -------------------------------------------------------------------------

    public function registerPostTypes(): void {
        register_post_type( 'acme_gallery', [
            'label'       => __( 'Galleries', 'acme-gallery' ),
            'public'      => true,
            'has_archive' => true,
            'supports'    => [ 'title', 'editor', 'thumbnail' ],
            'show_in_rest' => true,
        ] );
    }

    public function registerRoutes(): void {
        register_rest_route( 'acme/gallery/v1', '/items', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'handleGetItems' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function handleGetItems( \WP_REST_Request $request ): \WP_REST_Response {
        // $repo = $this->make( 'acme.gallery.repository' );
        return new \WP_REST_Response( [ 'items' => [] ], 200 );
    }

    public function enqueueAssets(): void {
        // wp_enqueue_style( 'acme-gallery', plugin_dir_url( __FILE__ ) . '../assets/gallery.css' );
    }
}
