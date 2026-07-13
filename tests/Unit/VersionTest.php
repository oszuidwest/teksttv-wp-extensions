<?php

declare(strict_types=1);

namespace ZuidWest\TekstTVExtensions\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    private function plugin_version(): string
    {
        $plugin = file_get_contents(dirname(__DIR__, 2) . '/teksttv-wp-extensions.php');

        $this->assertIsString($plugin);
        $this->assertMatchesRegularExpression('/^[ \t*]*Version:[ \t]*(.+)$/mi', $plugin);
        preg_match('/^[ \t*]*Version:[ \t]*(.+)$/mi', $plugin, $version_match);

        return trim($version_match[1]);
    }

    public function test_plugin_header_defines_the_expected_release_metadata(): void
    {
        $plugin = file_get_contents(dirname(__DIR__, 2) . '/teksttv-wp-extensions.php');

        $this->assertIsString($plugin);
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)\.\d+)?$/',
            $this->plugin_version()
        );
        $this->assertStringContainsString('Text Domain: teksttv-wp-extensions', $plugin);
        $this->assertStringContainsString('Requires at least: 7.0', $plugin);
        $this->assertStringContainsString('Requires PHP: 8.3', $plugin);
    }

    public function test_composer_package_and_plugin_slug_stay_aligned(): void
    {
        $composer = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        $this->assertIsArray($composer);
        $this->assertSame('oszuidwest/teksttv-wp-extensions', $composer['name'] ?? null);
        $this->assertFileExists(dirname(__DIR__, 2) . '/teksttv-wp-extensions.php');
    }

    public function test_translation_template_follows_the_header_version(): void
    {
        $pot = file_get_contents(dirname(__DIR__, 2) . '/languages/teksttv-wp-extensions.pot');

        $this->assertIsString($pot);
        $this->assertStringContainsString(
            'Project-Id-Version: TekstTV Streekomroep Extensions ' . $this->plugin_version() . '\\n',
            $pot
        );
        $this->assertStringContainsString('X-Domain: teksttv-wp-extensions\\n', $pot);
    }
}
