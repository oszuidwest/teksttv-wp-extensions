# TekstTV Streekomroep Extensions

This WordPress plugin makes the radio and television schedules from the [Streekomroep theme](https://github.com/oszuidwest/streekomroep-wp) available in [TekstTV](https://github.com/oszuidwest/teksttv-wp-plugin).

It adds four ticker types:

- **Nu op FM:** the programme currently on air.
- **Straks op FM:** the next radio programme.
- **Vandaag op TV:** one message for each of today's television programmes.
- **Morgen op TV:** one message for each of tomorrow's television programmes.

Messages use fixed Dutch text. TekstTV provides the shared date and weekday settings.

## Requirements

- WordPress 7.0 or newer
- PHP 8.3 or newer
- The TekstTV plugin
- The active Streekomroep theme

If either dependency is missing, the ticker types are not registered and WordPress shows an admin notice.

## Installation

1. Download the latest ZIP from [GitHub Releases](https://github.com/oszuidwest/teksttv-wp-extensions/releases).
2. In WordPress, open **Plugins**, select **Add New Plugin**, and then select **Upload Plugin**.
3. Activate **TekstTV Streekomroep Extensions**.
4. Open **Tekst TV**, go to **Loop**, and add the types you need under **Ticker messages**.

## Development

Composer is only needed for development:

```bash
composer install
composer quality
composer security
```

Run `composer contract` to check compatibility with local checkouts of TekstTV and the Streekomroep theme. Set `TEKSTTV_PATH` and `STREEKOMROEP_PATH` to their respective directories first.

To create a release, update the version in `teksttv-wp-extensions.php`, then run the release workflow from `main`.

## Error handling

If a schedule cannot be loaded, the affected ticker type returns no messages. Use the `teksttv_wp_extensions_schedule_error` hook to log these errors.

TekstTV owns the ticker configuration. Do not edit a channel that uses these types while this plugin is inactive, as TekstTV may remove rows it does not recognise.
