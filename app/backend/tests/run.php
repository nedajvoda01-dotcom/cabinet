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
use Cabinet\Backend\Tests\Unit\Application\HandlersTest;
use Cabinet\Backend\Tests\Unit\Application\PipelineHandlersTest;
use Cabinet\Backend\Tests\Unit\Application\IntegrationResultTest;
use Cabinet\Backend\Tests\Unit\Application\Observability\RedactorTest;
use Cabinet\Backend\Tests\ApplicationEndpointsTest;
use Cabinet\Backend\Tests\PipelineEndpointsTest;
use Cabinet\Backend\Tests\Integration\SqlitePersistenceTest;
use Cabinet\Backend\Tests\Integration\TaskOutputsRepositoryTest;
use Cabinet\Backend\Tests\Integration\PipelineTickIntegrationTest;
use Cabinet\Backend\Tests\Integration\JobQueueTest;
use Cabinet\Backend\Tests\Integration\WorkerIntegrationTest;
use Cabinet\Backend\Tests\Integration\AuditTrailIntegrationTest;

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
    new HandlersTest(),
    new PipelineHandlersTest(),
    new IntegrationResultTest(),
    new RedactorTest(),
    new ApplicationEndpointsTest(),
    new PipelineEndpointsTest(),
    new SqlitePersistenceTest(),
    new TaskOutputsRepositoryTest(),
    new PipelineTickIntegrationTest(),
    new JobQueueTest(),
    new WorkerIntegrationTest(),
    new AuditTrailIntegrationTest(),
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
