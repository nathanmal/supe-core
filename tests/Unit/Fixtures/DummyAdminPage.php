<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit\Fixtures;

use Supe\Core\Support\AbstractAdminPage;

final class DummyAdminPage extends AbstractAdminPage {

    public static int $renderCount = 0;

    public static function slug(): string {
        return 'acme-widget-settings';
    }

    public function pageTitle(): string {
        return 'Widget Settings';
    }

    public function render(): void {
        self::$renderCount++;
    }
}
