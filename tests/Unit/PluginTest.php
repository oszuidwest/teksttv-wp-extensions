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
use TekstTV\RestApi;
use ZuidWest\TekstTVExtensions\Plugin;

final class PluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $cache_invalidated = new ReflectionProperty(Plugin::class, 'cache_invalidated');
        $cache_invalidated->setValue(null, false);
        $ticker_blocks = new ReflectionProperty(Plugin::class, 'ticker_blocks');
        $ticker_blocks->setValue(null, null);
        BlockRegistry::$types = [];
        RestApi::$invalidations = 0;
        Functions\when('__')->returnArg();
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
        Actions\expectAdded('save_post_fm')
            ->with([Plugin::class, 'invalidate_slides_cache'])
            ->once();
        Actions\expectAdded('save_post_tv')
            ->with([Plugin::class, 'invalidate_slides_cache'])
            ->once();
        Actions\expectAdded('acf/save_post')
            ->with([Plugin::class, 'maybe_invalidate_options_cache'], 20)
            ->once();

        Plugin::init();
        $this->addToAssertionCount(5);
    }

    public function test_acf_options_save_invalidates_all_channel_caches_once(): void
    {
        Plugin::maybe_invalidate_options_cache(123);
        Plugin::maybe_invalidate_options_cache('post_123');
        $this->assertSame(0, RestApi::$invalidations);

        Plugin::maybe_invalidate_options_cache('options');
        Plugin::maybe_invalidate_options_cache('option');
        $this->assertSame(1, RestApi::$invalidations);
    }

    public function test_multiple_post_saves_in_one_request_invalidate_once(): void
    {
        Plugin::invalidate_slides_cache();
        Plugin::invalidate_slides_cache();

        $this->assertSame(1, RestApi::$invalidations);
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
