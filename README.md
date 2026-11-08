# Supe Core

**Version-aware WordPress module loader with a shared service container.**

`nathanmal/supe-core` provides the runtime that the [Supe](https://github.com/nathanmal/supe) plugin boots: the singleton `Core`, the `ModuleRegistry`, the `Container`, and the `AbstractModule`/`ModuleInterface` contracts that every Supe module package (`supe-editor`, `supe-email`, `supe-media`, `supe-security`, or your own) is built on.

You normally don't require this package directly — it's pulled in transitively by the `nathanmal/supe` plugin and by any Supe module package. Module authors require it directly since it's what their module classes extend/implement.

---

## How It Works

```
Plugin A (requires acme/gallery ^1.2)
Plugin B (requires acme/gallery ^1.3)   ← wins; 1.3 > 1.2
         ↓
   supe/before_boot hook
         ↓
   ModuleRegistry::resolve()            ← picks highest version per ID
         ↓
   GalleryModule::boot()                ← runs once
```

---

## Installation

```bash
composer require nathanmal/supe-core
```

The `Supe\Core\Core` singleton does nothing on its own — something must call `Core::getInstance()->boot()` on `plugins_loaded`. That's the job of the `nathanmal/supe` plugin, which must be active in WordPress before any module boots.

---

## Building a Module

Copy `stubs/ExampleModule.php` into your own module repository:

```
my-module/
├── composer.json
├── src/
│   └── MyModule.php       ← extends AbstractModule
└── bootstrap.php          ← registers into Supe
```

Within this project's own `Supe\*` ecosystem, every module names its main class `Module` (e.g. `Supe\Editor\Module`, `Supe\Email\Module`) so the module name lives in the namespace rather than the class name — see `supe-editor`, `supe-email`, `supe-media`, `supe-security` for the pattern. A third-party module outside that namespace (like this example) can name its class however it likes.

**`composer.json`**
```json
{
    "name": "acme/gallery",
    "type": "library",
    "require": {
        "nathanmal/supe-core": "^1.0"
    },
    "autoload": {
        "psr-4": { "AcmeCorp\\GalleryModule\\": "src/" },
        "files": ["bootstrap.php"]
    }
}
```

**`bootstrap.php`**
```php
<?php
if ( function_exists( 'add_action' ) ) {
    add_action( 'supe/before_boot', function ( $registry ) {
        $registry->register( \AcmeCorp\GalleryModule\GalleryModule::class );
    } );
}
```

**`src/GalleryModule.php`**
```php
<?php
final class GalleryModule extends \Supe\Core\Support\AbstractModule {

    public static function id(): string      { return 'acme/gallery'; }
    public static function version(): string { return '1.2.0'; }

    public function boot(): void {
        $this->addAction( 'init', [ $this, 'registerPostTypes' ] );
    }
}
```

---

## Building an Admin Page

`Core` registers one shared top-level "Supe" menu itself (`Admin\DashboardPage`, slug
`supe`) the moment it's constructed — before any module boots — precisely so every
module can attach its own page under it as a submenu instead of scattering itself
across `wp-admin`. Any module does this by extending `AbstractAdminPage` and calling
`$this->addAdminPage(...)` from its `boot()`. See `stubs/ExampleAdminPage.php` for a
full copy-paste starting point.

```php
<?php
final class GallerySettingsPage extends \Supe\Core\Support\AbstractAdminPage {

    public static function slug(): string { return 'acme-gallery-settings'; }

    public function pageTitle(): string { return __( 'Gallery Settings', 'acme-gallery' ); }

    public function parentSlug(): ?string { return \Supe\Core\Admin\DashboardPage::slug(); }

    public function render(): void {
        echo '<div class="wrap"><h1>' . esc_html( $this->pageTitle() ) . '</h1></div>';
    }
}
```

```php
final class GalleryModule extends \Supe\Core\Support\AbstractModule {
    // ...
    public function boot(): void {
        $this->addAdminPage( GallerySettingsPage::class );
    }
}
```

Only `slug()`, `pageTitle()`, and `render()` are required — `menuTitle()`, `capability()`,
`parentSlug()`, `icon()`, and `position()` all have sensible defaults on `AbstractAdminPage`
that you can override as needed. `parentSlug()` defaults to `null`, which registers a
brand new top-level menu instead of a submenu — reach for that only if a page genuinely
doesn't belong under the shared Supe menu; return `DashboardPage::slug()` (rather than
hardcoding the string `'supe'`) so a future rename of the shared menu doesn't silently
orphan your page. A submenu can also point at any existing WordPress menu instead
(e.g. `'options-general.php'` for Settings, `'upload.php'` for Media).

Pages are collected in a single shared `AdminMenuRegistry` (owned by `Core`, one instance
across every module) and added to WordPress on `admin_menu` — top-level pages before
submenus, so a submenu's parent always exists first.

---

## Available Hooks

| Hook | When | Passed |
|---|---|---|
| `supe/before_boot` | Before any module boots | `$registry` |
| `supe/after_boot` | After all modules have booted | `$registry` |
| `supe/module_registered` | When a candidate is added | `$id, $version, $class` |
| `supe/before_module_boot` | Before a specific module boots | `$module` |
| `supe/after_module_boot` | After a specific module boots | `$module` |
| `supe/module_booted/{id}` | After module `{id}` boots | `$module` |
| `supe/admin_page_registered` | When an admin page is added to the registry | `$slug, $pageClass` |
| `supe/admin_page_added` | After an admin page is added to the WP menu | `$slug, $page` |

---

## Checking if a Module is Loaded

```php
// In any plugin/theme, after supe/after_boot:
$registry = \Supe\Core\Core::getInstance()->getRegistry();

$registry->isLoaded( 'acme/gallery' );            // bool
$registry->isLoaded( 'acme/gallery', '1.2.0' );   // requires >= 1.2.0
$registry->getLoadedModules();                    // ['acme/gallery' => '1.3.0', ...]
```

Or use the global helper:

```php
$container = supe();                            // Container
$repo      = supe( 'acme.gallery.repository' ); // resolved binding
```

---

## Module Registry Pattern Summary

```
                    ┌──────────────────────────────┐
                    │  supe/before_boot  (hook) │
                    └──────────────┬───────────────┘
                                   │
              ┌────────────────────┼──────────────────────┐
              │                    │                       │
        Plugin A                Theme B               Plugin C
   registers acme/gallery    registers acme/gallery   registers acme/seo
        v1.2.0                    v1.3.0                  v2.0.0
              │                    │                       │
              └────────────────────┼───────────────────────┘
                                   │
                    ┌──────────────▼───────────────┐
                    │    ModuleRegistry::resolve() │
                    │  acme/gallery → picks v1.3.0 │
                    │  acme/seo     → picks v2.0.0 │
                    └──────────────┬───────────────┘
                                   │
                       boot() called once per winner
```

---

## Testing

This package (and every module built on it) follows the same two-tier PHPUnit setup:

- **Unit** (`tests/Unit/`, `phpunit.xml.dist`) — no WordPress at all. WP functions/hooks are faked by [`brain/monkey`](https://github.com/Brain-WP/BrainMonkey) via the [`yoast/wp-test-utils`](https://github.com/Yoast/wp-test-utils) `BrainMonkey\TestCase` base class. Fast, no database, safe to run constantly while developing.
- **Feature** (`tests/Feature/`, `phpunit-integration.xml.dist`) — boots a real WordPress install against a real test database, using [`wp-phpunit/wp-phpunit`](https://github.com/wp-phpunit/wp-phpunit) (ships the WP core test suite as a Composer package) and `yoast/wp-test-utils`' `WPIntegration\TestCase`.

```bash
composer install
composer test:unit      # fast, no WordPress or DB needed
composer test:feature   # needs the one-time setup below
```

### Why Feature tests need one-time setup

`wp-phpunit/wp-phpunit` ships the WP core *test suite*, not WordPress itself, and needs a real database to run migrations against. One time per machine:

1. Get a WordPress core checkout somewhere (e.g. `git clone https://github.com/WordPress/WordPress.git`).
2. Create a disposable test database (it gets wiped on every run — never point this at a real site's DB).
3. Copy `tests/wp-tests-config.sample.php` to `tests/wp-tests-config.php` and fill in `ABSPATH` (the WordPress checkout) and the DB credentials — or set the equivalent `WP_TESTS_*` environment variables instead of editing the file.
4. Optionally set `WP_TESTS_DIR` if you want to point at a shared/existing WP core test-suite checkout instead of the one `wp-phpunit/wp-phpunit` installed into `vendor/`.

### Writing your own tests as you build a module

Each module package (`supe-editor`, `supe-email`, `supe-media`, `supe-security`) ships this same structure and a couple of example tests — copy their `tests/Unit/*Test.php` files as a starting template. A few things worth knowing:

- Every Unit `TestCase` uses WordPress-style `set_up()`/`tear_down()` methods (not `setUp()`/`tearDown()`) — that's `yoast/wp-test-utils` bridging PHPUnit versions; always call `parent::set_up()` / `parent::tear_down()` first.
- Brain Monkey fakes `add_action`/`do_action`/`apply_filters`/etc. by default with real (in-memory) hook behavior — you only need `Functions\expect(...)` or `Functions\when(...)` when you care about a specific function's return value or want to assert it was called.
- WP classes like `WP_Error`/`WP_Post`/`WP_User` don't exist outside WordPress. If your code under test references one, call `self::makeDoubleForUnavailableClass(\WP_Error::class)` in `set_up()` first.
- Mocking a genuine **PHP built-in** (`header()`, `headers_sent()`, etc. — as opposed to a WordPress function) requires listing it in a `patchwork.json` file at the package root: `{"redefinable-internals": ["header"]}`. Ordinary WordPress functions need no such declaration.
- A Feature test's `tests/bootstrap-feature.php` re-registers the module into `supe/before_boot` explicitly (inside `muplugins_loaded`) rather than relying on `bootstrap.php`'s own `add_action()` call — that call is guarded to no-op when Composer autoloads it before WordPress exists (see `bootstrap.php` in any module package), which is always true the moment PHPUnit's own binary loads `vendor/autoload.php`.

## Requirements

- PHP 8.1+
- Composer (for module dependency management)

## License

MIT
