<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit\Fixtures;

use Supe\Core\Support\AbstractAdminPage;

/**
 * Top-level page that uses the shared shell (implements renderContent(),
 * not render()).
 */
final class DummyShellPage extends AbstractAdminPage {

    public static function slug(): string {
        return 'acme-shell';
    }

    public function pageTitle(): string {
        return 'Acme <Shell>';
    }

    public function description(): string {
        return 'Everything Acme.';
    }

    public function version(): string {
        return '1.2.3';
    }

    public function tabTitle(): string {
        return 'Overview';
    }

    protected function renderContent(): void {
        echo '<p>BODY</p>';
    }
}
