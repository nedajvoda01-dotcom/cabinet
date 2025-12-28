<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories;

use Cabinet\Backend\Application\Ports\PipelineStateRepository;
use Cabinet\Backend\Domain\Pipeline\JobId;
use Cabinet\Backend\Domain\Pipeline\PipelineState;
use Cabinet\Contracts\ErrorKind;
use Cabinet\Contracts\JobStatus;
use Cabinet\Contracts\PipelineStage;
use PDO;

final class PipelineStatesRepository implements PipelineStateRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(PipelineState $state): void
    {
        $isTerminal = $state->isInDeadLetter() ? 1 : 0;
        $lastError = $state->lastError() ? $state->lastError()->value : null;

        $sql = <<<SQL
        INSERT INTO pipeline_states (task_id, stage, status, attempt, last_error_kind, is_terminal, updated_at)
        VALUES (:task_id, :stage, :status, :attempt, :last_error_kind, :is_terminal, :updated_at)
        ON CONFLICT(task_id) DO UPDATE SET
            stage = :stage,
            status = :status,
            attempt = :attempt,
            last_error_kind = :last_error_kind,
            is_terminal = :is_terminal,
            updated_at = :updated_at
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':task_id' => $state->jobId()->toString(),
            ':stage' => $state->stage()->value,
            ':status' => $state->status()->value,
            ':attempt' => $state->attemptCount(),
            ':last_error_kind' => $lastError,
            ':is_terminal' => $isTerminal,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function findByJobId(JobId $jobId): ?PipelineState
    {
        $sql = 'SELECT * FROM pipeline_states WHERE task_id = :task_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':task_id' => $jobId->toString()]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row === false) {
            return null;
        }

        return $this->hydratePipelineState($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydratePipelineState(array $row): PipelineState
    {
        $state = PipelineState::create(JobId::fromString($row['task_id']));
        
        $targetStage = PipelineStage::from($row['stage']);
        $targetStatus = JobStatus::from($row['status']);
        $attemptCount = (int) $row['attempt'];
        $lastError = $row['last_error_kind'] ? ErrorKind::from($row['last_error_kind']) : null;

        // Recreate the state by applying transitions
        // This is a simplified approach - we reconstruct state directly via reflection or similar
        // For now, we'll use a more direct approach by manipulating the object
        
        // Start from initial state and apply necessary transitions to reach target state
        $this->applyStateTransitions($state, $targetStage, $targetStatus, $attemptCount, $lastError);

        return $state;
    }

    private function applyStateTransitions(
        PipelineState $state,
        PipelineStage $targetStage,
        JobStatus $targetStatus,
        int $attemptCount,
        ?ErrorKind $lastError
    ): void {
        // This is a simplified reconstruction - in production you'd want event sourcing or a more robust approach
        // For now, we'll advance through stages and apply the final status
        
        $currentStage = $state->stage();
        $stages = [
            PipelineStage::PARSE,
            PipelineStage::PHOTOS,
            PipelineStage::PUBLISH,
            PipelineStage::EXPORT,
            PipelineStage::CLEANUP,
        ];

        // Advance through stages until we reach the target
        while ($currentStage !== $targetStage) {
            if ($state->status() === JobStatus::QUEUED) {
                $state->markRunning();
            }
            $state->markSucceeded(); // This also advances the stage
            $currentStage = $state->stage();
        }

        // Now apply the final status at the target stage
        if ($targetStatus === JobStatus::RUNNING) {
            if ($state->status() === JobStatus::QUEUED) {
                for ($i = 0; $i < $attemptCount; $i++) {
                    $state->markRunning();
                    if ($i < $attemptCount - 1) {
                        // Simulate retry by failing and scheduling retry
                        $state->markFailed($lastError ?? ErrorKind::INTERNAL_ERROR);
                        $state->scheduleRetry();
                    }
                }
            }
        } elseif ($targetStatus === JobStatus::FAILED) {
            if ($state->status() !== JobStatus::RUNNING) {
                $state->markRunning();
            }
            $state->markFailed($lastError ?? ErrorKind::INTERNAL_ERROR);
        } elseif ($targetStatus === JobStatus::DEAD_LETTER) {
            if ($state->status() === JobStatus::QUEUED) {
                $state->markRunning();
            }
            if ($state->status() !== JobStatus::FAILED) {
                $state->markFailed($lastError ?? ErrorKind::INTERNAL_ERROR);
            }
            $state->moveToDeadLetter();
        }
    }
}
