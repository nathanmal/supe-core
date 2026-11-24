<?php

declare(strict_types=1);

namespace Supe\Core\Support;

use Supe\Core\Core;

/**
 * SettingsField
 *
 * Form controls for Settings API fields on Supe settings pages, styled by
 * assets/admin.css. Use render() as the add_settings_field() callback and
 * describe the control in the field's $args:
 *
 *     add_settings_field( 'max_edge', __( 'Longest edge', 'acme' ), [ SettingsField::class, 'render' ], $page, $section, [
 *         'type'      => 'number',
 *         'option'    => 'acme_images',
 *         'key'       => 'max_edge',
 *         'unit'      => 'px',
 *         'min'       => 0,
 *         'label_for' => SettingsField::id( 'acme_images', 'max_edge' ),
 *     ] );
 *
 * Or call toggle() / number() / radio() / text() / table() directly where markup is needed
 * outside a field row, e.g. a toggle in a card header (see AdminUi::cardStart()).
 *
 * Common args:
 *  - option      (required) Option name the value is stored in.
 *  - key         Key within an array option: the input is named "option[key]".
 *  - value       The current value. Defaults to reading get_option( option )[ key ].
 *  - name        Input name, overriding the one built from option and key.
 *  - id          Input id. Defaults to id( option, key ), or to the name when given.
 *  - aria_label  Accessible name when there's no visible <label> (e.g. inside a table cell).
 *  - description Help text under the control.
 *  - disabled    Render the control disabled.
 *
 * Per type:
 *  - toggle: label (text beside the switch), hide_label (keep it for screen readers only).
 *            A hidden "0" is submitted while the switch is off, unless disabled — so a
 *            disabled toggle submits nothing and the sanitize callback should keep the
 *            stored value.
 *  - number: unit (e.g. "px", "MB"), min, max, step.
 *  - radio:  choices (value => label, or value => [ 'label' => ..., 'description' => ... ]),
 *            legend (accessible group name; the field title is shown visually).
 *  - text:   placeholder, maxlength, class (default "regular-text").
 *  - table:  an editable list of rows (see table()).
 */
final class SettingsField {

    public const TYPES = [ 'toggle', 'number', 'radio', 'text', 'table' ];

    /** Column types a table() cell can hold. */
    public const TABLE_CELL_TYPES = [ 'text', 'number', 'toggle' ];

    /**
     * add_settings_field() callback: outputs the control named by $args['type'].
     *
     * @param array<string, mixed> $args
     */
    public static function render( array $args ): void {
        echo self::control( $args )->html; // Escaped by the builders.
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function control( array $args ): SafeHtml {
        return match ( $args['type'] ?? '' ) {
            'toggle' => self::toggle( $args ),
            'number' => self::number( $args ),
            'radio'  => self::radio( $args ),
            'text'   => self::text( $args ),
            'table'  => self::table( $args ),
            default  => self::unknownType( $args ),
        };
    }

    /**
     * The input id for an option (and key), safe for id/for attributes:
     * "acme_images" + "max_edge" => "acme_images-max_edge".
     */
    public static function id( string $option, string $key = '' ): string {
        return (string) preg_replace( '/[^A-Za-z0-9_-]/', '-', $key === '' ? $option : $option . '-' . $key );
    }

    /**
     * An on/off switch (a checkbox with role="switch").
     *
     * @param array<string, mixed> $args
     */
    public static function toggle( array $args ): SafeHtml {
        [ 'name' => $name, 'id' => $id, 'value' => $value, 'disabled' => $disabled ] = self::common( $args );

        $label      = (string) ( $args['label'] ?? '' );
        $labelClass = 'supe-admin__toggle-label' . ( empty( $args['hide_label'] ) ? '' : ' screen-reader-text' );

        $idAttr = $id === '' ? '' : ' id="' . esc_attr( $id ) . '"';
        $html   = '<label class="supe-admin__toggle"' . ( $id === '' ? '' : ' for="' . esc_attr( $id ) . '"' ) . '>';

        if ( ! $disabled && $name !== '' ) {
            $html .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="0">';
        }

        $html .= '<input type="checkbox" role="switch" class="supe-admin__toggle-input"'
            . $idAttr . ( $name === '' ? '' : ' name="' . esc_attr( $name ) . '"' ) . ' value="1"'
            . ( self::isOn( $value ) ? ' checked' : '' )
            . ( $disabled ? ' disabled' : '' )
            . self::ariaLabel( $args )
            . self::describedBy( $args, $id ) . '>'
            . '<span class="supe-admin__toggle-track" aria-hidden="true"></span>';

        if ( $label !== '' ) {
            $html .= '<span class="' . esc_attr( $labelClass ) . '">' . esc_html( $label ) . '</span>';
        }

        $html .= '</label>';

        return new SafeHtml( $html . self::description( $args, $id ) );
    }

    /**
     * A number input, optionally with a unit shown after it.
     *
     * @param array<string, mixed> $args
     */
    public static function number( array $args ): SafeHtml {
        [ 'name' => $name, 'id' => $id, 'value' => $value, 'disabled' => $disabled ] = self::common( $args );

        $html = '<span class="supe-admin__input-group">'
            . '<input type="number" class="small-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"'
            . ' value="' . esc_attr( is_numeric( $value ) ? (string) $value : '' ) . '"';

        foreach ( [ 'min', 'max', 'step' ] as $attribute ) {
            if ( isset( $args[ $attribute ] ) && is_numeric( $args[ $attribute ] ) ) {
                $html .= ' ' . $attribute . '="' . esc_attr( (string) $args[ $attribute ] ) . '"';
            }
        }

        $html .= ( $disabled ? ' disabled' : '' ) . self::ariaLabel( $args ) . self::describedBy( $args, $id ) . '>';

        if ( isset( $args['unit'] ) && $args['unit'] !== '' ) {
            $html .= '<span class="supe-admin__input-unit">' . esc_html( (string) $args['unit'] ) . '</span>';
        }

        $html .= '</span>';

        return new SafeHtml( $html . self::description( $args, $id ) );
    }

    /**
     * A group of radio buttons, each with an optional description.
     *
     * @param array<string, mixed> $args
     */
    public static function radio( array $args ): SafeHtml {
        [ 'name' => $name, 'id' => $id, 'value' => $value, 'disabled' => $disabled ] = self::common( $args );

        $choices = is_array( $args['choices'] ?? null ) ? $args['choices'] : [];
        $legend  = (string) ( $args['legend'] ?? '' );

        $html = '<fieldset class="supe-admin__choices" id="' . esc_attr( $id ) . '"' . self::describedBy( $args, $id ) . '>';

        if ( $legend !== '' ) {
            $html .= '<legend class="screen-reader-text">' . esc_html( $legend ) . '</legend>';
        }

        foreach ( $choices as $choiceValue => $choice ) {
            $choiceValue = (string) $choiceValue;
            $label       = is_array( $choice ) ? (string) ( $choice['label'] ?? $choiceValue ) : (string) $choice;
            $detail      = is_array( $choice ) ? (string) ( $choice['description'] ?? '' ) : '';

            $html .= '<label class="supe-admin__choice">'
                . '<input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $choiceValue ) . '"'
                . ( (string) $value === $choiceValue ? ' checked' : '' )
                . ( $disabled ? ' disabled' : '' ) . '>'
                . '<span class="supe-admin__choice-text">'
                . '<span class="supe-admin__choice-label">' . esc_html( $label ) . '</span>';

            if ( $detail !== '' ) {
                $html .= '<span class="supe-admin__choice-description">' . esc_html( $detail ) . '</span>';
            }

            $html .= '</span></label>';
        }

        $html .= '</fieldset>';

        return new SafeHtml( $html . self::description( $args, $id ) );
    }

    /**
     * A single-line text input.
     *
     * @param array<string, mixed> $args
     */
    public static function text( array $args ): SafeHtml {
        [ 'name' => $name, 'id' => $id, 'value' => $value, 'disabled' => $disabled ] = self::common( $args );

        $html = '<input type="text" class="' . esc_attr( (string) ( $args['class'] ?? 'regular-text' ) ) . '"'
            . ' id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"'
            . ' value="' . esc_attr( is_scalar( $value ) ? (string) $value : '' ) . '"';

        if ( ! empty( $args['placeholder'] ) ) {
            $html .= ' placeholder="' . esc_attr( (string) $args['placeholder'] ) . '"';
        }

        if ( isset( $args['maxlength'] ) && is_numeric( $args['maxlength'] ) ) {
            $html .= ' maxlength="' . esc_attr( (string) (int) $args['maxlength'] ) . '"';
        }

        $html .= ( $disabled ? ' disabled' : '' ) . self::ariaLabel( $args ) . self::describedBy( $args, $id ) . '>';

        return new SafeHtml( $html . self::description( $args, $id ) );
    }

    /**
     * An editable table: one row per item, one input per cell. Inputs are
     * named base[rowKey][column], where base is the field's name (option, or
     * option[key]). Rows that are removed in the browser are simply not
     * submitted, so the sanitize callback should treat the submitted rows as
     * the complete list.
     *
     * Args (plus the common ones):
     *  - columns   column key => [ 'label' => ..., 'type' => text|number|toggle, and that
     *              type's args (unit, min, max, step, placeholder, maxlength, class) ]
     *  - rows      row key => column values. Defaults to the stored value. Per-row extras:
     *              _locked    list of column keys shown read-only (not submitted)
     *              _display   column key => string|SafeHtml shown for a read-only cell
     *                         (default: the value, plus the column's unit)
     *              _removable whether the row gets a Remove button (default false)
     *  - addable   show an "Add" button that appends a blank row (key "new-…"); new rows
     *              are removable
     *  - add_label the button text (default "Add row")
     *  - new_row   column values a new row starts with
     *
     * @param array<string, mixed> $args
     */
    public static function table( array $args ): SafeHtml {
        // Explicit rows win; only read the stored value when there are none.
        [ 'name' => $base, 'id' => $id, 'value' => $value ] = self::common( is_array( $args['rows'] ?? null ) ? [ 'value' => null ] + $args : $args );

        $columns = self::tableColumns( $args['columns'] ?? null );
        $rows    = is_array( $args['rows'] ?? null ) ? $args['rows'] : ( is_array( $value ) ? $value : [] );
        $addable = ! empty( $args['addable'] );
        $actions = $addable;

        foreach ( $rows as $row ) {
            $actions = $actions || ( is_array( $row ) && ! empty( $row['_removable'] ) );
        }

        $html = '<div class="supe-admin__table-field" id="' . esc_attr( $id ) . '" data-supe-table' . self::describedBy( $args, $id ) . '>'
            . '<div class="supe-admin__table-wrap"><table class="supe-admin__table supe-admin__table--form"><thead><tr>';

        foreach ( $columns as $column ) {
            $html .= '<th scope="col">' . esc_html( $column['label'] ) . '</th>';
        }

        if ( $actions ) {
            $html .= '<th scope="col"><span class="screen-reader-text">' . esc_html__( 'Actions', 'supe' ) . '</span></th>';
        }

        $html .= '</tr></thead><tbody data-supe-table-body>';

        foreach ( $rows as $rowKey => $row ) {
            $html .= self::tableRow( $base, (string) $rowKey, $columns, is_array( $row ) ? $row : [], $actions );
        }

        $html .= '</tbody></table></div>';

        if ( $addable ) {
            $newRow = is_array( $args['new_row'] ?? null ) ? $args['new_row'] : [];

            $html .= '<template data-supe-table-template>'
                . self::tableRow( $base, '__ROW__', $columns, [ '_removable' => true ] + $newRow, true )
                . '</template>'
                . '<p class="supe-admin__table-actions"><button type="button" class="button" data-supe-table-add>'
                . '<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> '
                . esc_html( (string) ( $args['add_label'] ?? __( 'Add row', 'supe' ) ) )
                . '</button></p>';
        }

        $html .= '</div>';

        return new SafeHtml( $html . self::description( $args, $id ) );
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $args
     * @return array{name: string, id: string, value: mixed, disabled: bool}
     */
    private static function common( array $args ): array {
        $option = (string) ( $args['option'] ?? '' );
        $key    = (string) ( $args['key'] ?? '' );

        $name = isset( $args['name'] ) ? (string) $args['name'] : ( $key === '' ? $option : $option . '[' . $key . ']' );

        return [
            'name'     => $name,
            'id'       => (string) ( $args['id'] ?? ( isset( $args['name'] ) ? self::id( $name ) : self::id( $option, $key ) ) ),
            'value'    => array_key_exists( 'value', $args ) ? $args['value'] : self::storedValue( $option, $key ),
            'disabled' => ! empty( $args['disabled'] ),
        ];
    }

    /**
     * @return array<string, array<string, mixed>> Column key => definition, with a valid type and a label.
     */
    private static function tableColumns( mixed $columns ): array {
        $valid = [];

        foreach ( is_array( $columns ) ? $columns : [] as $key => $column ) {
            $column = is_array( $column ) ? $column : [ 'label' => (string) $column ];
            $type   = (string) ( $column['type'] ?? 'text' );

            $column['type']  = in_array( $type, self::TABLE_CELL_TYPES, true ) ? $type : 'text';
            $column['label'] = (string) ( $column['label'] ?? $key );

            $valid[ (string) $key ] = $column;
        }

        return $valid;
    }

    /**
     * @param array<string, array<string, mixed>> $columns
     * @param array<string, mixed>                $row
     */
    private static function tableRow( string $base, string $rowKey, array $columns, array $row, bool $actions ): string {
        $locked  = is_array( $row['_locked'] ?? null ) ? $row['_locked'] : [];
        $display = is_array( $row['_display'] ?? null ) ? $row['_display'] : [];
        $html    = '<tr data-supe-table-row>';

        foreach ( $columns as $key => $column ) {
            $cell = [
                'name'       => $base . '[' . $rowKey . '][' . $key . ']',
                'value'      => $row[ $key ] ?? null,
                'aria_label' => $column['label'],
            ] + $column;

            unset( $cell['label'], $cell['description'] );

            $html .= '<td data-column="' . esc_attr( $key ) . '">';

            if ( in_array( $key, $locked, true ) ) {
                $html .= self::lockedCell( $column, $cell['value'], $display[ $key ] ?? null );
            } else {
                $html .= self::control( $cell )->html;
            }

            $html .= '</td>';
        }

        if ( $actions ) {
            $html .= '<td class="supe-admin__table-row-actions">';

            if ( ! empty( $row['_removable'] ) ) {
                $html .= '<button type="button" class="button-link supe-admin__table-remove" data-supe-table-remove>'
                    . '<span class="dashicons dashicons-trash" aria-hidden="true"></span>'
                    . '<span class="screen-reader-text">' . esc_html__( 'Remove row', 'supe' ) . '</span></button>';
            }

            $html .= '</td>';
        }

        return $html . '</tr>';
    }

    /**
     * @param array<string, mixed> $column
     */
    private static function lockedCell( array $column, mixed $value, mixed $display ): string {
        if ( $display instanceof SafeHtml ) {
            return $display->html;
        }

        if ( is_scalar( $display ) ) {
            return esc_html( (string) $display );
        }

        // A read-only toggle shows its state as a disabled switch (submits nothing).
        if ( $column['type'] === 'toggle' ) {
            return self::toggle( [ 'name' => '', 'id' => '', 'value' => $value, 'disabled' => true, 'aria_label' => $column['label'] ] )->html;
        }

        $text = is_scalar( $value ) ? (string) $value : '';
        $unit = (string) ( $column['unit'] ?? '' );

        return '<span class="supe-admin__table-locked">' . esc_html( $text !== '' && $unit !== '' ? $text . ' ' . $unit : $text ) . '</span>';
    }

    /**
     * @param array<string, mixed> $args
     */
    private static function ariaLabel( array $args ): string {
        return empty( $args['aria_label'] ) ? '' : ' aria-label="' . esc_attr( (string) $args['aria_label'] ) . '"';
    }

    private static function storedValue( string $option, string $key ): mixed {
        if ( $option === '' ) {
            return null;
        }

        $stored = get_option( $option );

        if ( $key === '' ) {
            return $stored;
        }

        return is_array( $stored ) ? ( $stored[ $key ] ?? null ) : null;
    }

    private static function isOn( mixed $value ): bool {
        return is_scalar( $value ) && filter_var( $value, FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * @param array<string, mixed> $args
     */
    private static function describedBy( array $args, string $id ): string {
        return empty( $args['description'] ) ? '' : ' aria-describedby="' . esc_attr( $id . '-description' ) . '"';
    }

    /**
     * @param array<string, mixed> $args
     */
    private static function description( array $args, string $id ): string {
        if ( empty( $args['description'] ) ) {
            return '';
        }

        return '<p class="description" id="' . esc_attr( $id . '-description' ) . '">' . esc_html( (string) $args['description'] ) . '</p>';
    }

    /**
     * @param array<string, mixed> $args
     */
    private static function unknownType( array $args ): SafeHtml {
        _doing_it_wrong(
            __CLASS__ . '::control',
            esc_html( sprintf( 'Unknown field type "%s". Use one of: %s.', (string) ( $args['type'] ?? '' ), implode( ', ', self::TYPES ) ) ),
            esc_html( Core::VERSION )
        );

        return new SafeHtml( '' );
    }
}
