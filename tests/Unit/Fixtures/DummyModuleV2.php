<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit\Fixtures;

final class DummyModuleV2 extends DummyModule {

    public static int $bootCount = 0;

    public static function version(): string {
        return '2.0.0';
    }
}
