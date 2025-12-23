# WP Hook Kit

[![Tests](https://img.shields.io/github/actions/workflow/status/nlemoine/wp-hook-kit/tests.yml?branch=main&label=tests)](https://github.com/nlemoine/wp-hook-kit/actions/workflows/tests.yml)
[![Coverage](https://img.shields.io/codecov/c/github/nlemoine/wp-hook-kit)](https://codecov.io/gh/nlemoine/wp-hook-kit)
[![PHPStan](https://img.shields.io/badge/PHPStan-max-brightgreen)](https://phpstan.org/)

A lightweight WordPress hook helper library. Register hooks before WordPress loads, run callbacks only once, and more.

## Installation

```bash
composer require n5s/wp-hook-kit
```

## Usage

```php
use n5s\WpHookKit\Hook;

// Basic usage - works even before WordPress is loaded
Hook::addFilter('the_content', fn($content) => $content . '<p>Footer</p>');
Hook::addAction('init', fn() => register_post_type('book', []));

// Run a callback only once (removes itself after first execution)
Hook::addFilterOnce('the_title', fn($title) => $title . ' - Launch Sale!');
Hook::addActionOnce('wp_footer', fn() => echo '<!-- First visit -->');

// Side effects - run code without modifying the filtered value
Hook::addFilterSideEffect('the_content', function($content) {
    error_log('Content rendered: ' . strlen($content) . ' chars');
});

// Combine both: side effect that runs once
Hook::addFilterSideEffectOnce('template_include', function($template) {
    log_first_template_load($template);
});

// Register same callback on multiple hooks
Hook::addFilters(['the_title', 'the_content'], 'esc_html');
Hook::addActions(['wp_head', 'wp_footer'], fn() => do_something());
```

All methods accept the standard WordPress parameters: `$hook`, `$callback`, `$priority = 10`, `$accepted_args = 1`.

## Why?

**Early registration**: Register hooks before WordPress fully loads. The library writes directly to `$wp_filter` when `add_filter()` isn't available yet. Unlocks the power of bringing features without the "plugin" way hassle when it's not needed (library, composer autoloaded files, etc.).

**Once variants**: Because sometimes, you might not want your callback to be executed every time a hook is called.

**Side effects**: Runs your callback and returns the original value unchanged. Useful to trigger actions when an actual action isn't available, observe filter behavior, etc.

## Acknowledgments

This package is gathering multiple package implementations inside a single library, credits goes to:

- [wecodemore/wordpress-early-hook](https://github.com/wecodemore/wordpress-early-hook) — Early hook registration. The only downside is that it can't safely be used inside a library (because Composer doesn't guarantee autoloaded files order). Plus some minor performance improvements.
- [stevegrunwell/one-time-callbacks](https://github.com/stevegrunwell/one-time-callbacks) — Once variants
- [alleyinteractive/wp-filter-side-effects](https://github.com/alleyinteractive/wp-filter-side-effects) — Side effects

## License

MIT
