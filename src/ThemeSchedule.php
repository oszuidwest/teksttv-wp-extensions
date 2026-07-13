<?php
/**
 * Adapter for the schedule supplied by the Streekomroep theme.
 *
 * @package TekstTV_WP_Extensions
 */

declare(strict_types=1);

namespace ZuidWest\TekstTVExtensions;

/**
 * Delegates schedule queries to the active Streekomroep theme.
 */
final class ThemeSchedule implements Schedule
{
    /**
     * Theme schedule instance.
     *
     * @var \Streekomroep\BroadcastSchedule
     */
    private \Streekomroep\BroadcastSchedule $schedule;

    /**
     * Create the theme schedule adapter.
     */
    public function __construct()
    {
        $this->schedule = new \Streekomroep\BroadcastSchedule();
    }

    /**
     * Return the current radio broadcast.
     */
    public function get_current_radio_broadcast(): mixed
    {
        return $this->schedule->getCurrentRadioBroadcast();
    }

    /**
     * Return the next radio broadcast.
     */
    public function get_next_radio_broadcast(): mixed
    {
        return $this->schedule->getNextRadioBroadcast();
    }

    /**
     * Return today's schedule.
     */
    public function get_today(): mixed
    {
        return $this->schedule->getToday();
    }

    /**
     * Return tomorrow's schedule.
     */
    public function get_tomorrow(): mixed
    {
        return $this->schedule->getTomorrow();
    }
}
