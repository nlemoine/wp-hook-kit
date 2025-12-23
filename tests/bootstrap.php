<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Skip WordPress loading for unit tests
if (getenv('TEST_SUITE') === 'unit') {
    return;
}

use function Mantle\Testing\manager;

$rootDir = realpath(__DIR__ . '/..');

// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
putenv("WP_CORE_DIR=$rootDir/tmp/wordpress");

manager()->install();
