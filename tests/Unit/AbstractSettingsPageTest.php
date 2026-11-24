<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Brain\Monkey\Functions;
use Supe\Core\Support\Container;
use Supe\Core\Support\SafeHtml;
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

    public function test_child_sections_render_inside_their_parent_card_with_header_actions(): void {
        $GLOBALS['wp_settings_sections'] = [
            'acme-settings' => [
                'convert' => [
                    'id'               => 'convert',
                    'title'            => 'Conversion',
                    'callback'         => null,
                    'supe_description' => 'Modern formats.',
                    'supe_icon'        => 'dashicons-images-alt2',
                    'supe_action'      => static fn (): SafeHtml => new SafeHtml( '<i>MASTER</i>' ),
                ],
                'webp'    => [ 'id' => 'webp', 'title' => 'WebP', 'callback' => null, 'supe_parent' => 'convert', 'supe_action' => new SafeHtml( '<i>WEBP</i>' ) ],
                'avif'    => [ 'id' => 'avif', 'title' => 'AVIF', 'callback' => null, 'supe_parent' => 'convert', 'supe_collapsible' => 'open' ],
                'orphan'  => [ 'id' => 'orphan', 'title' => 'Orphan', 'callback' => null, 'supe_parent' => 'missing' ],
            ],
        ];
        $GLOBALS['wp_settings_fields'] = [ 'acme-settings' => [ 'webp' => [ 'quality' => [] ] ] ];

        Functions\when( 'settings_errors' )->justReturn( null );
        Functions\when( 'settings_fields' )->justReturn( null );
        Functions\when( 'submit_button' )->justReturn( null );
        Functions\expect( 'do_settings_fields' )->once()->with( 'acme-settings', 'webp' );

        $output = $this->render();

        // Two cards: "Conversion" and the orphan (its parent doesn't exist, so it isn't hidden).
        self::assertSame( 2, substr_count( $output, '<section class="supe-admin__card">' ) );
        self::assertSame( 1, substr_count( $output, '<section class="supe-admin__subsection">' ) );
        self::assertSame( 1, substr_count( $output, '<details class="supe-admin__subsection supe-admin__subsection--collapsible" open>' ) );
        self::assertStringContainsString( 'Modern formats.', $output );
        self::assertStringContainsString( 'dashicons-images-alt2', $output );
        self::assertStringContainsString( '<div class="supe-admin__card-action"><i>MASTER</i></div>', $output );
        self::assertStringContainsString( '<div class="supe-admin__subsection-action"><i>WEBP</i></div>', $output );
        self::assertStringContainsString( '<h2 class="supe-admin__card-title">Orphan</h2>', $output );

        // Children come after the parent's header, in registration order, inside its card.
        $convert = strpos( $output, 'Conversion' );
        $webp    = strpos( $output, '>WebP<' );
        $avif    = strpos( $output, '>AVIF<' );
        $orphan  = strpos( $output, 'Orphan' );

        self::assertTrue( $convert < $webp && $webp < $avif && $avif < $orphan );
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
