<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Feature;

use Supe\Core\Core;
use Yoast\WPTestUtils\WPIntegration\TestCase;

final class CoreBootTest extends TestCase {

    public function test_core_boots_inside_a_real_wordpress_request(): void {
        // tests/bootstrap-feature.php already called Core::boot() on
        // muplugins_loaded — this proves the whole stack came up for real,
        // with WordPress' actual hook system firing supe/before_boot and
        // supe/after_boot (no Brain Monkey faking involved here).
        self::assertTrue( Core::getInstance()->isBooted() );
        self::assertTrue( function_exists( 'add_action' ) );
    }
}
