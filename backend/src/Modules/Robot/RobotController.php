<?php
declare(strict_types=1);

namespace Modules\Robot;

final class RobotController
{
    public function __construct(
        private RobotService $service
    ) {}

    /**
     * GET /api/robot/runs/{id}
     */
    public function getRun(int $id): array
    {
        return $this->service->getRunStatus($id);
    }

    /**
     * POST /api/robot/runs/{id}/retry
     */
    public function retryRun(int $id): array
    {
        return $this->service->retryRun($id);
    }

    /**
     * POST /api/robot/sync
     * body: { filter?: {...} }
     */
    public function sync(array $body = []): array
    {
        return $this->service->syncStatuses($body['filter'] ?? []);
    }

    /**
     * GET /api/robot/health
     */
    public function health(): array
    {
        // minimal health — adapters connectivity must be checked in real impl
        return [
            'ok' => true,
            'module' => 'robot',
            'checked_at' => date('c'),
        ];
    }
}
