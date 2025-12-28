<?php

declare(strict_types=1);

use Cabinet\Backend\Tests\ErrorHandlingTest;
use Cabinet\Backend\Tests\HealthEndpointTest;
use Cabinet\Backend\Tests\ReadinessEndpointTest;
use Cabinet\Backend\Tests\RequestIdTest;
use Cabinet\Backend\Tests\SecurityPipelineTest;
use Cabinet\Backend\Tests\VersionEndpointTest;
use Cabinet\Backend\Tests\Unit\Domain\IdentifierTest;
use Cabinet\Backend\Tests\Unit\Domain\ScopeTest;
use Cabinet\Backend\Tests\Unit\Domain\ScopeSetTest;
use Cabinet\Backend\Tests\Unit\Domain\HierarchyRoleTest;
use Cabinet\Backend\Tests\Unit\Domain\AccessRequestTest;
use Cabinet\Backend\Tests\Unit\Domain\UserTest;
use Cabinet\Backend\Tests\Unit\Domain\TaskTest;
use Cabinet\Backend\Tests\Unit\Domain\PipelineStateTest;
use Cabinet\Backend\Tests\Unit\Application\ApplicationInfrastructureTest;

require __DIR__ . '/../../../vendor/autoload.php';

$tests = [
    new HealthEndpointTest(),
    new ReadinessEndpointTest(),
    new VersionEndpointTest(),
    new RequestIdTest(),
    new ErrorHandlingTest(),
    new SecurityPipelineTest(),
    new IdentifierTest(),
    new ScopeTest(),
    new ScopeSetTest(),
    new HierarchyRoleTest(),
    new AccessRequestTest(),
    new UserTest(),
    new TaskTest(),
    new PipelineStateTest(),
    new ApplicationInfrastructureTest(),
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
