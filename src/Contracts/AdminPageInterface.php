<?php

declare(strict_types=1);

namespace Supe\Core\Contracts;

use Supe\Core\Support\Container;

/**
 * AdminPageInterface
 *
 * Every Supe admin page/menu must implement this contract. Instances are
 * created by AdminMenuRegistry with the shared container, then registered
 * with WordPress on the `admin_menu` hook.
 */
interface AdminPageInterface {

    public function __construct( Container $container );

    /**
     * Globally unique menu slug for this page.
     * Use reverse-DNS / composer-style: "vendor-module-page"
     *
     * @example "acme-gallery-settings"
     */
    public static function slug(): string;

    /**
     * The page's browser title / on-screen heading.
     */
    public function pageTitle(): string;

    /**
     * The label shown in the WordPress admin menu.
     */
    public function menuTitle(): string;

    /**
     * The capability a user must have to see and access this page.
     *
     * @example "manage_options"
     */
    public function capability(): string;

    /**
     * The parent menu slug to attach this page under as a submenu.
     *
     * @example "options-general.php"
     * @return string|null Null registers a new top-level menu instead.
     */
    public function parentSlug(): ?string;

    /**
     * Dashicon class name, image URL, or base64-encoded SVG data URI.
     * Ignored for submenu pages (parentSlug() !== null).
     */
    public function icon(): string;

    /**
     * Position in the admin menu order. Null lets WordPress decide.
     */
    public function position(): ?int;

    /**
     * Output the page's markup. Called directly by WordPress when a user
     * views this page.
     */
    public function render(): void;
}
