<?php
/**
 * Schedule abstraction for the Streekomroep theme integration.
 *
 * @package TekstTV_WP_Extensions
 */

declare(strict_types=1);

namespace ZuidWest\TekstTVExtensions;

/**
 * Provides the schedule data used by the ticker builders.
 */
interface Schedule
{
    /**
     * Return the current radio broadcast, when available.
     */
    public function get_current_radio_broadcast(): mixed;

    /**
     * Return the next radio broadcast, when available.
     */
    public function get_next_radio_broadcast(): mixed;

    /**
     * Return today's schedule.
     */
    public function get_today(): mixed;

    /**
     * Return tomorrow's schedule.
     */
    public function get_tomorrow(): mixed;
}
