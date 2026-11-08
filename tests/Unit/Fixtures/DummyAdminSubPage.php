<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit\Fixtures;

use Supe\Core\Support\AbstractAdminPage;

final class DummyAdminSubPage extends AbstractAdminPage {

    public static function slug(): string {
        return 'acme-widget-advanced';
    }

    public function pageTitle(): string {
        return 'Advanced';
    }

    public function parentSlug(): ?string {
        return 'acme-widget-settings';
    }

    public function render(): void {}
}
