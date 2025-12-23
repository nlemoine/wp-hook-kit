<?php

declare(strict_types=1);

namespace n5s\WpHookKit\Tests\Unit;

use n5s\WpHookKit\Hook;
use ReflectionClass;

/**
 * Unit tests for Hook that test the fallback behavior
 * when WordPress is not loaded.
 */
class HookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset the static $wpLoaded property before each test
        $reflection = new ReflectionClass(Hook::class);
        $property = $reflection->getProperty('wpLoaded');
        $property->setValue(null, false);

        // Reset global $wp_filter
        $GLOBALS['wp_filter'] = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up global
        unset($GLOBALS['wp_filter']);
    }

    public function testAddFilterFallbackWritesToGlobal(): void
    {
        $callback = static fn ($value) => $value . '_filtered';

        $result = Hook::addFilter('test_hook', $callback, 10, 1);

        $this->assertTrue($result);
        $this->assertArrayHasKey('test_hook', $GLOBALS['wp_filter']);
        $this->assertArrayHasKey(10, $GLOBALS['wp_filter']['test_hook']);
        $this->assertCount(1, $GLOBALS['wp_filter']['test_hook'][10]);
        $this->assertSame($callback, $GLOBALS['wp_filter']['test_hook'][10][0]['function']);
        $this->assertSame(1, $GLOBALS['wp_filter']['test_hook'][10][0]['accepted_args']);
    }

    public function testAddActionFallbackWritesToGlobal(): void
    {
        $callback = static fn () => null;

        $result = Hook::addAction('test_action', $callback, 15, 2);

        $this->assertTrue($result);
        $this->assertArrayHasKey('test_action', $GLOBALS['wp_filter']);
        $this->assertArrayHasKey(15, $GLOBALS['wp_filter']['test_action']);
        $this->assertSame($callback, $GLOBALS['wp_filter']['test_action'][15][0]['function']);
        $this->assertSame(2, $GLOBALS['wp_filter']['test_action'][15][0]['accepted_args']);
    }

    public function testMultipleFiltersOnSameHookAndPriority(): void
    {
        $callback1 = static fn ($v) => $v . '_1';
        $callback2 = static fn ($v) => $v . '_2';

        Hook::addFilter('test_hook', $callback1, 10, 1);
        Hook::addFilter('test_hook', $callback2, 10, 1);

        $this->assertCount(2, $GLOBALS['wp_filter']['test_hook'][10]);
        $this->assertSame($callback1, $GLOBALS['wp_filter']['test_hook'][10][0]['function']);
        $this->assertSame($callback2, $GLOBALS['wp_filter']['test_hook'][10][1]['function']);
    }

    public function testMultipleFiltersWithDifferentPriorities(): void
    {
        $callback1 = static fn ($v) => $v . '_1';
        $callback2 = static fn ($v) => $v . '_2';

        Hook::addFilter('test_hook', $callback1, 5, 1);
        Hook::addFilter('test_hook', $callback2, 15, 1);

        $this->assertArrayHasKey(5, $GLOBALS['wp_filter']['test_hook']);
        $this->assertArrayHasKey(15, $GLOBALS['wp_filter']['test_hook']);
        $this->assertSame($callback1, $GLOBALS['wp_filter']['test_hook'][5][0]['function']);
        $this->assertSame($callback2, $GLOBALS['wp_filter']['test_hook'][15][0]['function']);
    }

    public function testAddFiltersRegistersMultipleHooks(): void
    {
        $callback = static fn ($v) => $v . '_filtered';

        Hook::addFilters(['hook_1', 'hook_2', 'hook_3'], $callback, 10, 1);

        $this->assertArrayHasKey('hook_1', $GLOBALS['wp_filter']);
        $this->assertArrayHasKey('hook_2', $GLOBALS['wp_filter']);
        $this->assertArrayHasKey('hook_3', $GLOBALS['wp_filter']);
    }

    public function testAddActionsRegistersMultipleHooks(): void
    {
        $callback = static fn () => null;

        Hook::addActions(['action_1', 'action_2'], $callback, 10, 1);

        $this->assertArrayHasKey('action_1', $GLOBALS['wp_filter']);
        $this->assertArrayHasKey('action_2', $GLOBALS['wp_filter']);
    }

    public function testFallbackInitializesWpFilterIfNotSet(): void
    {
        unset($GLOBALS['wp_filter']);

        Hook::addFilter('test_hook', static fn ($v) => $v, 10, 1);

        $this->assertIsArray($GLOBALS['wp_filter']);
        $this->assertArrayHasKey('test_hook', $GLOBALS['wp_filter']);
    }

    public function testAcceptedArgsIsStoredCorrectly(): void
    {
        Hook::addFilter('test_hook', static fn ($a, $b, $c) => $a, 10, 3);

        $this->assertSame(3, $GLOBALS['wp_filter']['test_hook'][10][0]['accepted_args']);
    }
}
