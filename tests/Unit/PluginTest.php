<?php

declare(strict_types=1);

namespace ZuidWest\TekstTVExtensions\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Streekomroep\BroadcastSchedule;
use TekstTV\BlockRegistry;
use ZuidWest\TekstTVExtensions\Plugin;

final class PluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $ticker_blocks = new ReflectionProperty(Plugin::class, 'ticker_blocks');
        $ticker_blocks->setValue(null, null);
        BlockRegistry::$types = [];
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_init_registers_hooks_at_the_expected_priorities(): void
    {
        Actions\expectAdded('init')
            ->with([Plugin::class, 'register_ticker_blocks'], 10)
            ->once();
        Actions\expectAdded('admin_notices')
            ->with([Plugin::class, 'render_dependency_notice'])
            ->once();

        Plugin::init();
        $this->addToAssertionCount(2);
    }

    public function test_registers_ticker_blocks_when_dependencies_are_available(): void
    {
        Plugin::register_ticker_blocks();

        $this->assertCount(4, BlockRegistry::$types);
    }

    public function test_dependency_notice_is_hidden_from_unauthorised_users(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('activate_plugins')
            ->andReturn(false);

        ob_start();
        Plugin::render_dependency_notice();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function test_dependency_notice_is_empty_when_dependencies_are_available(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('activate_plugins')
            ->andReturn(true);

        $this->assertTrue(class_exists(BlockRegistry::class));
        $this->assertTrue(class_exists(BroadcastSchedule::class));

        ob_start();
        Plugin::render_dependency_notice();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }
}
