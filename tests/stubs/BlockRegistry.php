<?php

declare(strict_types=1);

namespace TekstTV;

if (!class_exists(BlockRegistry::class)) {
    final class BlockRegistry
    {
        /** @var array<string, array<string, mixed>> */
        public static array $types = [];

        /** @param array<string, mixed> $args */
        public static function register(string $slug, array $args): void
        {
            self::$types[$slug] = $args;
        }
    }
}
