<?php

declare(strict_types=1);

namespace Streekomroep;

if (!class_exists(BroadcastSchedule::class)) {
    final class BroadcastSchedule
    {
        public static mixed $current = null;

        public static mixed $next = null;

        public static mixed $today = null;

        public static mixed $tomorrow = null;

        public function getCurrentRadioBroadcast(): mixed
        {
            return self::$current;
        }

        public function getNextRadioBroadcast(): mixed
        {
            return self::$next;
        }

        public function getToday(): mixed
        {
            return self::$today;
        }

        public function getTomorrow(): mixed
        {
            return self::$tomorrow;
        }
    }
}
