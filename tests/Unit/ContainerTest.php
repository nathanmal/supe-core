<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Supe\Core\Exceptions\ContainerException;
use Supe\Core\Support\Container;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class ContainerTest extends TestCase {

    public function test_bind_produces_a_new_instance_every_call(): void {
        $container = new Container();
        $container->bind( 'service', fn () => new \stdClass() );

        self::assertNotSame( $container->make( 'service' ), $container->make( 'service' ) );
    }

    public function test_singleton_returns_the_same_instance(): void {
        $container = new Container();
        $container->singleton( 'service', fn () => new \stdClass() );

        self::assertSame( $container->make( 'service' ), $container->make( 'service' ) );
    }

    public function test_instance_is_returned_verbatim(): void {
        $container = new Container();
        $object    = new \stdClass();
        $container->instance( 'service', $object );

        self::assertSame( $object, $container->make( 'service' ) );
    }

    public function test_make_auto_wires_a_concrete_class_with_no_bindings(): void {
        $container = new Container();

        self::assertInstanceOf( \stdClass::class, $container->make( \stdClass::class ) );
    }

    public function test_make_throws_for_an_unresolvable_abstract(): void {
        $container = new Container();

        $this->expectException( ContainerException::class );
        $container->make( 'does-not-exist' );
    }

    public function test_has_reflects_registered_bindings(): void {
        $container = new Container();

        self::assertFalse( $container->has( 'service' ) );
        $container->bind( 'service', fn () => new \stdClass() );
        self::assertTrue( $container->has( 'service' ) );
    }
}
