<?php

declare(strict_types=1);

namespace ZuidWest\TekstTVExtensions\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TekstTV\BlockRegistry;
use ZuidWest\TekstTVExtensions\Schedule;
use ZuidWest\TekstTVExtensions\ScheduleTickerBlocks;

final class ScheduleTickerBlocksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        BlockRegistry::$types = [];

        Functions\when('__')->returnArg();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_registers_four_ticker_types_with_expected_labels(): void
    {
        $blocks = new ScheduleTickerBlocks(static fn (): Schedule => $this->empty_schedule());
        $blocks->register();

        $this->assertSame(
            [
                'streekomroep_ticker_current_fm' => 'Now on FM',
                'streekomroep_ticker_next_fm' => 'Next on FM',
                'streekomroep_ticker_today_tv' => 'Today on TV',
                'streekomroep_ticker_tomorrow_tv' => 'Tomorrow on TV',
            ],
            array_map(static fn (array $type): string => $type['label'], BlockRegistry::$types)
        );

        foreach (BlockRegistry::$types as $type) {
            $this->assertSame('ticker', $type['context']);
            $this->assertIsCallable($type['render']);
            $this->assertIsCallable($type['save']);
            $this->assertIsCallable($type['build']);
        }
    }

    public function test_builds_current_and_next_fm_messages(): void
    {
        $schedule = new class () implements Schedule {
            public function get_current_radio_broadcast(): object
            {
                return $this->broadcast('Morning Show');
            }

            public function get_next_radio_broadcast(): object
            {
                return $this->broadcast('Lunchtime Radio');
            }

            private function broadcast(string $name): object
            {
                return new class ($name) {
                    public function __construct(private string $name)
                    {
                    }

                    public function getName(): string
                    {
                        return $this->name;
                    }
                };
            }

            public function get_today(): object
            {
                return (object) ['television' => []];
            }

            public function get_tomorrow(): object
            {
                return (object) ['television' => []];
            }
        };

        $blocks = new ScheduleTickerBlocks(static fn (): Schedule => $schedule);

        $this->assertSame(
            [['message' => 'Now on FM: Morning Show']],
            $blocks->build_current_fm([], 'tv1')
        );
        $this->assertSame(
            [['message' => 'Next on FM: Lunchtime Radio']],
            $blocks->build_next_fm([], 'tv1')
        );
    }

    public function test_builds_one_tv_message_per_programme(): void
    {
        $schedule = new class () implements Schedule {
            public function get_current_radio_broadcast(): null
            {
                return null;
            }

            public function get_next_radio_broadcast(): null
            {
                return null;
            }

            public function get_today(): object
            {
                return (object) [
                    'television' => [(object) ['name' => 'News'], (object) ['name' => 'Sports']],
                ];
            }

            public function get_tomorrow(): object
            {
                return (object) [
                    'television' => [(object) ['name' => 'Documentary']],
                ];
            }
        };

        $blocks = new ScheduleTickerBlocks(static fn (): Schedule => $schedule);

        $this->assertSame(
            [['message' => 'Today on TV: News'], ['message' => 'Today on TV: Sports']],
            $blocks->build_today_tv([], 'tv1')
        );
        $this->assertSame(
            [['message' => 'Tomorrow on TV: Documentary']],
            $blocks->build_tomorrow_tv([], 'tv1')
        );
    }

    public function test_returns_empty_output_when_schedule_has_no_programme(): void
    {
        $schedule = new class () implements Schedule {
            public function get_current_radio_broadcast(): null
            {
                return null;
            }

            public function get_next_radio_broadcast(): null
            {
                return null;
            }

            public function get_today(): object
            {
                return (object) ['television' => []];
            }

            public function get_tomorrow(): object
            {
                return (object) ['television' => []];
            }
        };

        $blocks = new ScheduleTickerBlocks(static fn (): Schedule => $schedule);

        $this->assertSame([], $blocks->build_current_fm([], 'tv1'));
        $this->assertSame([], $blocks->build_today_tv([], 'tv1'));
    }

    public function test_reuses_one_schedule_for_all_ticker_types(): void
    {
        $calls = 0;
        $schedule = new class () implements Schedule {
            public function get_current_radio_broadcast(): null
            {
                return null;
            }

            public function get_next_radio_broadcast(): null
            {
                return null;
            }

            public function get_today(): object
            {
                return (object) ['television' => []];
            }

            public function get_tomorrow(): object
            {
                return (object) ['television' => []];
            }
        };
        $blocks = new ScheduleTickerBlocks(static function () use (&$calls, $schedule): Schedule {
            ++$calls;
            return $schedule;
        });

        $blocks->build_current_fm([], 'tv1');
        $blocks->build_next_fm([], 'tv1');

        $this->assertSame(1, $calls);
    }

    public function test_does_not_retry_a_failed_schedule_factory(): void
    {
        Actions\expectDone('teksttv_wp_extensions_schedule_error')
            ->once()
            ->with(\Mockery::type(RuntimeException::class));
        $calls = 0;
        $blocks = new ScheduleTickerBlocks(static function () use (&$calls): Schedule {
            ++$calls;
            throw new RuntimeException('Schedule unavailable');
        });

        $this->assertSame([], $blocks->build_current_fm([], 'tv1'));
        $this->assertSame([], $blocks->build_next_fm([], 'tv1'));
        $this->assertSame(1, $calls);
    }

    public function test_registry_save_and_render_callbacks_follow_the_upstream_contract(): void
    {
        $blocks = new ScheduleTickerBlocks(static fn (): Schedule => $this->empty_schedule());
        $blocks->register();

        foreach (BlockRegistry::$types as $type) {
            $this->assertSame([], ($type['save'])(['ignored' => 'value']));

            ob_start();
            ($type['render'])(0, [], 'teksttv_ticker');
            $this->assertSame('', ob_get_clean());
        }
    }

    public function test_reports_schedule_errors_and_returns_no_messages(): void
    {
        $schedule = new class () implements Schedule {
            public function get_current_radio_broadcast(): never
            {
                throw new RuntimeException('Current programme unavailable');
            }

            public function get_next_radio_broadcast(): null
            {
                return null;
            }

            public function get_today(): object
            {
                return (object) ['television' => []];
            }

            public function get_tomorrow(): object
            {
                return (object) ['television' => []];
            }
        };
        Actions\expectDone('teksttv_wp_extensions_schedule_error')
            ->once()
            ->with(\Mockery::type(RuntimeException::class));

        $blocks = new ScheduleTickerBlocks(static fn (): Schedule => $schedule);

        $this->assertSame([], $blocks->build_current_fm([], 'tv1'));
    }

    public function test_rejects_an_invalid_schedule_factory_result_once(): void
    {
        Actions\expectDone('teksttv_wp_extensions_schedule_error')
            ->once()
            ->with(\Mockery::type(\UnexpectedValueException::class));
        $calls = 0;
        $blocks = new ScheduleTickerBlocks(static function () use (&$calls): object {
            ++$calls;
            return new \stdClass();
        });

        $this->assertSame([], $blocks->build_current_fm([], 'tv1'));
        $this->assertSame([], $blocks->build_next_fm([], 'tv1'));
        $this->assertSame(1, $calls);
    }

    public function test_ignores_malformed_television_schedule_data(): void
    {
        $schedule = new class () implements Schedule {
            public function get_current_radio_broadcast(): null
            {
                return null;
            }

            public function get_next_radio_broadcast(): null
            {
                return null;
            }

            public function get_today(): string
            {
                return 'invalid';
            }

            public function get_tomorrow(): object
            {
                return (object) [
                    'television' => [null, (object) ['name' => []]],
                ];
            }
        };
        $blocks = new ScheduleTickerBlocks(static fn (): Schedule => $schedule);

        $this->assertSame([], $blocks->build_today_tv([], 'tv1'));
        $this->assertSame([], $blocks->build_tomorrow_tv([], 'tv1'));
    }

    private function empty_schedule(): Schedule
    {
        return new class () implements Schedule {
            public function get_current_radio_broadcast(): null
            {
                return null;
            }

            public function get_next_radio_broadcast(): null
            {
                return null;
            }

            public function get_today(): object
            {
                return (object) ['television' => []];
            }

            public function get_tomorrow(): object
            {
                return (object) ['television' => []];
            }
        };
    }
}
