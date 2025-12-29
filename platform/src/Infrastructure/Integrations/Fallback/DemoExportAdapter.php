<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Integrations\Fallback;

use Cabinet\Backend\Application\Integrations\ExportIntegration;
use Cabinet\Backend\Application\Shared\IntegrationResult;
use Cabinet\Backend\Domain\Tasks\TaskId;

final class DemoExportAdapter implements ExportIntegration
{
    public function run(TaskId $taskId): IntegrationResult
    {
        $payload = [
            'exported' => true,
            'url' => 'demo://export/task/' . $taskId->toString(),
        ];

        return IntegrationResult::succeeded($payload);
    }
}
