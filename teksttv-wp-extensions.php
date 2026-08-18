<?php
/**
 * Plugin Name: TekstTV Streekomroep Extensions
 * Plugin URI: https://github.com/oszuidwest/teksttv-wp-extensions
 * Description: Voegt tickerberichten uit de radio- en televisieprogrammering van het Streekomroep-thema toe aan TekstTV.
 * Version: 0.0.2
 * Author: ZuidWest
 * Author URI: https://www.zuidwesttv.nl/
 * License: GPL-2.0-or-later
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
