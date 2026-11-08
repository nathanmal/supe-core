<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Brain\Monkey\Actions;
use Supe\Core\Core;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class CoreTest extends TestCase {

    protected function tear_down(): void {
        // Core::getInstance() is a process-wide singleton; reset it between
        // tests so each test gets an isolated registry/container.
        $property = new \ReflectionProperty( Core::class, 'instance' );
        $property->setAccessible( true );
        $property->setValue( null, null );

        parent::tear_down();
    }

    public function test_get_instance_always_returns_the_same_object(): void {
        self::assertSame( Core::getInstance(), Core::getInstance() );
    }

    public function test_it_registers_the_shared_dashboard_page_on_construction(): void {
        self::assertTrue( Core::getInstance()->getAdminMenuRegistry()->hasPage( 'supe' ) );
    }

    public function test_boot_fires_its_hooks_exactly_once(): void {
        Actions\expectDone( 'supe/before_boot' )->once();
        Actions\expectDone( 'supe/after_boot' )->once();

        $core = Core::getInstance();
        $core->boot();
        $core->boot(); // second call must be a no-op

        self::assertTrue( $core->isBooted() );
    }
}
