# Roadmap — supe-core

Candidate features for this package, to be prioritized and scoped properly later. Nothing here is committed — this is a parking lot, not a sprint plan.

Nothing currently planned. `supe-core` is intentionally small: the loader, registry, container and contracts, plus shared infrastructure that more than one module needs (admin shell/UI kit, settings page base, package asset URLs). Any future addition here should be weighed hard against "does this belong in a module instead?" before landing in the core package, since everything here is a dependency of every module.

## Promotion candidates

Things built in a module that might belong in core later but don't meet the bar yet (no second use). Add an entry whenever you notice one, per `../supe/SUPE.md` ("Promote shared code to supe-core"). Promote, or delete with a reason, when a second module needs it.

| Candidate | Lives in | Why it might be general |
|---|---|---|
| Asset registration from wp-scripts `.asset.php` files (deps + version) | `supe-editor` (`SharedComponents`, `EditorAssets`, `SupportAssets`, `HeaderBehavior`) | Any module with a JS/CSS build will need it. Would pair naturally with `Support\PackageAssets`. |
| "Configurable defaults" pattern (static `defaults()` + filtered instance method) | `supe-media` (CLAUDE.md convention) | A convention today. Could become a small base/trait if a second module adopts it. |

See `PROGRESS.md` in this directory for this package's change history and `../supe/TESTING.md` for its testing setup.
