<?php

declare(strict_types=1);

use Cabinet\Backend\Tests\ErrorHandlingTest;
use Cabinet\Backend\Tests\HealthEndpointTest;
use Cabinet\Backend\Tests\ReadinessEndpointTest;
use Cabinet\Backend\Tests\RequestIdTest;
use Cabinet\Backend\Tests\SecurityPipelineTest;
use Cabinet\Backend\Tests\VersionEndpointTest;

require __DIR__ . '/../../../vendor/autoload.php';

$tests = [
    new HealthEndpointTest(),
    new ReadinessEndpointTest(),
    new VersionEndpointTest(),
    new RequestIdTest(),
    new ErrorHandlingTest(),
    new SecurityPipelineTest(),
];

$failures = 0;
foreach ($tests as $test) {
    try {
        $methods = $test->run();
        echo sprintf("[PASS] %s (%d tests)\n", get_class($test), count($methods));
    } catch (Throwable $throwable) {
        $failures++;
        echo sprintf("[FAIL] %s: %s\n", get_class($test), $throwable->getMessage());
    }
}

if ($failures > 0) {
    exit(1);
}
