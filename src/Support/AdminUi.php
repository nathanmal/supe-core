<?php

declare(strict_types=1);

namespace Supe\Core\Support;

/**
 * AdminUi
 *
 * Shared markup builders for Supe admin pages, styled by assets/admin.css
 * (enqueued on every Supe screen by Admin\AdminAssets). Use these from a
 * page's renderContent() instead of hand-writing wp-admin markup, so every
 * module's screens share one look.
 *
 * Every helper escapes the plain strings it's given. Table cells may also
 * be SafeHtml (e.g. from badge() or code()) to output trusted markup as-is.
 */
final class AdminUi {

    /** Badge / notice tones understood by admin.css. */
    public const TONES = [ 'neutral', 'accent', 'success', 'warning', 'danger', 'info' ];

    // -------------------------------------------------------------------------
    // Brand
    // -------------------------------------------------------------------------

    /**
     * The Supe logo mark as inline SVG (decorative; hidden from screen readers).
     */
    public static function logo(): string {
        return '<svg class="supe-admin__logo" viewBox="0 0 32 32" width="32" height="32" aria-hidden="true" focusable="false">'
            . '<defs><linearGradient id="supe-admin-logo-gradient" x1="0" y1="0" x2="1" y2="1">'
            . '<stop offset="0" stop-color="#8b7bff"/><stop offset="1" stop-color="#ff5fa2"/>'
            . '</linearGradient></defs>'
            . '<path d="M16 1.5 28.6 8.75v14.5L16 30.5 3.4 23.25V8.75Z" fill="url(#supe-admin-logo-gradient)"/>'
            . '<path d="M18.2 6.5 10.5 17.6h5.2l-1.9 7.9 7.7-11.1h-5.2Z" fill="#fff"/>'
            . '</svg>';
    }

    // -------------------------------------------------------------------------
    // Inline builders (return SafeHtml so they can be nested in table cells)
    // -------------------------------------------------------------------------

    /**
     * A small pill label, e.g. a version number or a status.
     */
    public static function badge( string $text, string $tone = 'neutral' ): SafeHtml {
        return new SafeHtml(
            '<span class="supe-admin__badge supe-admin__badge--' . esc_attr( self::tone( $tone ) ) . '">'
            . esc_html( $text )
            . '</span>'
        );
    }

    /**
     * An inline code token, e.g. a block name or option key.
     */
    public static function code( string $text ): SafeHtml {
        return new SafeHtml( '<code class="supe-admin__code">' . esc_html( $text ) . '</code>' );
    }

    // -------------------------------------------------------------------------
    // Block-level output
    // -------------------------------------------------------------------------

    /**
     * Open a content card. Always pair with cardEnd().
     *
     * @param string $icon Optional dashicon class, e.g. "dashicons-admin-media".
     */
    public static function cardStart( string $title = '', string $description = '', string $icon = '' ): void {
        echo '<section class="supe-admin__card">';

        if ( $title !== '' || $description !== '' ) {
            echo '<header class="supe-admin__card-header">';

            if ( $icon !== '' ) {
                echo '<span class="supe-admin__card-icon dashicons ' . esc_attr( self::iconClass( $icon ) ) . '" aria-hidden="true"></span>';
            }

            echo '<div>';

            if ( $title !== '' ) {
                echo '<h2 class="supe-admin__card-title">' . esc_html( $title ) . '</h2>';
            }

            if ( $description !== '' ) {
                echo '<p class="supe-admin__card-description">' . esc_html( $description ) . '</p>';
            }

            echo '</div></header>';
        }

        echo '<div class="supe-admin__card-body">';
    }

    /**
     * Close a card opened with cardStart().
     */
    public static function cardEnd(): void {
        echo '</div></section>';
    }

    /**
     * A responsive row of stat tiles.
     *
     * @param list<array{label: string, value: string|int, icon?: string}> $items
     */
    public static function stats( array $items ): void {
        if ( empty( $items ) ) {
            return;
        }

        echo '<div class="supe-admin__stats">';

        foreach ( $items as $item ) {
            $icon = self::iconClass( $item['icon'] ?? 'dashicons-chart-bar' );

            echo '<div class="supe-admin__stat">';
            echo '<span class="supe-admin__stat-icon dashicons ' . esc_attr( $icon ) . '" aria-hidden="true"></span>';
            echo '<span class="supe-admin__stat-value">' . esc_html( (string) $item['value'] ) . '</span>';
            echo '<span class="supe-admin__stat-label">' . esc_html( $item['label'] ) . '</span>';
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * A styled data table.
     *
     * @param array<string, string>                          $columns Column key => heading label.
     * @param list<array<string, string|int|SafeHtml|null>>  $rows    Each row keyed by column key.
     */
    public static function table( array $columns, array $rows ): void {
        echo '<div class="supe-admin__table-wrap"><table class="supe-admin__table"><thead><tr>';

        foreach ( $columns as $label ) {
            echo '<th scope="col">' . esc_html( $label ) . '</th>';
        }

        echo '</tr></thead><tbody>';

        foreach ( $rows as $row ) {
            echo '<tr>';

            foreach ( array_keys( $columns ) as $key ) {
                echo '<td>' . self::cell( $row[ $key ] ?? '' ) . '</td>';
            }

            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }

    /**
     * A friendly placeholder for "nothing here yet" screens.
     */
    public static function emptyState( string $title, string $message = '', string $icon = 'dashicons-info-outline' ): void {
        echo '<div class="supe-admin__empty">';
        echo '<span class="supe-admin__empty-icon dashicons ' . esc_attr( self::iconClass( $icon ) ) . '" aria-hidden="true"></span>';
        echo '<h2 class="supe-admin__empty-title">' . esc_html( $title ) . '</h2>';

        if ( $message !== '' ) {
            echo '<p class="supe-admin__empty-message">' . esc_html( $message ) . '</p>';
        }

        echo '</div>';
    }

    /**
     * An inline callout within page content. (For transient "saved" style
     * messages, use WordPress admin notices / settings_errors() instead.)
     */
    public static function notice( string $message, string $tone = 'info' ): void {
        echo '<div class="supe-admin__notice supe-admin__notice--' . esc_attr( self::tone( $tone ) ) . '" role="note">'
            . esc_html( $message )
            . '</div>';
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private static function cell( string|int|SafeHtml|null $value ): string {
        if ( $value instanceof SafeHtml ) {
            return $value->html;
        }

        return esc_html( (string) $value );
    }

    private static function tone( string $tone ): string {
        return in_array( $tone, self::TONES, true ) ? $tone : 'neutral';
    }

    /**
     * Restrict an icon to a single class-name-safe token.
     */
    private static function iconClass( string $icon ): string {
        return (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $icon );
    }
}
