<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Supe\Core\Support\AdminUi;
use Supe\Core\Support\SafeHtml;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class AdminUiTest extends TestCase {

    protected function set_up(): void {
        parent::set_up();

        // Real-ish escaping, so tests can prove helpers escape their input.
        \Brain\Monkey\Functions\when( 'esc_html' )->alias( static fn ( $s ) => htmlspecialchars( (string) $s, ENT_QUOTES ) );
        \Brain\Monkey\Functions\when( 'esc_attr' )->alias( static fn ( $s ) => htmlspecialchars( (string) $s, ENT_QUOTES ) );
    }

    public function test_badge_escapes_text_and_applies_the_tone(): void {
        $badge = AdminUi::badge( '<b>2.0</b>', 'success' );

        self::assertInstanceOf( SafeHtml::class, $badge );
        self::assertSame(
            '<span class="supe-admin__badge supe-admin__badge--success">&lt;b&gt;2.0&lt;/b&gt;</span>',
            (string) $badge
        );
    }

    public function test_badge_falls_back_to_neutral_for_an_unknown_tone(): void {
        self::assertStringContainsString( 'supe-admin__badge--neutral', (string) AdminUi::badge( 'x', 'nope" onclick="' ) );
    }

    public function test_table_escapes_plain_cells_but_passes_safe_html_through(): void {
        $output = $this->capture( static function (): void {
            AdminUi::table(
                [ 'name' => 'Name', 'version' => 'Version', 'missing' => 'Missing' ],
                [ [ 'name' => '<script>', 'version' => AdminUi::code( '1.0' ) ] ]
            );
        } );

        self::assertStringContainsString( '<th scope="col">Name</th>', $output );
        self::assertStringContainsString( '<td>&lt;script&gt;</td>', $output );
        self::assertStringContainsString( '<td><code class="supe-admin__code">1.0</code></td>', $output );
        self::assertStringContainsString( '<td></td>', $output ); // absent key renders an empty cell
    }

    public function test_card_wraps_its_body_and_only_prints_a_header_when_given_one(): void {
        $withHeader = $this->capture( static function (): void {
            AdminUi::cardStart( 'Uploads', 'How files are handled.', 'dashicons-upload' );
            echo 'BODY';
            AdminUi::cardEnd();
        } );

        self::assertStringContainsString( '<h2 class="supe-admin__card-title">Uploads</h2>', $withHeader );
        self::assertStringContainsString( 'How files are handled.', $withHeader );
        self::assertStringContainsString( 'dashicons dashicons-upload', $withHeader );
        self::assertStringContainsString( '<div class="supe-admin__card-body">BODY</div></section>', $withHeader );

        $bare = $this->capture( static function (): void {
            AdminUi::cardStart();
            AdminUi::cardEnd();
        } );

        self::assertStringNotContainsString( 'supe-admin__card-header', $bare );
    }

    public function test_card_header_shows_an_action_on_its_own(): void {
        $output = $this->capture( static function (): void {
            AdminUi::cardStart( '', '', '', AdminUi::badge( 'On' ) );
            AdminUi::cardEnd();
        } );

        self::assertStringContainsString( 'supe-admin__card-header', $output );
        self::assertStringContainsString( '<div class="supe-admin__card-action"><span class="supe-admin__badge', $output );
        self::assertStringNotContainsString( 'supe-admin__card-title', $output );
    }

    public function test_subsection_wraps_its_body_with_an_optional_header(): void {
        $output = $this->capture( static function (): void {
            AdminUi::subsectionStart( 'WebP', 'Smaller files.', AdminUi::badge( 'x' ) );
            echo 'BODY';
            AdminUi::subsectionEnd();
        } );

        self::assertStringContainsString( '<h3 class="supe-admin__subsection-title">WebP</h3>', $output );
        self::assertStringContainsString( '<p class="supe-admin__subsection-description">Smaller files.</p>', $output );
        self::assertStringContainsString( 'supe-admin__subsection-action', $output );
        self::assertStringContainsString( '<div class="supe-admin__subsection-body">BODY</div></section>', $output );

        $bare = $this->capture( static function (): void {
            AdminUi::subsectionStart();
            AdminUi::subsectionEnd();
        } );

        self::assertStringNotContainsString( 'supe-admin__subsection-header', $bare );
    }

    public function test_collapsible_subsection_uses_details_and_closes_with_its_own_tag(): void {
        $output = $this->capture( static function (): void {
            AdminUi::subsectionStart( 'Engines', 'What the server has.', AdminUi::badge( 'act' ), true );
            AdminUi::subsectionStart( 'Inner' ); // nested plain one closes correctly too
            echo 'BODY';
            AdminUi::subsectionEnd();
            AdminUi::subsectionEnd();
        } );

        self::assertStringStartsWith( '<details class="supe-admin__subsection supe-admin__subsection--collapsible">', $output );
        self::assertStringContainsString( '<summary class="supe-admin__subsection-header">', $output );
        self::assertStringContainsString( '<h3 class="supe-admin__subsection-title">Engines</h3>', $output );
        // The action can't live in <summary>, so it opens the body instead.
        self::assertStringContainsString( '</summary><div class="supe-admin__subsection-body"><div class="supe-admin__subsection-action">', $output );
        self::assertStringEndsWith( 'BODY</div></section></div></details>', $output );

        $open = $this->capture( static function (): void {
            AdminUi::subsectionStart( 'Open', '', null, true, true );
            AdminUi::subsectionEnd();
        } );

        self::assertStringContainsString( '--collapsible" open>', $open );
    }

    public function test_icon_button_escapes_and_keeps_its_label_for_screen_readers(): void {
        \Brain\Monkey\Functions\when( 'esc_url' )->alias( static fn ( $s ) => htmlspecialchars( (string) $s, ENT_QUOTES ) );

        $button = (string) AdminUi::iconButton( 'https://x.test/?a=1&b=2', 'Re-check "server"' );

        self::assertStringContainsString( 'href="https://x.test/?a=1&amp;b=2"', $button );
        self::assertStringContainsString( 'title="Re-check &quot;server&quot;"', $button );
        self::assertStringContainsString( 'dashicons dashicons-update', $button );
        self::assertStringContainsString( '<span class="screen-reader-text">Re-check &quot;server&quot;</span>', $button );
    }

    public function test_icon_class_is_reduced_to_a_single_safe_token(): void {
        $output = $this->capture( static function (): void {
            AdminUi::emptyState( 'Empty', '', 'dashicons-x" onmouseover="alert(1)' );
        } );

        self::assertStringContainsString( 'dashicons dashicons-xonmouseoveralert1"', $output );
    }

    public function test_stats_renders_one_tile_per_item_and_nothing_when_empty(): void {
        $output = $this->capture( static function (): void {
            AdminUi::stats( [
                [ 'label' => 'Modules', 'value' => 3 ],
                [ 'label' => 'Pages', 'value' => 5, 'icon' => 'dashicons-admin-page' ],
            ] );
        } );

        self::assertSame( 2, substr_count( $output, 'class="supe-admin__stat"' ) );
        self::assertStringContainsString( '<span class="supe-admin__stat-value">3</span>', $output );
        self::assertStringContainsString( 'dashicons-admin-page', $output );

        self::assertSame( '', $this->capture( static fn () => AdminUi::stats( [] ) ) );
    }

    public function test_empty_state_and_notice(): void {
        $output = $this->capture( static function (): void {
            AdminUi::emptyState( 'Nothing yet', 'Add something.' );
            AdminUi::notice( 'Heads up', 'warning' );
        } );

        self::assertStringContainsString( 'Nothing yet', $output );
        self::assertStringContainsString( 'Add something.', $output );
        self::assertStringContainsString( 'supe-admin__notice--warning', $output );
    }

    private function capture( callable $fn ): string {
        ob_start();
        $fn();

        return (string) ob_get_clean();
    }
}
