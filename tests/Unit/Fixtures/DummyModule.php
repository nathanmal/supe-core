<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit\Fixtures;

use Supe\Core\Contracts\ModuleInterface;
use Supe\Core\Support\Container;

abstract class DummyModule implements ModuleInterface {

    public function __construct( private readonly Container $container ) {}

    public static function id(): string {
        return 'acme/widget';
    }

    public function boot(): void {
        static::$bootCount++;
    }
}
