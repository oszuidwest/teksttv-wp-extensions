<?php
/**
 * WordPress hook registration and dependency handling.
 *
 * @package TekstTV_WP_Extensions
 */

declare(strict_types=1);

namespace ZuidWest\TekstTVExtensions;

/**
 * Boots the plugin and coordinates dependency handling.
 */
final class Plugin
{
    /**
     * Shared ticker block registrar.
     *
     * @var ScheduleTickerBlocks|null
     */
    private static ?ScheduleTickerBlocks $ticker_blocks = null;

    /**
     * Register the plugin's WordPress hooks.
     */
    public static function init(): void
    {
        add_action('init', [self::class, 'register_ticker_blocks'], 10);
        add_action('admin_notices', [self::class, 'render_dependency_notice']);
    }

    /**
     * Register ticker blocks when both runtime dependencies are available.
     */
    public static function register_ticker_blocks(): void
    {
        if (!class_exists(\TekstTV\BlockRegistry::class) || !class_exists(\Streekomroep\BroadcastSchedule::class)) {
            return;
        }

        self::$ticker_blocks ??= new ScheduleTickerBlocks();
        self::$ticker_blocks->register();
    }

    /**
     * Render an administrator notice when a runtime dependency is missing.
     */
    public static function render_dependency_notice(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        $missing = [];
        if (!class_exists(\TekstTV\BlockRegistry::class)) {
            $missing[] = __('TekstTV', 'teksttv-wp-extensions');
        }
        if (!class_exists(\Streekomroep\BroadcastSchedule::class)) {
            $missing[] = __('the Streekomroep theme', 'teksttv-wp-extensions');
        }

        if ($missing === []) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: %s: comma-separated list of missing dependencies. */
                    __('TekstTV Streekomroep Extensions is inactive. Missing dependencies: %s.', 'teksttv-wp-extensions'),
                    implode(', ', $missing)
                )
            )
        );
    }
}
