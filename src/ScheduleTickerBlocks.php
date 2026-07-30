<?php
/**
 * TekstTV ticker type registration and schedule message builders.
 *
 * @package TekstTV_WP_Extensions
 */

declare(strict_types=1);

namespace ZuidWest\TekstTVExtensions;

use Closure;
use TekstTV\BlockRegistry;
use Throwable;

/**
 * Registers and builds the four Streekomroep ticker types.
 */
final class ScheduleTickerBlocks
{
    /**
     * Factory used to construct the schedule lazily.
     *
     * @var Closure
     */
    private Closure $schedule_factory;

    /**
     * Schedule shared by all ticker builders in the current request.
     *
     * @var Schedule|null
     */
    private ?Schedule $schedule = null;

    /**
     * Whether schedule construction already failed in the current request.
     *
     * @var bool
     */
    private bool $schedule_failed = false;

    /**
     * Create the ticker block registrar.
     *
     * @param callable(): Schedule|null $schedule_factory Optional schedule factory.
     */
    public function __construct(?callable $schedule_factory = null)
    {
        $this->schedule_factory = $schedule_factory !== null
            ? Closure::fromCallable($schedule_factory)
            : static fn (): Schedule => new ThemeSchedule();
    }

    /**
     * Register all Streekomroep ticker types with TekstTV.
     */
    public function register(): void
    {
        $this->register_type(
            'streekomroep_ticker_current_fm',
            'Nu op FM',
            'microphone',
            '#8e44ad',
            [$this, 'build_current_fm']
        );
        $this->register_type(
            'streekomroep_ticker_next_fm',
            'Straks op FM',
            'controls-forward',
            '#8e44ad',
            [$this, 'build_next_fm']
        );
        $this->register_type(
            'streekomroep_ticker_today_tv',
            'Vandaag op TV',
            'video-alt3',
            '#2271b1',
            [$this, 'build_today_tv']
        );
        $this->register_type(
            'streekomroep_ticker_tomorrow_tv',
            'Morgen op TV',
            'calendar-alt',
            '#2271b1',
            [$this, 'build_tomorrow_tv']
        );
    }

    /**
     * These ticker types have no type-specific settings. TekstTV renders its
     * shared date and weekday scheduling controls after this callback.
     *
     * @param int|string           $index  Ticker row index.
     * @param array<string, mixed> $data   Saved ticker data.
     * @param string               $prefix Form field prefix.
     */
    public function render_fields(int|string $index, array $data, string $prefix): void
    {
    }

    /**
     * Sanitize type-specific settings.
     *
     * @param array<string, mixed> $raw Raw ticker data.
     * @return array<string, mixed>
     */
    public function save(array $raw): array
    {
        return [];
    }

    /**
     * Build the current FM programme message.
     *
     * @param array<string, mixed> $data    Saved ticker data.
     * @param string               $channel TekstTV channel identifier.
     * @return list<array{message: string}>
     */
    public function build_current_fm(array $data, string $channel): array
    {
        return $this->with_schedule(function (Schedule $schedule): array {
            $broadcast = $schedule->get_current_radio_broadcast();
            if (!is_object($broadcast) || !is_callable([$broadcast, 'getName'])) {
                return [];
            }

            return $this->message('Nu op FM: ', $broadcast->getName());
        });
    }

    /**
     * Build the next FM programme message.
     *
     * @param array<string, mixed> $data    Saved ticker data.
     * @param string               $channel TekstTV channel identifier.
     * @return list<array{message: string}>
     */
    public function build_next_fm(array $data, string $channel): array
    {
        return $this->with_schedule(function (Schedule $schedule): array {
            $broadcast = $schedule->get_next_radio_broadcast();
            if (!is_object($broadcast) || !is_callable([$broadcast, 'getName'])) {
                return [];
            }

            return $this->message('Straks op FM: ', $broadcast->getName());
        });
    }

    /**
     * Build today's television programme messages.
     *
     * @param array<string, mixed> $data    Saved ticker data.
     * @param string               $channel TekstTV channel identifier.
     * @return list<array{message: string}>
     */
    public function build_today_tv(array $data, string $channel): array
    {
        return $this->with_schedule(
            fn (Schedule $schedule): array => $this->television_messages('Vandaag op TV: ', $schedule->get_today())
        );
    }

    /**
     * Build tomorrow's television programme messages.
     *
     * @param array<string, mixed> $data    Saved ticker data.
     * @param string               $channel TekstTV channel identifier.
     * @return list<array{message: string}>
     */
    public function build_tomorrow_tv(array $data, string $channel): array
    {
        return $this->with_schedule(
            fn (Schedule $schedule): array => $this->television_messages('Morgen op TV: ', $schedule->get_tomorrow())
        );
    }

    /**
     * Run a builder with the shared schedule and contain integration errors.
     *
     * @param callable $callback Schedule builder.
     * @phpstan-param callable(Schedule): list<array{message: string}> $callback
     * @return list<array{message: string}>
     */
    private function with_schedule(callable $callback): array
    {
        $schedule = $this->get_schedule();
        if ($schedule === null) {
            return [];
        }

        try {
            return $callback($schedule);
        } catch (Throwable $exception) {
            $this->report_error($exception);
            return [];
        }
    }

    /**
     * Get or lazily construct the request-scoped schedule.
     */
    private function get_schedule(): ?Schedule
    {
        if ($this->schedule !== null) {
            return $this->schedule;
        }
        if ($this->schedule_failed) {
            return null;
        }

        try {
            $schedule = ($this->schedule_factory)();
            if (!$schedule instanceof Schedule) {
                $this->schedule_failed = true;
                $this->report_error(new \UnexpectedValueException('The schedule factory must return a Schedule.'));
                return null;
            }

            $this->schedule = $schedule;
            return $this->schedule;
        } catch (Throwable $exception) {
            $this->schedule_failed = true;
            $this->report_error($exception);
            return null;
        }
    }

    /**
     * Convert a television schedule day into ticker messages.
     *
     * @param string $prefix Message prefix.
     * @param mixed  $day    Theme schedule day.
     * @return list<array{message: string}>
     */
    private function television_messages(string $prefix, mixed $day): array
    {
        if (!is_object($day) || !isset($day->television) || !is_iterable($day->television)) {
            return [];
        }

        $messages = [];
        foreach ($day->television as $broadcast) {
            if (!is_object($broadcast)) {
                continue;
            }

            $messages = array_merge($messages, $this->message($prefix, $broadcast->name ?? null));
        }

        return $messages;
    }

    /**
     * Build one non-empty ticker message.
     *
     * @param string $prefix Message prefix.
     * @param mixed  $name   Programme name.
     * @return list<array{message: string}>
     */
    private function message(string $prefix, mixed $name): array
    {
        if (!is_string($name) && !is_numeric($name)) {
            return [];
        }

        $name = trim((string) $name);
        return $name === '' ? [] : [['message' => $prefix . $name]];
    }

    /**
     * Register one ticker type with the upstream registry.
     *
     * @param string   $slug  Unique ticker type slug.
     * @param string   $label Administrator-facing label.
     * @param string   $icon  Dashicon name.
     * @param string   $color Icon background colour.
     * @param callable $build Message builder.
     * @phpstan-param callable(array<string, mixed>, string): list<array{message: string}> $build
     */
    private function register_type(string $slug, string $label, string $icon, string $color, callable $build): void
    {
        BlockRegistry::register($slug, [
            'label' => $label,
            'icon' => $icon,
            'color' => $color,
            'context' => 'ticker',
            'render' => [$this, 'render_fields'],
            'save' => [$this, 'save'],
            'build' => $build,
        ]);
    }

    /**
     * Publish a contained schedule integration error for optional logging.
     *
     * @param Throwable $exception Schedule integration error.
     */
    private function report_error(Throwable $exception): void
    {
        /**
         * Fires when the Streekomroep schedule cannot produce ticker data.
         *
         * @param Throwable $exception The schedule error.
         */
        do_action('teksttv_wp_extensions_schedule_error', $exception);
    }
}
