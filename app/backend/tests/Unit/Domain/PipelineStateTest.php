<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Domain;

use Cabinet\Backend\Domain\Pipeline\JobId;
use Cabinet\Backend\Domain\Pipeline\PipelineState;
use Cabinet\Backend\Domain\Shared\Exceptions\InvalidStateTransition;
use Cabinet\Backend\Tests\TestCase;
use Cabinet\Contracts\ErrorKind;
use Cabinet\Contracts\JobStatus;
use Cabinet\Contracts\PipelineStage;

final class PipelineStateTest extends TestCase
{
    public function testCreatePipelineState(): void
    {
        $jobId = JobId::fromString('job-123');
        $state = PipelineState::create($jobId);
        
        $this->assertTrue($state->jobId()->equals($jobId));
        $this->assertEquals(PipelineStage::PARSE, $state->stage());
        $this->assertEquals(JobStatus::QUEUED, $state->status());
        $this->assertEquals(0, $state->attemptCount());
        $this->assertEquals(null, $state->lastError());
        $this->assertTrue(!$state->isDone());
        $this->assertTrue(!$state->isInDeadLetter());
    }

    public function testMarkRunningIncrementsAttempt(): void
    {
        $jobId = JobId::fromString('job-123');
        $state = PipelineState::create($jobId);
        
        $this->assertEquals(0, $state->attemptCount());
        
        $state->markRunning();
        $this->assertEquals(JobStatus::RUNNING, $state->status());
        $this->assertEquals(1, $state->attemptCount());
        
        $state->markFailed(ErrorKind::INTERNAL_ERROR);
        $state->scheduleRetry();
        $state->markRunning();
        $this->assertEquals(2, $state->attemptCount());
    }

    public function testSuccessfulPipelineProgression(): void
    {
        $jobId = JobId::fromString('job-123');
        $state = PipelineState::create($jobId);
        
        // PARSE stage
        $this->assertEquals(PipelineStage::PARSE, $state->stage());
        $state->markRunning();
        $state->markSucceeded();
        
        // PHOTOS stage
        $this->assertEquals(PipelineStage::PHOTOS, $state->stage());
        $this->assertEquals(JobStatus::QUEUED, $state->status());
        $this->assertEquals(0, $state->attemptCount());
        $state->markRunning();
        $state->markSucceeded();
        
        // PUBLISH stage
        $this->assertEquals(PipelineStage::PUBLISH, $state->stage());
        $this->assertEquals(JobStatus::QUEUED, $state->status());
        $state->markRunning();
        $state->markSucceeded();
        
        // EXPORT stage
        $this->assertEquals(PipelineStage::EXPORT, $state->stage());
        $this->assertEquals(JobStatus::QUEUED, $state->status());
        $state->markRunning();
        $state->markSucceeded();
        
        // CLEANUP stage
        $this->assertEquals(PipelineStage::CLEANUP, $state->stage());
        $this->assertEquals(JobStatus::QUEUED, $state->status());
        $state->markRunning();
        $state->markSucceeded();
        
        // Done
        $this->assertEquals(PipelineStage::CLEANUP, $state->stage());
        $this->assertEquals(JobStatus::SUCCEEDED, $state->status());
        $this->assertTrue($state->isDone());
    }

    public function testMarkFailedStoresError(): void
    {
        $jobId = JobId::fromString('job-123');
        $state = PipelineState::create($jobId);
        
        $state->markRunning();
        $state->markFailed(ErrorKind::VALIDATION_ERROR);
        
        $this->assertEquals(JobStatus::FAILED, $state->status());
        $this->assertEquals(ErrorKind::VALIDATION_ERROR, $state->lastError());
    }

    public function testScheduleRetry(): void
    {
        $jobId = JobId::fromString('job-123');
        $state = PipelineState::create($jobId);
        
        $state->markRunning();
        $state->markFailed(ErrorKind::INTERNAL_ERROR);
        
        $this->assertEquals(JobStatus::FAILED, $state->status());
        $this->assertEquals(1, $state->attemptCount());
        
        $state->scheduleRetry();
        
        $this->assertEquals(JobStatus::QUEUED, $state->status());
        $this->assertEquals(1, $state->attemptCount()); // Attempt count persists
        $this->assertEquals(ErrorKind::INTERNAL_ERROR, $state->lastError()); // Error persists
    }

    public function testCannotRetryWhenNotFailed(): void
    {
        $jobId = JobId::fromString('job-123');
        $state = PipelineState::create($jobId);
        
        try {
            $state->scheduleRetry();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testMoveToDeadLetter(): void
    {
        $jobId = JobId::fromString('job-123');
        $state = PipelineState::create($jobId);
        
        $state->markRunning();
        $state->markFailed(ErrorKind::INTERNAL_ERROR);
        $state->moveToDeadLetter();
        
        $this->assertEquals(JobStatus::DEAD_LETTER, $state->status());
        $this->assertTrue($state->isInDeadLetter());
    }

    public function testCannotChangeAfterDeadLetter(): void
    {
        $jobId = JobId::fromString('job-123');
        $state = PipelineState::create($jobId);
        
        $state->markRunning();
        $state->markFailed(ErrorKind::INTERNAL_ERROR);
        $state->moveToDeadLetter();
        
        // Try various operations
        try {
            $state->markRunning();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
        
        try {
            $state->markSucceeded();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
        
        try {
            $state->markFailed(ErrorKind::INTERNAL_ERROR);
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
        
        try {
            $state->scheduleRetry();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
        
        try {
            $state->moveToDeadLetter();
            throw new \Exception('Should have thrown InvalidStateTransition');
        } catch (InvalidStateTransition $e) {
            $this->assertTrue(true);
        }
    }

    public function testRetryPreservesStage(): void
    {
        $jobId = JobId::fromString('job-123');
        $state = PipelineState::create($jobId);
        
        // Advance to PHOTOS
        $state->markRunning();
        $state->markSucceeded();
        
        $this->assertEquals(PipelineStage::PHOTOS, $state->stage());
        
        // Fail and retry
        $state->markRunning();
        $state->markFailed(ErrorKind::INTEGRATION_UNAVAILABLE);
        $state->scheduleRetry();
        
        // Should still be at PHOTOS
        $this->assertEquals(PipelineStage::PHOTOS, $state->stage());
        $this->assertEquals(JobStatus::QUEUED, $state->status());
    }

    public function testSucceededClearsError(): void
    {
        $jobId = JobId::fromString('job-123');
        $state = PipelineState::create($jobId);
        
        $state->markRunning();
        $state->markFailed(ErrorKind::INTERNAL_ERROR);
        
        $this->assertEquals(ErrorKind::INTERNAL_ERROR, $state->lastError());
        
        $state->scheduleRetry();
        $state->markRunning();
        $state->markSucceeded();
        
        $this->assertEquals(null, $state->lastError());
    }

    public function testAttemptCountResetsOnNewStage(): void
    {
        $jobId = JobId::fromString('job-123');
        $state = PipelineState::create($jobId);
        
        // Multiple attempts on PARSE
        $state->markRunning(); // attempt 1
        $state->markFailed(ErrorKind::INTERNAL_ERROR);
        $state->scheduleRetry();
        $state->markRunning(); // attempt 2
        $state->markFailed(ErrorKind::INTERNAL_ERROR);
        $state->scheduleRetry();
        $state->markRunning(); // attempt 3
        
        $this->assertEquals(3, $state->attemptCount());
        
        // Success advances to PHOTOS and resets
        $state->markSucceeded();
        
        $this->assertEquals(PipelineStage::PHOTOS, $state->stage());
        $this->assertEquals(0, $state->attemptCount());
    }

    public function testStartResetsState(): void
    {
        $jobId = JobId::fromString('job-123');
        $state = PipelineState::create($jobId);
        
        $state->markRunning();
        $state->markFailed(ErrorKind::INTERNAL_ERROR);
        
        $this->assertEquals(1, $state->attemptCount());
        $this->assertEquals(ErrorKind::INTERNAL_ERROR, $state->lastError());
        
        $state->start();
        
        $this->assertEquals(JobStatus::QUEUED, $state->status());
        $this->assertEquals(0, $state->attemptCount());
        $this->assertEquals(null, $state->lastError());
    }

    public function testAllErrorKinds(): void
    {
        $jobId = JobId::fromString('job-123');
        
        $errorKinds = [
            ErrorKind::VALIDATION_ERROR,
            ErrorKind::SECURITY_DENIED,
            ErrorKind::NOT_FOUND,
            ErrorKind::INTERNAL_ERROR,
            ErrorKind::INTEGRATION_UNAVAILABLE,
            ErrorKind::RATE_LIMITED,
        ];
        
        foreach ($errorKinds as $errorKind) {
            $state = PipelineState::create($jobId);
            $state->markRunning();
            $state->markFailed($errorKind);
            
            $this->assertEquals($errorKind, $state->lastError());
        }
    }
}
