<?php

declare(strict_types=1);

use Supe\Core\Core;

if ( ! function_exists( 'supe' ) ) {
    /**
     * Access the Supe service container or resolve a binding.
     *
     * Usage:
     *   supe()                 → Container instance
     *   supe('my-service')     → resolved binding
     *
     * @param  string|null $abstract
     * @return mixed
     */
    function supe( ?string $abstract = null ): mixed {
        $supe = Core::getInstance();

        if ( $abstract === null ) {
            return $supe->getContainer();
        }

        return $supe->getContainer()->make( $abstract );
    }
}

require_once(__DIR__ . '/helpers.php');