<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline;

use App\Queues\QueueJob;
use App\Queues\QueueService;
use Backend\Application\Pipeline\Jobs\Job;
use Backend\Application\Pipeline\Jobs\JobType;

final class JobDispatcher
{
    public function __construct(private QueueService $queues)
    {
    }

    public function enqueue(Job $job): QueueJob
    {
        $payload = $job->payload()->toArray();

        return match ($job->type()) {
            JobType::PHOTOS => $this->queues->enqueuePhotos((int)$job->subjectId(), $payload),
            JobType::EXPORT => $this->queues->enqueueExport((int)$job->subjectId(), $payload),
            JobType::PUBLISH => $this->queues->enqueuePublish((int)$job->subjectId(), $payload),
            JobType::PARSER => $this->queues->enqueueParser((int)$job->subjectId(), $payload),
            JobType::ROBOT_STATUS => $this->queues->enqueueRobotStatus((int)$job->subjectId(), $payload),
            default => throw new \InvalidArgumentException("Unknown job type: {$job->type()}")
        };
    }
}
