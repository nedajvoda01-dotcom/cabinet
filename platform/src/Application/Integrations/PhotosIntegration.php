<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Integrations;

use Cabinet\Backend\Application\Shared\IntegrationResult;
use Cabinet\Backend\Domain\Tasks\TaskId;

interface PhotosIntegration
{
    public function run(TaskId $taskId): IntegrationResult;
}
