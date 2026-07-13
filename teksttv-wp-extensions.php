<?php
/**
 * Plugin Name: TekstTV Streekomroep Extensions
 * Plugin URI: https://github.com/oszuidwest/teksttv-wp-extensions
 * Description: Adds ticker messages from the Streekomroep theme's radio and television schedules to TekstTV.
 * Version: 0.0.1
 * Author: ZuidWest
 * Author URI: https://www.zuidwesttv.nl/
 * License: GPL-2.0-or-later
 * Text Domain: teksttv-wp-extensions
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Requires Plugins: teksttv
 * Update URI: https://github.com/oszuidwest/teksttv-wp-extensions
 *
 * @package TekstTV_WP_Extensions
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Schedule.php';
require_once __DIR__ . '/src/ThemeSchedule.php';
require_once __DIR__ . '/src/ScheduleTickerBlocks.php';
require_once __DIR__ . '/src/Plugin.php';

ZuidWest\TekstTVExtensions\Plugin::init();
