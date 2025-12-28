<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Integrations\Fallback;

use Cabinet\Backend\Application\Integrations\CleanupIntegration;
use Cabinet\Backend\Application\Shared\IntegrationResult;
use Cabinet\Backend\Domain\Tasks\TaskId;

final class DemoCleanupAdapter implements CleanupIntegration
{
    public function run(TaskId $taskId): IntegrationResult
    {
        $payload = [
            'cleaned' => true,
        ];

        return IntegrationResult::succeeded($payload);
    }
}
