<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Supe\Core\Support\Container;
use Supe\Core\Tests\Unit\Fixtures\DummyAdminPage;
use Supe\Core\Tests\Unit\Fixtures\DummyAdminSubPage;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class AbstractAdminPageTest extends TestCase {

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

    public function test_a_subpage_can_override_parent_slug(): void {
        $page = new DummyAdminSubPage( new Container() );

        self::assertSame( 'acme-widget-settings', $page->parentSlug() );
    }
}
