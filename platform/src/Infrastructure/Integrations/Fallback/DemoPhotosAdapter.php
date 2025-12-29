<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Integrations\Fallback;

use Cabinet\Backend\Application\Integrations\PhotosIntegration;
use Cabinet\Backend\Application\Shared\IntegrationResult;
use Cabinet\Backend\Domain\Tasks\TaskId;

final class DemoPhotosAdapter implements PhotosIntegration
{
    public function run(TaskId $taskId): IntegrationResult
    {
        $payload = [
            'processed' => true,
            'count' => 12,
        ];

        return IntegrationResult::succeeded($payload);
    }
}
