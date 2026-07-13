<?php

declare(strict_types=1);

namespace ZuidWest\TekstTVExtensions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Streekomroep\BroadcastSchedule;
use ZuidWest\TekstTVExtensions\ThemeSchedule;

final class ThemeScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        BroadcastSchedule::$current = (object) ['name' => 'Current'];
        BroadcastSchedule::$next = (object) ['name' => 'Next'];
        BroadcastSchedule::$today = (object) ['television' => ['Today']];
        BroadcastSchedule::$tomorrow = (object) ['television' => ['Tomorrow']];
    }

    public function test_delegates_every_schedule_query_to_the_theme_schedule(): void
    {
        $schedule = new ThemeSchedule();

        $this->assertSame(BroadcastSchedule::$current, $schedule->get_current_radio_broadcast());
        $this->assertSame(BroadcastSchedule::$next, $schedule->get_next_radio_broadcast());
        $this->assertSame(BroadcastSchedule::$today, $schedule->get_today());
        $this->assertSame(BroadcastSchedule::$tomorrow, $schedule->get_tomorrow());
    }
}
