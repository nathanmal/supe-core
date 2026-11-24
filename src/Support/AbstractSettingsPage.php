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
 *
 * Extra keys in add_settings_section()'s $args (WordPress keeps them on the
 * section) shape how it renders:
 *
 *  - supe_parent:      id of another section on this page. The section renders
 *                      as a sub-section inside that section's card, after the
 *                      parent's own fields. Children render in registration order.
 *  - supe_description: short text under the card / sub-section title.
 *  - supe_icon:        dashicon class for the card header (top-level sections only).
 *  - supe_action:      SafeHtml, or a callable returning it, shown at the right of the
 *                      card / sub-section header, e.g. SettingsField::toggle() as an
 *                      on/off switch for the whole group.
 *  - supe_collapsible: child sections only. true = starts collapsed, "open" = starts
 *                      expanded; the heading shows/hides the body (AdminUi::subsectionStart()).
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

        foreach ( $this->topLevelSections() as $section ) {
            $this->renderSection( $section );
        }

        echo '<div class="supe-admin__savebar">';
        submit_button( null, 'primary', 'submit', false );
        echo '</div></form>';
    }

    /**
     * One Settings API section as a card: its title, its description
     * callback, its fields in a standard form-table, then its child sections.
     *
     * @param array<string, mixed> $section
     */
    protected function renderSection( array $section ): void {
        AdminUi::cardStart(
            (string) ( $section['title'] ?? '' ),
            (string) ( $section['supe_description'] ?? '' ),
            (string) ( $section['supe_icon'] ?? '' ),
            self::action( $section )
        );

        $this->renderSectionBody( $section );

        foreach ( $this->childSections( (string) $section['id'] ) as $child ) {
            $this->renderSubsection( $child );
        }

        AdminUi::cardEnd();
    }

    /**
     * A child section (supe_parent) as a sub-section inside its parent's card.
     *
     * @param array<string, mixed> $section
     */
    protected function renderSubsection( array $section ): void {
        $collapsible = $section['supe_collapsible'] ?? false;

        AdminUi::subsectionStart(
            (string) ( $section['title'] ?? '' ),
            (string) ( $section['supe_description'] ?? '' ),
            self::action( $section ),
            ! empty( $collapsible ),
            $collapsible === 'open'
        );

        $this->renderSectionBody( $section );

        AdminUi::subsectionEnd();
    }

    /**
     * @param array<string, mixed> $section
     */
    private function renderSectionBody( array $section ): void {
        global $wp_settings_fields;

        if ( ! empty( $section['callback'] ) && is_callable( $section['callback'] ) ) {
            call_user_func( $section['callback'], $section );
        }

        if ( ! empty( $wp_settings_fields[ static::slug() ][ $section['id'] ] ) ) {
            echo '<table class="form-table" role="presentation">';
            do_settings_fields( static::slug(), $section['id'] );
            echo '</table>';
        }
    }

    /**
     * Sections rendered as cards: those without a supe_parent, plus any whose
     * parent isn't registered (so a typo never hides a section).
     *
     * @return list<array<string, mixed>>
     */
    protected function topLevelSections(): array {
        $sections = $this->sections();

        return array_values( array_filter(
            $sections,
            static fn ( array $section ): bool => ! isset( $sections[ (string) ( $section['supe_parent'] ?? '' ) ] )
        ) );
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function childSections( string $parentId ): array {
        return array_values( array_filter(
            $this->sections(),
            static fn ( array $section ): bool => (string) ( $section['supe_parent'] ?? '' ) === $parentId
                && $parentId !== (string) $section['id']
        ) );
    }

    /**
     * @param array<string, mixed> $section
     */
    private static function action( array $section ): ?SafeHtml {
        $action = $section['supe_action'] ?? null;

        if ( ! $action instanceof SafeHtml && is_callable( $action ) ) {
            $action = $action( $section );
        }

        return $action instanceof SafeHtml ? $action : null;
    }

    /**
     * Sections services have added to this page, in registration order.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function sections(): array {
        global $wp_settings_sections;

        return $wp_settings_sections[ static::slug() ] ?? [];
    }
}
