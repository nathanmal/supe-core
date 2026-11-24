# CLAUDE.md — supe-core

Package-specific guidance for Claude Code. The ecosystem-wide rules (architecture, module pattern, how packages stay connected, what's shared from core, PSR-4 discipline, code style, security, testing) live in `SUPE.md` in the `supe` plugin and are imported here. This directory is a sibling of `../supe`, not inside it, so they would not load otherwise:

@../supe/SUPE.md

This file only adds what's specific to `supe-core`. Before non-trivial work, read `PROGRESS.md` (current state) and `ROADMAP.md` (ideas and promotion candidates). The public API is documented in `README.md`. Log meaningful changes in `PROGRESS.md` (newest first, terse).

## Scope

`supe-core` is the runtime and shared infrastructure every module depends on:

- the loader: `Core`, `Registry\ModuleRegistry`/`ModuleCandidate`, `Support\Container`, `Support\AbstractModule`, `Contracts\*`, `Exceptions\*`, `supe()`
- admin: `Admin\AdminMenuRegistry`, `Admin\DashboardPage` (the shared "Supe" menu), `Admin\AdminAssets`
- the shared admin look & feel: `Support\AbstractAdminPage` (page shell), `Support\AbstractSettingsPage`, `Support\AdminUi` + `Support\SafeHtml`, `Support\SettingsField`, and `assets/admin.css` + `assets/admin.js`
- `Support\PackageAssets`: public URLs for files inside any Supe package

Everything here is a dependency of every module, so weigh each addition against "does this belong in a module instead?" Core stays generic: no module names, option keys, or behavior.

## Changing core (changes flow down)

A change to a shared API isn't done until the modules that use it are updated in the same change:

1. Update core code, Unit tests, `README.md` (public API), and `stubs/` if the example pattern changed.
2. Update every **active** module that uses it (`supe-editor`, `supe-media`): code, tests (`composer test:unit` green in each), `PROGRESS.md`.
3. Update the instructions in `../supe/SUPE.md` ("Shared from supe-core") and any module `CLAUDE.md` that describes the old way. Module files should point at the shared section rather than restate it.
4. Keep existing subclasses working where reasonable (e.g. `render()` stayed overridable when the page shell landed). The paused modules (`supe-email`, `supe-security`, `supe-server`) aren't edited, but shouldn't break when they're resumed.

## Promotions from modules (changes flow up)

When a module has built something general (second use, or obviously infrastructure), propose moving it here and confirm with the user first. Then move it, generalize it (modules pass their specifics in), and switch the original module over to the core version. `PackageAssets` (from `supe-editor`'s `Module::mapSymlinkedInstall()`) and `AbstractSettingsPage` (from `supe-media`'s `SettingsPage`) are the worked examples. Ideas that aren't ready yet go under "Promotion candidates" in `ROADMAP.md`.

## Conventions specific to this package

- Admin UI: every class in `assets/admin.css` is under `.supe-admin` and named `supe-admin__{element}` (BEM-ish), with `--supe-*` custom properties for colors. New `AdminUi` helpers escape all plain-string input, and return `SafeHtml` when meant to be nested (e.g. in table cells).
- `assets/admin.js` is plain ES5-ish JavaScript with no build step and no dependencies, driven only by `data-supe-*` attributes that PHP builders output. Keep it small; anything bigger belongs in a module's own build.
- The stylesheet is enqueued as a file (`AdminAssets` via `PackageAssets`), only on registered Supe screens. Don't inline it.
- `Core::VERSION` must match `composer.json`'s `"version"`.
- Unit tests use Brain Monkey with no WordPress. Call `$this->stubTranslationFunctions()` / `$this->stubEscapeFunctions()` explicitly (the Yoast base TestCase doesn't). A `Functions\when()` stub set in `set_up()` overrides a later `Functions\expect()` for the same function, so stub per test when a test needs to expect it.

## Commands

```bash
composer test:unit     # Brain Monkey, no WordPress
composer test:feature  # real WordPress; one-time setup in ../supe/TESTING.md
```

## Local development

The dev site `supe.test` loads this package through `~/Development/supe/vendor/nathanmal/supe-core → ~/Development/supe-core` (a symlink), so PHP and `assets/` edits are live (FPM opcache can take up to 60s). Admin CSS is served from `/wp-content/plugins/supe/vendor/nathanmal/supe-core/assets/admin.css`. Full dev-site notes are in `../supe-editor/CLAUDE.md` under "Local development".
