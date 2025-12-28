<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Queries;

use Cabinet\Backend\Application\Ports\TaskOutputRepository;
use Cabinet\Backend\Application\Shared\ApplicationError;
use Cabinet\Backend\Application\Shared\Result;
use Cabinet\Backend\Domain\Tasks\TaskId;

final class GetTaskOutputsQuery
{
    public function __construct(
        private readonly TaskOutputRepository $taskOutputRepository
    ) {
    }

    /**
     * @return Result<array<string, mixed>>
     */
    public function execute(string $taskId): Result
    {
        try {
            $id = TaskId::fromString($taskId);
            $outputs = $this->taskOutputRepository->read($id);

            return Result::success(['outputs' => $outputs]);
        } catch (\Exception $e) {
            return Result::failure(ApplicationError::validationError($e->getMessage()));
        }
    }
}
