<?php

declare(strict_types=1);

// Safe to load our own Composer autoload before WordPress: the plugin's/
// modules' eager "supe/before_boot" registrations guard on function_exists()
// and simply no-op until WordPress' hook system is actually live.
require dirname(__DIR__) . '/vendor/autoload.php';

if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
    define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
    $_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    fwrite( STDERR,
        "Could not find the WP test suite at {$_tests_dir}.\n" .
        "Run 'composer install' and copy tests/wp-tests-config.sample.php to\n" .
        "tests/wp-tests-config.php (see that file for the required setup).\n"
    );
    exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', static function (): void {
    \Supe\Core\Core::getInstance()->boot();
} );

require $_tests_dir . '/includes/bootstrap.php';
