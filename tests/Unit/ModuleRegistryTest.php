<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Supe\Core\Exceptions\InvalidModuleException;
use Supe\Core\Registry\ModuleRegistry;
use Supe\Core\Support\Container;
use Supe\Core\Tests\Unit\Fixtures\DummyModuleV1;
use Supe\Core\Tests\Unit\Fixtures\DummyModuleV2;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class ModuleRegistryTest extends TestCase {

    protected function set_up(): void {
        parent::set_up();

        DummyModuleV1::$bootCount = 0;
        DummyModuleV2::$bootCount = 0;
    }

    public function test_resolve_boots_only_the_highest_version_candidate(): void {
        $registry = new ModuleRegistry( new Container() );

        $registry->register( DummyModuleV1::class );
        $registry->register( DummyModuleV2::class );

        $registry->resolve();

        self::assertSame( [ 'acme/widget' => '2.0.0' ], $registry->getLoadedModules() );
        self::assertSame( 0, DummyModuleV1::$bootCount, 'the losing version should never boot' );
        self::assertSame( 1, DummyModuleV2::$bootCount, 'the winning version should boot exactly once' );
    }

    public function test_is_loaded_respects_a_minimum_version(): void {
        $registry = new ModuleRegistry( new Container() );
        $registry->register( DummyModuleV2::class );
        $registry->resolve();

        self::assertTrue( $registry->isLoaded( 'acme/widget' ) );
        self::assertTrue( $registry->isLoaded( 'acme/widget', '1.5.0' ) );
        self::assertFalse( $registry->isLoaded( 'acme/widget', '3.0.0' ) );
        self::assertFalse( $registry->isLoaded( 'acme/unregistered' ) );
    }

    public function test_register_rejects_a_class_that_does_not_exist(): void {
        $registry = new ModuleRegistry( new Container() );

        $this->expectException( InvalidModuleException::class );
        $registry->register( 'Supe\\Core\\Tests\\Unit\\Fixtures\\DoesNotExist' );
    }

    public function test_register_rejects_a_class_that_does_not_implement_module_interface(): void {
        $registry = new ModuleRegistry( new Container() );

        $this->expectException( InvalidModuleException::class );
        $registry->register( \stdClass::class );
    }
}
