<?php

declare(strict_types=1);

namespace Supe\Core\Support;

use Composer\InstalledVersions;

/**
 * PackageAssets
 *
 * Resolves public URLs for files shipped inside a Supe Composer package
 * (supe-core itself or any module), so CSS/JS can be enqueued as real files.
 *
 * Supe packages live in the host plugin's vendor/ directory, so a file's
 * Composer install path (wp-content/plugins/supe/vendor/nathanmal/{pkg}/…)
 * is web-reachable. But when a package is symlinked in through a Composer
 * path repository, PHP sees its *real* path (e.g. ~/Development/{pkg}),
 * which is outside wp-content, so plugin_basename() / plugins_url() can't
 * turn it into a URL. Worse, a sibling symlinked plugin whose real path is
 * a string prefix of ours (../supe vs ../supe-core) rewrites it to a bogus
 * URL. register() records the install path ↔ real path pair in
 * $wp_plugin_paths, as wp_register_plugin_realpath() does for symlinked
 * plugins. It's a no-op when the package isn't symlinked.
 *
 * WordPress also realpath()s block.json asset paths, so a module that
 * registers blocks should call register() from boot() even if it never
 * calls url() itself.
 */
final class PackageAssets {

    private string $package;
    private string $rootDir;
    private ?string $installPath;
    private bool $registered = false;

    /**
     * @param string      $package     Composer package name, e.g. "nathanmal/supe-editor".
     * @param string      $rootDir     The package's root directory (where its composer.json is).
     * @param string|null $installPath Override for the Composer install path; defaults to
     *                                 Composer\InstalledVersions. Mainly for tests.
     */
    public function __construct( string $package, string $rootDir, ?string $installPath = null ) {
        $this->package     = $package;
        $this->rootDir     = rtrim( $rootDir, '/\\' );
        $this->installPath = $installPath;
    }

    /**
     * Map this package's install path to its real path, so WordPress can
     * build URLs for files inside it. Safe to call more than once.
     */
    public function register(): void {
        global $wp_plugin_paths;

        if ( $this->registered ) {
            return;
        }

        $this->registered = true;

        $installPath = $this->installPath ?? $this->composerInstallPath();
        $realPath    = realpath( $this->rootDir );

        if ( $installPath === null || $realPath === false ) {
            return;
        }

        $installPath = self::collapseDotSegments( wp_normalize_path( $installPath ) );
        $realPath    = wp_normalize_path( $realPath );

        if ( $installPath !== $realPath ) {
            if ( ! is_array( $wp_plugin_paths ) ) {
                $wp_plugin_paths = [];
            }

            $wp_plugin_paths[ $installPath ] = $realPath;
        }
    }

    /**
     * Public URL of a file in this package, e.g. url( 'assets/admin.css' ).
     */
    public function url( string $relativePath ): string {
        $this->register();

        return plugins_url( ltrim( $relativePath, '/' ), $this->rootFile() );
    }

    /**
     * Absolute filesystem path of a file in this package.
     */
    public function path( string $relativePath ): string {
        return $this->rootDir . '/' . ltrim( $relativePath, '/' );
    }

    /**
     * Cache-busting version string for a file: its modification time, or
     * null (WordPress's "no version") if the file doesn't exist.
     */
    public function version( string $relativePath ): ?string {
        $path = $this->path( $relativePath );

        return is_file( $path ) ? (string) filemtime( $path ) : null;
    }

    /**
     * A file in the package root, for APIs that take a "plugin file" to
     * resolve URLs against (plugins_url( $path, $file )).
     */
    public function rootFile(): string {
        return $this->rootDir . '/composer.json';
    }

    private function composerInstallPath(): ?string {
        if ( ! class_exists( InstalledVersions::class ) || ! InstalledVersions::isInstalled( $this->package ) ) {
            return null;
        }

        return InstalledVersions::getInstallPath( $this->package );
    }

    /**
     * Resolves ./.. segments lexically: unlike realpath(), without following
     * the symlink we're trying to preserve.
     */
    public static function collapseDotSegments( string $path ): string {
        $segments = [];

        foreach ( explode( '/', $path ) as $segment ) {
            if ( $segment === '..' ) {
                array_pop( $segments );
            } elseif ( $segment !== '.' && ( $segment !== '' || $segments === [] ) ) {
                $segments[] = $segment;
            }
        }

        return implode( '/', $segments );
    }
}
