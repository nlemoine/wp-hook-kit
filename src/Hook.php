<?php

declare(strict_types=1);

namespace n5s\WpHookKit;

class Hook
{
    private static bool $wpLoaded = false;

    /**
     * Add a filter hook, even before WordPress is loaded.
     *
     * @param string   $hook         The name of the filter.
     * @param callable $callback     The callback to run.
     * @param int      $priority     Priority. Default 10.
     * @param int      $acceptedArgs Number of accepted arguments. Default 1.
     * @return bool
     */
    public static function addFilter(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): bool {

        return self::addHook('filter', $hook, $callback, $priority, $acceptedArgs);
    }

    /**
     * Add the same callback to multiple filter hooks.
     *
     * @param array<string> $hooks        The filter names.
     * @param callable      $callback     The callback to run.
     * @param int           $priority     Priority. Default 10.
     * @param int           $acceptedArgs Number of accepted arguments. Default 1.
     */
    public static function addFilters(
        array $hooks,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): void {

        foreach ($hooks as $hook) {
            self::addFilter($hook, $callback, $priority, $acceptedArgs);
        }
    }

    /**
     * Add a filter hook that runs exactly once, even before WordPress is loaded.
     *
     * @param string   $hook         The name of the filter.
     * @param callable $callback     The callback to run.
     * @param int      $priority     Priority. Default 10.
     * @param int      $acceptedArgs Number of accepted arguments. Default 1.
     * @return bool
     */
    public static function addFilterOnce(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): bool {

        $wrapper = null;
        $wrapper = static function (mixed ...$args) use ($hook, $callback, $priority, &$wrapper): mixed {
            remove_filter($hook, $wrapper, $priority);

            return $callback(...$args);
        };

        return self::addHook('filter', $hook, $wrapper, $priority, $acceptedArgs);
    }

    /**
     * Add a filter hook for side effects only (value passes through unchanged).
     *
     * Useful when you need to react to a filter's data without modifying it.
     *
     * @param string   $hook         The name of the filter.
     * @param callable $callback     The callback to run (return value is ignored).
     * @param int      $priority     Priority. Default 10.
     * @param int      $acceptedArgs Number of accepted arguments. Default 1.
     * @return bool
     */
    public static function addFilterSideEffect(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): bool {

        $wrapper = static function (mixed ...$args) use ($callback): mixed {
            $callback(...$args);

            return $args[0];
        };

        return self::addHook('filter', $hook, $wrapper, $priority, $acceptedArgs);
    }

    /**
     * Add a filter hook for side effects that runs exactly once.
     *
     * @param string   $hook         The name of the filter.
     * @param callable $callback     The callback to run (return value is ignored).
     * @param int      $priority     Priority. Default 10.
     * @param int      $acceptedArgs Number of accepted arguments. Default 1.
     * @return bool
     */
    public static function addFilterSideEffectOnce(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): bool {

        $wrapper = null;
        $wrapper = static function (mixed ...$args) use ($hook, $callback, $priority, &$wrapper): mixed {
            remove_filter($hook, $wrapper, $priority);

            $callback(...$args);

            return $args[0];
        };

        return self::addHook('filter', $hook, $wrapper, $priority, $acceptedArgs);
    }

    /**
     * Add an action hook, even before WordPress is loaded.
     *
     * @param string   $hook         The name of the action.
     * @param callable $callback     The callback to run.
     * @param int      $priority     Priority. Default 10.
     * @param int      $acceptedArgs Number of accepted arguments. Default 1.
     * @return bool
     */
    public static function addAction(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): bool {

        return self::addHook('action', $hook, $callback, $priority, $acceptedArgs);
    }

    /**
     * Add the same callback to multiple action hooks.
     *
     * @param array<string> $hooks        The action names.
     * @param callable      $callback     The callback to run.
     * @param int           $priority     Priority. Default 10.
     * @param int           $acceptedArgs Number of accepted arguments. Default 1.
     */
    public static function addActions(
        array $hooks,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): void {

        foreach ($hooks as $hook) {
            self::addAction($hook, $callback, $priority, $acceptedArgs);
        }
    }

    /**
     * Add an action hook that runs exactly once, even before WordPress is loaded.
     *
     * @param string   $hook         The name of the action.
     * @param callable $callback     The callback to run.
     * @param int      $priority     Priority. Default 10.
     * @param int      $acceptedArgs Number of accepted arguments. Default 1.
     * @return bool
     */
    public static function addActionOnce(
        string $hook,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): bool {

        $wrapper = null;
        $wrapper = static function (mixed ...$args) use ($hook, $callback, $priority, &$wrapper): void {
            remove_action($hook, $wrapper, $priority);

            $callback(...$args);
        };

        return self::addHook('action', $hook, $wrapper, $priority, $acceptedArgs);
    }

    /**
     * Internal method to add a hook.
     *
     * @param string   $type         Either 'filter' or 'action'.
     * @param string   $hook         The hook name.
     * @param callable $callback     The callback.
     * @param int      $priority     Priority.
     * @param int      $acceptedArgs Accepted args.
     * @return bool
     */
    private static function addHook(
        string $type,
        string $hook,
        callable $callback,
        int $priority,
        int $acceptedArgs
    ): bool {
        // Fast path: WP is loaded (most common case)
        if (self::$wpLoaded || self::ensureWpLoaded()) {
            return $type === 'filter'
                ? add_filter($hook, $callback, $priority, $acceptedArgs)
                : add_action($hook, $callback, $priority, $acceptedArgs);
        }

        // Early registration fallback
        global $wp_filter;

        /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $wp_filter[$hook] ??= [];
        /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $wp_filter[$hook][$priority] ??= [];
        /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $wp_filter[$hook][$priority][] = [
            'function' => $callback,
            'accepted_args' => $acceptedArgs,
        ];

        return true;
    }

    /**
     * Check if WordPress hook functions are available and cache the result.
     */
    private static function ensureWpLoaded(): bool
    {
        if (function_exists('add_filter')) {
            self::$wpLoaded = true;

            return true;
        }

        if (defined('ABSPATH')) {
            require_once ABSPATH . 'wp-includes/plugin.php';
            self::$wpLoaded = true;

            return true;
        }

        return false;
    }
}
