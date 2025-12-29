<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Integrations\Fallback;

use Cabinet\Backend\Application\Integrations\ParserIntegration;
use Cabinet\Backend\Application\Shared\IntegrationResult;
use Cabinet\Backend\Domain\Tasks\TaskId;

final class DemoParserAdapter implements ParserIntegration
{
    public function run(TaskId $taskId): IntegrationResult
    {
        $payload = [
            'source' => 'demo',
            'items' => 3,
            'sample' => [
                ['id' => 'demo-1'],
                ['id' => 'demo-2'],
                ['id' => 'demo-3'],
            ],
        ];

        return IntegrationResult::succeeded($payload);
    }
}
