<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Supe\Core\Admin\DashboardPage;
use Supe\Core\Core;
use Supe\Core\Support\Container;
use Supe\Core\Tests\Unit\Fixtures\DummyModuleV2;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class DashboardPageTest extends TestCase {

    protected function tear_down(): void {
        // Core::getInstance() is a process-wide singleton; reset it between
        // tests so each test gets an isolated registry/container.
        $property = new \ReflectionProperty( Core::class, 'instance' );
        $property->setAccessible( true );
        $property->setValue( null, null );

        parent::tear_down();
    }

    public function test_page_metadata(): void {
        $page = new DashboardPage( new Container() );

        self::assertSame( 'supe', DashboardPage::slug() );
        self::assertNull( $page->parentSlug() );
        self::assertSame( 'dashicons-superhero-alt', $page->icon() );
    }

    public function test_render_lists_loaded_modules(): void {
        Functions\stubs( [ '__', 'esc_html', 'esc_html__' ] );

        DummyModuleV2::$bootCount = 0;

        Actions\expectDone( 'supe/before_boot' )->once()->whenHappen(
            static function ( $registry ): void {
                $registry->register( DummyModuleV2::class );
            }
        );

        $core = Core::getInstance();
        $core->boot();

        $page = new DashboardPage( $core->getContainer() );

        ob_start();
        $page->render();
        $output = ob_get_clean();

        self::assertStringContainsString( 'acme/widget', $output );
        self::assertStringContainsString( '2.0.0', $output );
    }

    public function test_render_shows_a_placeholder_when_nothing_is_loaded(): void {
        Functions\stubs( [ '__', 'esc_html', 'esc_html__' ] );

        $page = new DashboardPage( Core::getInstance()->getContainer() );

        ob_start();
        $page->render();
        $output = ob_get_clean();

        self::assertStringContainsString( 'No Supe modules are currently loaded.', $output );
    }
}
