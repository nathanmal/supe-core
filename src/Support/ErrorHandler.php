<?php

declare(strict_types=1);

namespace Supe\Core\Support;


class ErrorHandler
{
    static function boot() : void
    {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) ) {
    
        // Determine target file path based on your WP_DEBUG_LOG definition
        $log_file = ( is_string( WP_DEBUG_LOG ) ) ? WP_DEBUG_LOG : WP_CONTENT_DIR . '/debug.log';
        
        // Hook directly into WordPress's error capturing sequence
        add_action( 'wp_die_handler', function( $handler ) use ( $log_file ) {
            // If an explicit WP error triggers a termination screen, log it here
            return $handler;
        });

        // Intercept native PHP errors before ServBay grabs them
        set_error_handler( function( $errno, $errstr, $errfile, $errline ) use ( $log_file ) {
            // Respect error reporting levels
            if ( !( error_reporting() & $errno ) ) {
                return false;
            }
            
            $error_type = match( $errno ) {
                E_USER_ERROR      => 'Fatal Error',
                E_USER_WARNING    => 'Warning',
                E_USER_NOTICE     => 'Notice',
                default           => 'Error',
            };

            $message = sprintf( "[%s] WordPress %s: %s in %s on line %d\n", date( 'd-M-Y H:i:s e' ), $error_type, $errstr, $errfile, $errline );
            error_log( $message, 3, $log_file );
            
            return false; // Let standard execution continue
        });
    }
    }
}