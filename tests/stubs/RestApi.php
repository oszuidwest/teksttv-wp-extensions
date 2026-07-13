<?php

declare(strict_types=1);

namespace TekstTV;

if (!class_exists(RestApi::class)) {
    final class RestApi
    {
        public static int $invalidations = 0;

        public static function invalidate_slides_cache(string $channel = ''): void
        {
            ++self::$invalidations;
        }
    }
}
