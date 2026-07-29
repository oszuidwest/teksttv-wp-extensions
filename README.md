# TekstTV Streekomroep Extensions

A WordPress plugin that exposes the radio and television schedules from the [Streekomroep theme](https://github.com/oszuidwest/streekomroep-wp) as ticker types for the [TekstTV plugin](https://github.com/oszuidwest/teksttv-wp-plugin).

## Ticker types

- **Now on FM** — the programme currently playing according to the radio schedule.
- **Next on FM** — the next radio programme in the schedule.
- **Today on TV** — one ticker message for each of today's television programmes.
- **Tomorrow on TV** — one ticker message for each of tomorrow's television programmes.

The messages use the following prefixes: `Now on FM: …`, `Next on FM: …`, `Today on TV: …`, and `Tomorrow on TV: …`. TekstTV automatically adds its shared date and weekday scheduling controls to every type.

## Requirements

- WordPress 7.0 or newer.
- PHP 8.3 or newer.
- The TekstTV plugin with the `TekstTV\BlockRegistry` extension API.
- The active Streekomroep theme with `Streekomroep\BroadcastSchedule`.

The plugin registers its ticker types on `init` at priority 10, after TekstTV registers its built-in types at priority 5. If a dependency is unavailable, the plugin registers nothing and shows an explanatory notice to administrators.

## Installation

1. Download the latest release ZIP from [GitHub Releases](https://github.com/oszuidwest/teksttv-wp-extensions/releases).
2. Upload it through **WordPress Admin → Plugins → Add New → Upload Plugin**.
3. Activate **TekstTV Streekomroep Extensions**.
4. Add the required types under **Tekst TV → Loop → Ticker messages**.

The production plugin has no Composer dependencies. Composer is only required for development:

```bash
composer install
composer check
composer analyse
composer test
composer security
```

`composer contract` additionally verifies the extension against real checkouts of the TekstTV plugin and Streekomroep theme. Set `TEKSTTV_PATH` and `STREEKOMROEP_PATH` to those checkout directories before running it locally.

CI runs PHP syntax validation, Composer validation and auditing, PHP_CodeSniffer, PHPStan, strict PHPUnit tests, WordPress Plugin Check, a 90% line-coverage gate, a deterministic POT check, and the pinned upstream contract test. PHP behavior is tested on PHP 8.3 and 8.4. GitHub Actions are pinned to immutable commits, and Dependabot checks Composer and GitHub Actions dependencies every Monday.

## Release

The release workflow reads the version from `teksttv-wp-extensions.php` and can only be dispatched manually from `main`. It repeats the complete quality and upstream contract gates before packaging. The allowlisted archive contains only `teksttv-wp-extensions.php`, `README.md`, `src/`, and `languages/`; tests, stubs, Composer tooling, and CI files cannot enter the release. The workflow creates a SHA-256 checksum, tags only after the package has passed validation, and refuses to overwrite an existing GitHub Release. The `force` input is limited to recovering a missing release for the current version.

## Behaviour

All four builders share one `BroadcastSchedule` within a request, so the relatively expensive schedule model is not built four times.

If a programme is unavailable or the schedule cannot be built, that ticker type returns no messages. The `teksttv_wp_extensions_schedule_error` hook receives the thrown `Throwable`, allowing a site to connect its own logging.

TekstTV stores ticker configuration in its own options. Do not save a channel containing one of these types while this extension is deactivated: the current registry API cannot recognise an inactive type and may drop that row.
