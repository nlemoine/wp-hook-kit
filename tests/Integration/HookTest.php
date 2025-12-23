<?php

declare(strict_types=1);

namespace n5s\WpHookKit\Tests\Integration;

use n5s\WpHookKit\Hook;

class HookTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up any hooks we've added
        remove_all_filters('test_filter');
        remove_all_filters('test_filter_1');
        remove_all_filters('test_filter_2');
        remove_all_filters('test_filter_3');
        remove_all_actions('test_action');
        remove_all_actions('test_action_1');
        remove_all_actions('test_action_2');
        remove_all_actions('test_action_3');
    }

    public function testAddFilter(): void
    {
        Hook::addFilter('test_filter', static fn ($value) => $value . '_filtered');

        $result = apply_filters('test_filter', 'original');

        $this->assertSame('original_filtered', $result);
    }

    public function testAddFilterWithPriority(): void
    {
        Hook::addFilter('test_filter', static fn ($value) => $value . '_first', 5);
        Hook::addFilter('test_filter', static fn ($value) => $value . '_second', 15);

        $result = apply_filters('test_filter', 'original');

        $this->assertSame('original_first_second', $result);
    }

    public function testAddFilterWithMultipleArgs(): void
    {
        Hook::addFilter(
            'test_filter',
            static fn ($value, $arg1, $arg2) => $value . "_{$arg1}_{$arg2}",
            10,
            3
        );

        $result = apply_filters('test_filter', 'original', 'foo', 'bar');

        $this->assertSame('original_foo_bar', $result);
    }

    public function testAddFilters(): void
    {
        $callback = static fn ($value) => $value . '_filtered';

        Hook::addFilters(['test_filter_1', 'test_filter_2', 'test_filter_3'], $callback);

        $this->assertSame('value1_filtered', apply_filters('test_filter_1', 'value1'));
        $this->assertSame('value2_filtered', apply_filters('test_filter_2', 'value2'));
        $this->assertSame('value3_filtered', apply_filters('test_filter_3', 'value3'));
    }

    public function testAddFilterOnceRunsOnlyOnce(): void
    {
        $counter = 0;

        Hook::addFilterOnce('test_filter', static function ($value) use (&$counter) {
            $counter++;

            return $value . '_filtered';
        });

        $result1 = apply_filters('test_filter', 'first');
        $result2 = apply_filters('test_filter', 'second');
        $result3 = apply_filters('test_filter', 'third');

        $this->assertSame('first_filtered', $result1);
        $this->assertSame('second', $result2);
        $this->assertSame('third', $result3);
        $this->assertSame(1, $counter);
    }

    public function testAddFilterSideEffectPassesValueThrough(): void
    {
        $captured = null;

        Hook::addFilterSideEffect('test_filter', static function ($value) use (&$captured) {
            $captured = $value;

            return 'this_should_be_ignored';
        });

        $result = apply_filters('test_filter', 'original');

        $this->assertSame('original', $result);
        $this->assertSame('original', $captured);
    }

    public function testAddFilterSideEffectWithMultipleArgs(): void
    {
        $capturedArgs = [];

        Hook::addFilterSideEffect(
            'test_filter',
            static function ($value, $arg1, $arg2) use (&$capturedArgs) {
                $capturedArgs = [$value, $arg1, $arg2];
            },
            10,
            3
        );

        $result = apply_filters('test_filter', 'original', 'foo', 'bar');

        $this->assertSame('original', $result);
        $this->assertSame(['original', 'foo', 'bar'], $capturedArgs);
    }

    public function testAddFilterSideEffectOnce(): void
    {
        $counter = 0;

        Hook::addFilterSideEffectOnce('test_filter', static function ($value) use (&$counter) {
            $counter++;
        });

        $result1 = apply_filters('test_filter', 'first');
        $result2 = apply_filters('test_filter', 'second');

        $this->assertSame('first', $result1);
        $this->assertSame('second', $result2);
        $this->assertSame(1, $counter);
    }

    public function testAddAction(): void
    {
        $executed = false;

        Hook::addAction('test_action', static function () use (&$executed) {
            $executed = true;
        });

        do_action('test_action');

        $this->assertTrue($executed);
    }

    public function testAddActionWithPriority(): void
    {
        $order = [];

        Hook::addAction('test_action', static function () use (&$order) {
            $order[] = 'second';
        }, 15);

        Hook::addAction('test_action', static function () use (&$order) {
            $order[] = 'first';
        }, 5);

        do_action('test_action');

        $this->assertSame(['first', 'second'], $order);
    }

    public function testAddActionWithArgs(): void
    {
        $capturedArgs = [];

        Hook::addAction(
            'test_action',
            static function ($arg1, $arg2) use (&$capturedArgs) {
                $capturedArgs = [$arg1, $arg2];
            },
            10,
            2
        );

        do_action('test_action', 'foo', 'bar');

        $this->assertSame(['foo', 'bar'], $capturedArgs);
    }

    public function testAddActions(): void
    {
        $executedHooks = [];

        $callback = static function () use (&$executedHooks) {
            $executedHooks[] = current_action();
        };

        Hook::addActions(['test_action_1', 'test_action_2', 'test_action_3'], $callback);

        do_action('test_action_1');
        do_action('test_action_2');
        do_action('test_action_3');

        $this->assertSame(['test_action_1', 'test_action_2', 'test_action_3'], $executedHooks);
    }

    public function testAddActionOnceRunsOnlyOnce(): void
    {
        $counter = 0;

        Hook::addActionOnce('test_action', static function () use (&$counter) {
            $counter++;
        });

        do_action('test_action');
        do_action('test_action');
        do_action('test_action');

        $this->assertSame(1, $counter);
    }

    public function testAddActionOnceWithArgs(): void
    {
        $capturedArgs = [];

        Hook::addActionOnce(
            'test_action',
            static function ($arg1, $arg2) use (&$capturedArgs) {
                $capturedArgs = [$arg1, $arg2];
            },
            10,
            2
        );

        do_action('test_action', 'foo', 'bar');
        do_action('test_action', 'baz', 'qux');

        $this->assertSame(['foo', 'bar'], $capturedArgs);
    }

    public function testAddFilterReturnsTrue(): void
    {
        $result = Hook::addFilter('test_filter', static fn ($v) => $v);

        $this->assertTrue($result);
    }

    public function testAddActionReturnsTrue(): void
    {
        $result = Hook::addAction('test_action', static fn () => null);

        $this->assertTrue($result);
    }

    public function testFilterOnceWithPriority(): void
    {
        $results = [];

        Hook::addFilter('test_filter', static function ($value) use (&$results) {
            $results[] = 'always';

            return $value;
        }, 5);

        Hook::addFilterOnce('test_filter', static function ($value) use (&$results) {
            $results[] = 'once';

            return $value;
        }, 10);

        apply_filters('test_filter', 'value');
        apply_filters('test_filter', 'value');

        $this->assertSame(['always', 'once', 'always'], $results);
    }

    public function testActionOnceWithPriority(): void
    {
        $results = [];

        Hook::addAction('test_action', static function () use (&$results) {
            $results[] = 'always';
        }, 5);

        Hook::addActionOnce('test_action', static function () use (&$results) {
            $results[] = 'once';
        }, 10);

        do_action('test_action');
        do_action('test_action');

        $this->assertSame(['always', 'once', 'always'], $results);
    }

    public function testAddFilterWithClassMethodCallback(): void
    {
        $handler = new class {
            public function filter(string $value): string
            {
                return $value . '_from_method';
            }
        };

        Hook::addFilter('test_filter', [$handler, 'filter']);

        $result = apply_filters('test_filter', 'original');

        $this->assertSame('original_from_method', $result);
    }

    public function testAddActionWithClassMethodCallback(): void
    {
        $handler = new class {
            public bool $executed = false;

            public function action(): void
            {
                $this->executed = true;
            }
        };

        Hook::addAction('test_action', [$handler, 'action']);

        do_action('test_action');

        $this->assertTrue($handler->executed);
    }

    public function testHasFilterReturnsPriority(): void
    {
        $callback = static fn ($v) => $v;

        Hook::addFilter('test_filter', $callback, 15);

        $this->assertSame(15, has_filter('test_filter', $callback));
    }

    public function testHasActionReturnsPriority(): void
    {
        $callback = static fn () => null;

        Hook::addAction('test_action', $callback, 20);

        $this->assertSame(20, has_action('test_action', $callback));
    }

    public function testRemoveFilterWorks(): void
    {
        $callback = static fn ($v) => $v . '_filtered';

        Hook::addFilter('test_filter', $callback);

        $this->assertSame('original_filtered', apply_filters('test_filter', 'original'));

        remove_filter('test_filter', $callback);

        $this->assertSame('original', apply_filters('test_filter', 'original'));
        $this->assertFalse(has_filter('test_filter', $callback));
    }

    public function testRemoveActionWorks(): void
    {
        $counter = 0;
        $callback = static function () use (&$counter) {
            $counter++;
        };

        Hook::addAction('test_action', $callback);

        do_action('test_action');
        $this->assertSame(1, $counter);

        remove_action('test_action', $callback);

        do_action('test_action');
        $this->assertSame(1, $counter);
        $this->assertFalse(has_action('test_action', $callback));
    }

    public function testFilterChaining(): void
    {
        Hook::addFilter('test_filter', static fn (int $v) => $v + 1);
        Hook::addFilter('test_filter', static fn (int $v) => $v + 1);
        Hook::addFilter('test_filter', static fn (int $v) => $v * 2);

        // (1 + 1 + 1) * 2 = 6
        $result = apply_filters('test_filter', 1);

        $this->assertSame(6, $result);
    }

    public function testRemoveClassMethodCallback(): void
    {
        $handler = new class {
            public function filter(string $value): string
            {
                return $value . '_filtered';
            }
        };

        Hook::addFilter('test_filter', [$handler, 'filter']);

        $this->assertSame('original_filtered', apply_filters('test_filter', 'original'));
        $this->assertSame(10, has_filter('test_filter', [$handler, 'filter']));

        remove_filter('test_filter', [$handler, 'filter']);

        $this->assertSame('original', apply_filters('test_filter', 'original'));
        $this->assertFalse(has_filter('test_filter', [$handler, 'filter']));
    }
}
