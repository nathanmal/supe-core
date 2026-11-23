<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Brain\Monkey\Functions;
use Supe\Core\Support\PackageAssets;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class PackageAssetsTest extends TestCase {

    private string $tmp;

    protected function set_up(): void {
        parent::set_up();

        Functions\when( 'wp_normalize_path' )->alias( static fn ( string $p ): string => str_replace( '\\', '/', $p ) );

        // A real package dir, and a "plugin vendor" dir that symlinks to it,
        // mirroring a Composer path-repository install.
        $this->tmp = realpath( sys_get_temp_dir() ) . '/supe-package-assets-' . uniqid( '', true );
        mkdir( $this->tmp . '/real/acme-pkg/assets', 0777, true );
        mkdir( $this->tmp . '/plugin/vendor/acme', 0777, true );
        file_put_contents( $this->tmp . '/real/acme-pkg/assets/app.css', 'body{}' );
        symlink( $this->tmp . '/real/acme-pkg', $this->tmp . '/plugin/vendor/acme/acme-pkg' );

        $GLOBALS['wp_plugin_paths'] = [];
    }

    protected function tear_down(): void {
        unlink( $this->tmp . '/plugin/vendor/acme/acme-pkg' );
        unlink( $this->tmp . '/real/acme-pkg/assets/app.css' );
        rmdir( $this->tmp . '/real/acme-pkg/assets' );
        rmdir( $this->tmp . '/real/acme-pkg' );
        rmdir( $this->tmp . '/real' );
        rmdir( $this->tmp . '/plugin/vendor/acme' );
        rmdir( $this->tmp . '/plugin/vendor' );
        rmdir( $this->tmp . '/plugin' );
        rmdir( $this->tmp );
        unset( $GLOBALS['wp_plugin_paths'] );

        parent::tear_down();
    }

    public function test_register_maps_a_symlinked_install_path_to_the_real_path(): void {
        // Composer reports install paths with ../ segments, relative to vendor/composer/.
        $installPath = $this->tmp . '/plugin/vendor/composer/../acme/acme-pkg';

        ( new PackageAssets( 'acme/pkg', $this->tmp . '/real/acme-pkg', $installPath ) )->register();

        self::assertSame(
            [ $this->tmp . '/plugin/vendor/acme/acme-pkg' => $this->tmp . '/real/acme-pkg' ],
            $GLOBALS['wp_plugin_paths']
        );
    }

    public function test_register_is_a_no_op_when_the_package_is_not_symlinked(): void {
        $root = $this->tmp . '/real/acme-pkg';

        ( new PackageAssets( 'acme/pkg', $root, $root ) )->register();

        self::assertSame( [], $GLOBALS['wp_plugin_paths'] );
    }

    public function test_register_is_a_no_op_for_a_package_composer_does_not_know(): void {
        ( new PackageAssets( 'acme/not-installed', $this->tmp . '/real/acme-pkg' ) )->register();

        self::assertSame( [], $GLOBALS['wp_plugin_paths'] );
    }

    public function test_url_registers_the_mapping_then_resolves_against_the_package_root(): void {
        $root    = $this->tmp . '/real/acme-pkg';
        $package = new PackageAssets( 'acme/pkg', $root, $this->tmp . '/plugin/vendor/acme/acme-pkg' );

        Functions\expect( 'plugins_url' )
            ->once()
            ->with( 'assets/app.css', $root . '/composer.json' )
            ->andReturnUsing( function (): string {
                // The mapping must already be in place when WordPress resolves the URL.
                self::assertArrayHasKey( $this->tmp . '/plugin/vendor/acme/acme-pkg', $GLOBALS['wp_plugin_paths'] );

                return 'https://example.test/wp-content/plugins/host/vendor/acme/acme-pkg/assets/app.css';
            } );

        self::assertSame(
            'https://example.test/wp-content/plugins/host/vendor/acme/acme-pkg/assets/app.css',
            $package->url( '/assets/app.css' )
        );
    }

    public function test_path_and_version(): void {
        $package = new PackageAssets( 'acme/pkg', $this->tmp . '/real/acme-pkg/' );

        self::assertSame( $this->tmp . '/real/acme-pkg/assets/app.css', $package->path( 'assets/app.css' ) );
        self::assertSame( (string) filemtime( $this->tmp . '/real/acme-pkg/assets/app.css' ), $package->version( 'assets/app.css' ) );
        self::assertNull( $package->version( 'assets/missing.css' ) );
    }

    public function test_collapse_dot_segments_resolves_lexically(): void {
        self::assertSame( '/a/c', PackageAssets::collapseDotSegments( '/a/b/../c' ) );
        self::assertSame( '/a/b/c', PackageAssets::collapseDotSegments( '/a/./b//c/' ) );
        self::assertSame( 'C:/x', PackageAssets::collapseDotSegments( 'C:/w/../x' ) );
    }
}
