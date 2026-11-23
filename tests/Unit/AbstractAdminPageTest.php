<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Brain\Monkey\Functions;
use Supe\Core\Admin\AdminMenuRegistry;
use Supe\Core\Support\Container;
use Supe\Core\Tests\Unit\Fixtures\DummyAdminPage;
use Supe\Core\Tests\Unit\Fixtures\DummyAdminSubPage;
use Supe\Core\Tests\Unit\Fixtures\DummyShellPage;
use Supe\Core\Tests\Unit\Fixtures\DummyShellSubPage;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class AbstractAdminPageTest extends TestCase {

    protected function set_up(): void {
        parent::set_up();

        $this->stubTranslationFunctions();
        $this->stubEscapeFunctions();
        Functions\when( 'admin_url' )->alias( static fn ( string $path ): string => 'https://example.test/wp-admin/' . $path );
    }

    public function test_default_menu_title_falls_back_to_page_title(): void {
        $page = new DummyAdminPage( new Container() );

        self::assertSame( 'Widget Settings', $page->menuTitle() );
    }

    public function test_defaults_for_capability_icon_position_and_parent_slug(): void {
        $page = new DummyAdminPage( new Container() );

        self::assertSame( 'manage_options', $page->capability() );
        self::assertSame( '', $page->icon() );
        self::assertNull( $page->position() );
        self::assertNull( $page->parentSlug() );
    }

    public function test_shell_defaults_for_description_version_and_tab_title(): void {
        $page = new DummyAdminPage( new Container() );

        self::assertSame( '', $page->description() );
        self::assertSame( '', $page->version() );
        self::assertSame( 'Widget Settings', $page->tabTitle() );
    }

    public function test_a_subpage_can_override_parent_slug(): void {
        $page = new DummyAdminSubPage( new Container() );

        self::assertSame( 'acme-widget-settings', $page->parentSlug() );
    }

    public function test_render_outputs_nothing_for_users_without_the_capability(): void {
        Functions\when( 'current_user_can' )->justReturn( false );

        self::assertSame( '', $this->render( new DummyShellPage( new Container() ) ) );
    }

    public function test_render_wraps_content_in_the_branded_shell(): void {
        Functions\when( 'current_user_can' )->justReturn( true );

        $output = $this->render( new DummyShellPage( new Container() ) );

        self::assertStringContainsString( '<div class="wrap supe-admin">', $output );
        self::assertStringContainsString( 'supe-admin__logo', $output );
        self::assertStringContainsString( '<h1 class="supe-admin__title">Acme <Shell></h1>', $output );
        self::assertStringContainsString( 'Everything Acme.', $output );
        self::assertStringContainsString( 'v1.2.3', $output );
        self::assertStringContainsString( '<hr class="wp-header-end">', $output );
        self::assertStringContainsString( '<div class="supe-admin__content"><p>BODY</p></div>', $output );
    }

    public function test_header_omits_empty_description_and_version(): void {
        Functions\when( 'current_user_can' )->justReturn( true );

        $output = $this->render( new DummyShellSubPage( new Container() ) );

        self::assertStringNotContainsString( 'supe-admin__description', $output );
        self::assertStringNotContainsString( 'supe-admin__version', $output );
    }

    public function test_no_tab_bar_when_the_menu_registry_is_unbound(): void {
        Functions\when( 'current_user_can' )->justReturn( true );

        $output = $this->render( new DummyShellPage( new Container() ) );

        self::assertStringNotContainsString( 'supe-admin__tabs', $output );
    }

    public function test_tab_bar_links_every_page_in_the_same_menu(): void {
        Functions\when( 'current_user_can' )->justReturn( true );

        $container = $this->containerWithPages( DummyShellPage::class, DummyShellSubPage::class );
        $output    = $this->render( new DummyShellSubPage( $container ) );

        self::assertStringContainsString( 'supe-admin__tabs', $output );
        self::assertStringContainsString(
            '<a class="supe-admin__tab" href="https://example.test/wp-admin/admin.php?page=acme-shell">Overview</a>',
            $output
        );
        self::assertStringContainsString(
            '<a class="supe-admin__tab is-current" href="https://example.test/wp-admin/admin.php?page=acme-shell-child" aria-current="page">Child</a>',
            $output
        );
        self::assertStringContainsString( 'supe-admin__hero--tabbed', $output );
    }

    public function test_tab_bar_hides_pages_the_user_cannot_access(): void {
        // Can see the top-level page, not the child (which needs edit_posts).
        Functions\when( 'current_user_can' )->alias( static fn ( string $cap ): bool => $cap !== 'edit_posts' );

        $container = $this->containerWithPages( DummyShellPage::class, DummyShellSubPage::class );
        $output    = $this->render( new DummyShellPage( $container ) );

        // Only one accessible page left — nothing to switch between.
        self::assertStringNotContainsString( 'supe-admin__tabs', $output );
        self::assertStringNotContainsString( 'Child', $output );
    }

    private function containerWithPages( string ...$pageClasses ): Container {
        $container = new Container();
        $registry  = new AdminMenuRegistry( $container );

        foreach ( $pageClasses as $pageClass ) {
            $registry->addPage( $pageClass );
        }

        $container->instance( AdminMenuRegistry::class, $registry );

        return $container;
    }

    private function render( object $page ): string {
        ob_start();
        $page->render();

        return (string) ob_get_clean();
    }
}
