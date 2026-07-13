<?php
/**
 * Contract test against the real upstream TekstTV and Streekomroep APIs.
 *
 * @package TekstTV_WP_Extensions
 */

declare(strict_types=1);

use TekstTV\BlockRegistry;
use ZuidWest\TekstTVExtensions\Schedule;
use ZuidWest\TekstTVExtensions\ScheduleTickerBlocks;

$root = dirname(__DIR__);
$teksttv_path = getenv('TEKSTTV_PATH') ?: $root . '/upstream/teksttv';
$streekomroep_path = getenv('STREEKOMROEP_PATH') ?: $root . '/upstream/streekomroep';

/**
 * Fail the contract test with a useful error.
 *
 * @param bool   $condition Whether the contract condition is satisfied.
 * @param string $message   Failure description.
 */
function teksttv_wp_extensions_contract_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, 'Contract failure: ' . $message . "\n");
        exit(1);
    }
}

if (!function_exists('wp_parse_args')) {
    /**
     * Minimal WordPress-compatible argument merge for BlockRegistry.
     *
     * @param array<string, mixed> $args     Supplied values.
     * @param array<string, mixed> $defaults Default values.
     * @return array<string, mixed>
     */
    function wp_parse_args(array $args, array $defaults = []): array
    {
        return array_merge($defaults, $args);
    }
}

if (!function_exists('__')) {
    /**
     * Minimal translation stub for the contract process.
     *
     * @param string $text Text to translate.
     */
    function __(string $text): string
    {
        return $text;
    }
}

if (!function_exists('do_action')) {
    /**
     * Minimal action stub for the contract process.
     */
    function do_action(): void
    {
    }
}

$upstream_files = [
    $teksttv_path . '/src/BlockRegistry.php',
    $streekomroep_path . '/src/BroadcastSchedule.php',
];
foreach ($upstream_files as $upstream_file) {
    teksttv_wp_extensions_contract_assert(
        is_readable($upstream_file),
        'required upstream file is missing: ' . $upstream_file
    );
    require_once $upstream_file;
}

foreach (['getCurrentRadioBroadcast', 'getNextRadioBroadcast', 'getToday', 'getTomorrow'] as $method) {
    teksttv_wp_extensions_contract_assert(
        method_exists(\Streekomroep\BroadcastSchedule::class, $method),
        'Streekomroep\\BroadcastSchedule::' . $method . '() is unavailable'
    );
}

require_once $root . '/src/Schedule.php';
require_once $root . '/src/ScheduleTickerBlocks.php';

$schedule = new class () implements Schedule {
    public function get_current_radio_broadcast(): object
    {
        return new class () {
            public function getName(): string
            {
                return 'Morning Show';
            }
        };
    }

    public function get_next_radio_broadcast(): null
    {
        return null;
    }

    public function get_today(): object
    {
        return (object) ['television' => [(object) ['name' => 'News']]];
    }

    public function get_tomorrow(): object
    {
        return (object) ['television' => []];
    }
};

(new ScheduleTickerBlocks(static fn (): Schedule => $schedule))->register();

$expected_types = [
    'streekomroep_ticker_current_fm',
    'streekomroep_ticker_next_fm',
    'streekomroep_ticker_today_tv',
    'streekomroep_ticker_tomorrow_tv',
];
teksttv_wp_extensions_contract_assert(
    array_keys(BlockRegistry::all('ticker')) === $expected_types,
    'the real TekstTV registry did not receive exactly four ticker types'
);

$saved = BlockRegistry::save('streekomroep_ticker_current_fm', []);
teksttv_wp_extensions_contract_assert(
    $saved === ['type' => 'streekomroep_ticker_current_fm'],
    'the real TekstTV registry save contract changed'
);
teksttv_wp_extensions_contract_assert(
    BlockRegistry::build('streekomroep_ticker_current_fm', [], 'tv1') === [
        ['message' => 'Now on FM: Morning Show'],
    ],
    'the current FM ticker output no longer matches the real registry contract'
);
teksttv_wp_extensions_contract_assert(
    BlockRegistry::build('streekomroep_ticker_today_tv', [], 'tv1') === [
        ['message' => 'Today on TV: News'],
    ],
    'the television ticker output no longer matches the real registry contract'
);

fwrite(STDOUT, "Upstream contract verified.\n");
