<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Integrations\Fallback;

use Cabinet\Backend\Application\Integrations\PublisherIntegration;
use Cabinet\Backend\Application\Shared\IntegrationResult;
use Cabinet\Backend\Domain\Tasks\TaskId;

final class DemoPublisherAdapter implements PublisherIntegration
{
    public function run(TaskId $taskId): IntegrationResult
    {
        $payload = [
            'published' => true,
            'external_id' => 'demo-123',
        ];

        return IntegrationResult::succeeded($payload);
    }
}
