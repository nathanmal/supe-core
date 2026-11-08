<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Supe\Core\Admin\AdminMenuRegistry;
use Supe\Core\Exceptions\InvalidAdminPageException;
use Supe\Core\Support\Container;
use Supe\Core\Tests\Unit\Fixtures\DummyAdminPage;
use Supe\Core\Tests\Unit\Fixtures\DummyAdminSubPage;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class AdminMenuRegistryTest extends TestCase {

    public function test_add_page_rejects_a_class_that_does_not_exist(): void {
        $registry = new AdminMenuRegistry( new Container() );

        $this->expectException( InvalidAdminPageException::class );
        $registry->addPage( 'Supe\\Core\\Tests\\Unit\\Fixtures\\DoesNotExist' );
    }

    public function test_add_page_rejects_a_class_that_does_not_implement_admin_page_interface(): void {
        $registry = new AdminMenuRegistry( new Container() );

        $this->expectException( InvalidAdminPageException::class );
        $registry->addPage( \stdClass::class );
    }

    public function test_add_page_registers_by_slug(): void {
        Actions\expectDone( 'supe/admin_page_registered' )
            ->once()
            ->with( 'acme-widget-settings', DummyAdminPage::class );

        $registry = new AdminMenuRegistry( new Container() );
        $registry->addPage( DummyAdminPage::class );

        self::assertTrue( $registry->hasPage( 'acme-widget-settings' ) );
        self::assertFalse( $registry->hasPage( 'does-not-exist' ) );
        self::assertSame(
            [ 'acme-widget-settings' => DummyAdminPage::class ],
            $registry->getPages()
        );
    }

    public function test_register_menus_adds_a_top_level_page_via_add_menu_page(): void {
        Functions\expect( 'add_menu_page' )
            ->once()
            ->with(
                'Widget Settings',
                'Widget Settings',
                'manage_options',
                'acme-widget-settings',
                \Mockery::type( 'array' ),
                '',
                null
            );

        Actions\expectDone( 'supe/admin_page_added' )
            ->once()
            ->with( 'acme-widget-settings', \Mockery::type( DummyAdminPage::class ) );

        $registry = new AdminMenuRegistry( new Container() );
        $registry->addPage( DummyAdminPage::class );
        $registry->registerMenus();
    }

    public function test_register_menus_adds_submenu_pages_via_add_submenu_page_after_their_parent(): void {
        $calls = [];

        Functions\when( 'add_menu_page' )->alias( function () use ( &$calls ) {
            $calls[] = 'add_menu_page';
            return 'acme-widget-settings';
        } );

        Functions\when( 'add_submenu_page' )->alias( function () use ( &$calls ) {
            $calls[] = 'add_submenu_page';
            return 'acme-widget-advanced';
        } );

        $registry = new AdminMenuRegistry( new Container() );

        // Registered out of parent/child order on purpose — the registry
        // must still add the top-level page to WordPress first.
        $registry->addPage( DummyAdminSubPage::class );
        $registry->addPage( DummyAdminPage::class );

        $registry->registerMenus();

        self::assertSame( [ 'add_menu_page', 'add_submenu_page' ], $calls );
    }
}
