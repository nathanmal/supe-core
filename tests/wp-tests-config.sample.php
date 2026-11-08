<?php
/**
 * One-time setup for Feature (WordPress integration) tests.
 *
 * Copy this file to `wp-tests-config.php` (same directory) and fill in a
 * throwaway database plus the path to an actual WordPress core checkout —
 * wp-phpunit/wp-phpunit ships the test *suite*, not WordPress itself.
 *
 * All values may also be supplied via environment variables so CI doesn't
 * need a committed config file.
 */

// Absolute path to a WordPress core checkout, trailing slash required.
define( 'ABSPATH', getenv( 'WP_TESTS_ABSPATH' ) ?: '/path/to/wordpress/' );

define( 'WP_DEBUG', true );

// ── Database (use a dedicated, disposable test database — it gets wiped) ──
define( 'DB_NAME',     getenv( 'WP_TESTS_DB_NAME' )     ?: 'supe_test' );
define( 'DB_USER',     getenv( 'WP_TESTS_DB_USER' )     ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASSWORD' ) ?: '' );
define( 'DB_HOST',     getenv( 'WP_TESTS_DB_HOST' )     ?: 'localhost' );
define( 'DB_CHARSET',  'utf8' );
define( 'DB_COLLATE',  '' );

$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL',  'admin@example.org' );
define( 'WP_TESTS_TITLE',  'Supe Test Site' );

define( 'WP_PHP_BINARY', 'php' );
