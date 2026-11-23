<?php

declare(strict_types=1);

namespace Supe\Core\Support;

/**
 * AbstractSettingsPage
 *
 * Base class for a Settings API page inside the shared Supe shell. Services
 * add their own sections and fields with add_settings_section() /
 * add_settings_field() using the page's slug() as the page, and register
 * their options with register_setting() in its optionGroup(). The page
 * renders whatever has been registered — each section as its own card, with
 * one save bar for the whole form — so it never needs editing when a service
 * adds a setting.
 */
abstract class AbstractSettingsPage extends AbstractAdminPage {

    /**
     * The Settings API option group every setting on this page is
     * registered in (the first argument to register_setting()).
     */
    abstract public function optionGroup(): string;

    /**
     * Message shown when no service has added a section to this page yet.
     */
    protected function emptyMessage(): string {
        return __( 'Settings will appear here once a feature that uses them is enabled.', 'supe' );
    }

    protected function renderContent(): void {
        // Supe pages aren't under Settings, where WordPress prints these
        // automatically — without this, "Settings saved." never shows.
        if ( $this->parentSlug() !== 'options-general.php' ) {
            settings_errors();
        }

        $sections = $this->sections();

        if ( empty( $sections ) ) {
            AdminUi::emptyState(
                __( 'Nothing to configure yet', 'supe' ),
                $this->emptyMessage(),
                'dashicons-admin-settings'
            );
            return;
        }

        echo '<form class="supe-admin__form" method="post" action="options.php">';
        settings_fields( $this->optionGroup() );

        foreach ( $sections as $section ) {
            $this->renderSection( $section );
        }

        echo '<div class="supe-admin__savebar">';
        submit_button( null, 'primary', 'submit', false );
        echo '</div></form>';
    }

    /**
     * One Settings API section as a card: its title, its description
     * callback, then its fields in a standard form-table.
     *
     * @param array{id: string, title?: string, callback?: callable|null} $section
     */
    protected function renderSection( array $section ): void {
        global $wp_settings_fields;

        AdminUi::cardStart( (string) ( $section['title'] ?? '' ) );

        if ( ! empty( $section['callback'] ) ) {
            call_user_func( $section['callback'], $section );
        }

        if ( ! empty( $wp_settings_fields[ static::slug() ][ $section['id'] ] ) ) {
            echo '<table class="form-table" role="presentation">';
            do_settings_fields( static::slug(), $section['id'] );
            echo '</table>';
        }

        AdminUi::cardEnd();
    }

    /**
     * Sections services have added to this page, in registration order.
     *
     * @return array<string, array{id: string, title?: string, callback?: callable|null}>
     */
    protected function sections(): array {
        global $wp_settings_sections;

        return $wp_settings_sections[ static::slug() ] ?? [];
    }
}
