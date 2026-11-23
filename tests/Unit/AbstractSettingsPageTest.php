<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Brain\Monkey\Functions;
use Supe\Core\Support\Container;
use Supe\Core\Tests\Unit\Fixtures\DummySettingsPage;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class AbstractSettingsPageTest extends TestCase {

    protected function set_up(): void {
        parent::set_up();

        $this->stubTranslationFunctions();
        $this->stubEscapeFunctions();
        Functions\when( 'current_user_can' )->justReturn( true );
    }

    protected function tear_down(): void {
        unset( $GLOBALS['wp_settings_sections'], $GLOBALS['wp_settings_fields'] );

        parent::tear_down();
    }

    public function test_shows_an_empty_state_when_no_sections_are_registered(): void {
        Functions\when( 'settings_errors' )->justReturn( null );
        Functions\expect( 'settings_fields' )->never();

        $output = $this->render();

        self::assertStringContainsString( 'Nothing to configure yet', $output );
        self::assertStringNotContainsString( '<form', $output );
    }

    public function test_renders_each_section_as_a_card_inside_one_form(): void {
        $GLOBALS['wp_settings_sections'] = [
            'acme-settings' => [
                'general'  => [
                    'id'       => 'general',
                    'title'    => 'General',
                    'callback' => static function (): void {
                        echo '<p>General intro</p>';
                    },
                ],
                'advanced' => [ 'id' => 'advanced', 'title' => 'Advanced', 'callback' => null ],
            ],
        ];
        $GLOBALS['wp_settings_fields'] = [ 'acme-settings' => [ 'general' => [ 'field' => [] ] ] ];

        Functions\when( 'settings_errors' )->justReturn( null );
        Functions\expect( 'settings_fields' )->once()->with( 'acme_settings' );
        Functions\expect( 'do_settings_fields' )->once()->with( 'acme-settings', 'general' );
        Functions\expect( 'submit_button' )->once()->with( null, 'primary', 'submit', false );

        $output = $this->render();

        self::assertSame( 1, substr_count( $output, '<form' ) );
        self::assertSame( 2, substr_count( $output, '<section class="supe-admin__card">' ) );
        self::assertStringContainsString( '<h2 class="supe-admin__card-title">General</h2>', $output );
        self::assertStringContainsString( '<p>General intro</p>', $output );
        self::assertStringContainsString( '<h2 class="supe-admin__card-title">Advanced</h2>', $output );
        self::assertSame( 1, substr_count( $output, 'form-table' ) ); // only the section with fields
        self::assertStringContainsString( 'supe-admin__savebar', $output );
    }

    public function test_prints_settings_errors_since_supe_pages_are_not_under_settings(): void {
        Functions\expect( 'settings_errors' )->once();

        $this->render();
    }

    private function render(): string {
        ob_start();
        ( new DummySettingsPage( new Container() ) )->render();

        return (string) ob_get_clean();
    }
}
