<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit\Fixtures;

use Supe\Core\Support\AbstractAdminPage;

/**
 * Submenu of DummyShellPage with its own capability, for tab-bar tests.
 */
final class DummyShellSubPage extends AbstractAdminPage {

    public static function slug(): string {
        return 'acme-shell-child';
    }

    public function pageTitle(): string {
        return 'Acme Child Page';
    }

    public function menuTitle(): string {
        return 'Child';
    }

    public function capability(): string {
        return 'edit_posts';
    }

    public function parentSlug(): ?string {
        return DummyShellPage::slug();
    }
}
