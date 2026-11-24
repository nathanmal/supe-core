<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Brain\Monkey\Functions;
use Supe\Core\Admin\AdminAssets;
use Supe\Core\Admin\AdminMenuRegistry;
use Supe\Core\Support\Container;
use Supe\Core\Support\PackageAssets;
use Supe\Core\Tests\Unit\Fixtures\DummyShellPage;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class AdminAssetsTest extends TestCase {

    protected function set_up(): void {
        parent::set_up();

        Functions\when( 'wp_unslash' )->returnArg();
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'wp_normalize_path' )->returnArg();
    }

    protected function tear_down(): void {
        unset( $_GET['page'] );

        parent::tear_down();
    }

    public function test_does_nothing_outside_supe_screens(): void {
        $_GET['page'] = 'some-other-plugin';

        Functions\expect( 'wp_enqueue_style' )->never();
        Functions\expect( 'wp_enqueue_script' )->never();

        $this->assets()->enqueue();
    }

    public function test_does_nothing_without_a_page_parameter(): void {
        Functions\expect( 'wp_enqueue_style' )->never();

        $this->assets()->enqueue();
    }

    public function test_enqueues_the_shared_stylesheet_and_script_as_files_on_a_registered_supe_page(): void {
        $_GET['page'] = 'acme-shell';

        $root = dirname( __DIR__, 2 ); // supe-core's own root, which ships assets/admin.css

        Functions\expect( 'plugins_url' )
            ->twice()
            ->andReturnUsing( static fn ( string $path, string $file ): string => $file === $root . '/composer.json'
                ? 'https://example.test/wp-content/plugins/supe/vendor/nathanmal/supe-core/' . $path
                : 'wrong-root' );

        Functions\expect( 'wp_enqueue_style' )
            ->once()
            ->with(
                AdminAssets::HANDLE,
                'https://example.test/wp-content/plugins/supe/vendor/nathanmal/supe-core/assets/admin.css',
                [ 'dashicons' ],
                (string) filemtime( $root . '/assets/admin.css' )
            );

        Functions\expect( 'wp_enqueue_script' )
            ->once()
            ->with(
                AdminAssets::HANDLE,
                'https://example.test/wp-content/plugins/supe/vendor/nathanmal/supe-core/assets/admin.js',
                [],
                (string) filemtime( $root . '/assets/admin.js' ),
                [ 'in_footer' => true, 'strategy' => 'defer' ]
            );

        // Same path for install and real dir: no symlink mapping needed.
        $this->assets( new PackageAssets( 'nathanmal/supe-core', $root, $root ) )->enqueue();
    }

    private function assets( ?PackageAssets $package = null ): AdminAssets {
        $registry = new AdminMenuRegistry( new Container() );
        $registry->addPage( DummyShellPage::class );

        return new AdminAssets( $registry, $package ?? new PackageAssets( 'nathanmal/supe-core', dirname( __DIR__, 2 ) ) );
    }
}
