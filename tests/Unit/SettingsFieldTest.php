<?php

declare(strict_types=1);

namespace Supe\Core\Tests\Unit;

use Brain\Monkey\Functions;
use Supe\Core\Support\SafeHtml;
use Supe\Core\Support\SettingsField;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

final class SettingsFieldTest extends TestCase {

    protected function set_up(): void {
        parent::set_up();

        Functions\when( 'esc_html' )->alias( static fn ( $s ) => htmlspecialchars( (string) $s, ENT_QUOTES ) );
        Functions\when( 'esc_attr' )->alias( static fn ( $s ) => htmlspecialchars( (string) $s, ENT_QUOTES ) );
    }

    public function test_id_is_built_from_option_and_key_and_made_attribute_safe(): void {
        self::assertSame( 'acme_images-max_edge', SettingsField::id( 'acme_images', 'max_edge' ) );
        self::assertSame( 'acme_images', SettingsField::id( 'acme_images' ) );
        self::assertSame( 'a--b-', SettingsField::id( 'a"', 'b>' ) );
    }

    public function test_toggle_reads_an_array_option_and_submits_zero_when_off(): void {
        Functions\when( 'get_option' )->justReturn( [ 'keep' => '1' ] );

        $on = (string) SettingsField::toggle( [ 'option' => 'acme', 'key' => 'keep', 'label' => 'Keep it' ] );

        self::assertStringContainsString( '<input type="hidden" name="acme[keep]" value="0">', $on );
        self::assertStringContainsString( 'role="switch"', $on );
        self::assertStringContainsString( 'id="acme-keep" name="acme[keep]" value="1" checked', $on );
        self::assertStringContainsString( '<span class="supe-admin__toggle-label">Keep it</span>', $on );

        $off = (string) SettingsField::toggle( [ 'option' => 'acme', 'key' => 'keep', 'value' => false ] );

        self::assertStringNotContainsString( 'checked', $off );
    }

    public function test_disabled_toggle_submits_nothing_so_the_stored_value_is_kept(): void {
        $html = (string) SettingsField::toggle( [ 'option' => 'acme', 'value' => true, 'disabled' => true ] );

        self::assertStringNotContainsString( 'type="hidden"', $html );
        self::assertStringContainsString( ' checked disabled', $html );
    }

    public function test_toggle_can_hide_its_label_visually_and_links_its_description(): void {
        $html = (string) SettingsField::toggle( [
            'option'      => 'acme',
            'value'       => 'yes',
            'label'       => 'Enable',
            'hide_label'  => true,
            'description' => 'Turns it all on.',
        ] );

        self::assertStringContainsString( 'supe-admin__toggle-label screen-reader-text', $html );
        self::assertStringContainsString( 'aria-describedby="acme-description"', $html );
        self::assertStringContainsString( '<p class="description" id="acme-description">Turns it all on.</p>', $html );
        self::assertStringContainsString( ' checked', $html ); // "yes" counts as on
    }

    public function test_number_renders_bounds_and_unit_and_ignores_non_numeric_values(): void {
        $html = (string) SettingsField::number( [
            'option' => 'acme',
            'key'    => 'edge',
            'value'  => 2560,
            'unit'   => 'px',
            'min'    => 0,
            'max'    => 'nope',
            'step'   => 10,
        ] );

        self::assertStringContainsString( 'name="acme[edge]" value="2560" min="0" step="10"', $html );
        self::assertStringNotContainsString( 'max=', $html );
        self::assertStringContainsString( '<span class="supe-admin__input-unit">px</span>', $html );

        self::assertStringContainsString( 'value=""', (string) SettingsField::number( [ 'option' => 'acme', 'value' => '"><script>' ] ) );
    }

    public function test_radio_checks_the_current_choice_and_escapes_labels(): void {
        $html = (string) SettingsField::radio( [
            'option'  => 'acme',
            'key'     => 'mode',
            'value'   => 'img',
            'legend'  => 'Delivery',
            'choices' => [
                'picture' => [ 'label' => '<picture>', 'description' => 'Best format per browser.' ],
                'img'     => 'Plain <img>',
            ],
        ] );

        self::assertStringContainsString( '<legend class="screen-reader-text">Delivery</legend>', $html );
        self::assertStringContainsString( 'value="img" checked', $html );
        self::assertStringNotContainsString( 'value="picture" checked', $html );
        self::assertStringContainsString( '&lt;picture&gt;', $html );
        self::assertStringContainsString( 'Best format per browser.', $html );
        self::assertStringContainsString( 'Plain &lt;img&gt;', $html );
    }

    public function test_text_uses_a_name_override_and_an_aria_label(): void {
        $html = (string) SettingsField::text( [
            'name'        => 'acme[rows][a][title]',
            'value'       => '"x"',
            'placeholder' => 'Title',
            'maxlength'   => 40,
            'aria_label'  => 'Title',
        ] );

        self::assertStringContainsString( 'id="acme-rows--a--title-" name="acme[rows][a][title]" value="&quot;x&quot;"', $html );
        self::assertStringContainsString( 'placeholder="Title" maxlength="40" aria-label="Title"', $html );
        self::assertStringContainsString( 'class="regular-text"', $html );
    }

    public function test_table_names_cells_by_row_and_column_and_honors_locked_cells(): void {
        Functions\when( 'esc_html__' )->returnArg();
        Functions\when( '__' )->returnArg();

        $html = (string) SettingsField::table( [
            'option'  => 'acme',
            'key'     => 'sizes',
            'columns' => [
                'name'  => [ 'label' => 'Name', 'type' => 'text' ],
                'width' => [ 'label' => 'Width', 'type' => 'number', 'unit' => 'px', 'min' => 0 ],
                'crop'  => [ 'label' => 'Crop', 'type' => 'toggle' ],
            ],
            'rows'    => [
                'thumbnail' => [ 'name' => 'thumbnail', 'width' => 150, 'crop' => true, '_locked' => [ 'name', 'crop' ] ],
                'hero'      => [ 'name' => 'hero', 'width' => 1920, 'crop' => false, '_removable' => true, '_locked' => [ 'name' ], '_display' => [ 'name' => new SafeHtml( '<b>Hero</b>' ) ] ],
            ],
            'addable' => true,
            'new_row' => [ 'width' => 800 ],
        ] );

        // Editable cells are named base[row][column] and labelled by their column.
        self::assertStringContainsString( 'name="acme[sizes][thumbnail][width]" value="150" min="0" aria-label="Width"', $html );
        self::assertStringContainsString( 'name="acme[sizes][hero][crop]" value="1" aria-label="Crop"', $html );

        // Locked cells submit nothing: plain text, SafeHtml display, or a disabled switch.
        self::assertStringNotContainsString( 'name="acme[sizes][thumbnail][name]"', $html );
        self::assertStringNotContainsString( 'name="acme[sizes][thumbnail][crop]"', $html );
        self::assertStringContainsString( '<span class="supe-admin__table-locked">thumbnail</span>', $html );
        self::assertStringContainsString( '<b>Hero</b>', $html );
        self::assertStringContainsString( 'value="1" checked disabled aria-label="Crop"', $html );

        // Only removable rows get a Remove button; the template row is removable and uses __ROW__.
        self::assertSame( 2, substr_count( $html, 'data-supe-table-remove' ) ); // hero + template
        self::assertStringContainsString( '<template data-supe-table-template>', $html );
        self::assertStringContainsString( 'name="acme[sizes][__ROW__][width]" value="800"', $html );
        self::assertStringContainsString( 'data-supe-table-add', $html );
    }

    public function test_table_falls_back_to_text_for_unknown_column_types_and_reads_rows_from_the_option(): void {
        Functions\when( 'get_option' )->justReturn( [ 'list' => [ 'a' => [ 'x' => 'stored' ] ] ] );

        $html = (string) SettingsField::table( [
            'option'  => 'acme',
            'key'     => 'list',
            'columns' => [ 'x' => [ 'label' => 'X', 'type' => 'colour' ] ],
        ] );

        self::assertStringContainsString( 'type="text"', $html );
        self::assertStringContainsString( 'name="acme[list][a][x]" value="stored"', $html );
        self::assertStringNotContainsString( 'data-supe-table-add', $html );
        self::assertStringNotContainsString( 'Actions', $html );
    }

    public function test_render_dispatches_on_type_and_flags_unknown_types(): void {
        ob_start();
        SettingsField::render( [ 'type' => 'number', 'option' => 'acme', 'value' => 5 ] );
        self::assertStringContainsString( 'type="number"', (string) ob_get_clean() );

        Functions\expect( '_doing_it_wrong' )->once();

        $unknown = SettingsField::control( [ 'type' => 'colour', 'option' => 'acme' ] );

        self::assertInstanceOf( SafeHtml::class, $unknown );
        self::assertSame( '', (string) $unknown );
    }
}
