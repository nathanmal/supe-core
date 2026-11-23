<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit\Fixtures;

use Supe\Core\Support\AbstractSettingsPage;

final class DummySettingsPage extends AbstractSettingsPage {

    public static function slug(): string {
        return 'acme-settings';
    }

    public function pageTitle(): string {
        return 'Acme Settings';
    }

    public function parentSlug(): ?string {
        return 'supe';
    }

    public function optionGroup(): string {
        return 'acme_settings';
    }
}
