<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Dlq;

final class FileDlqWriter implements DlqWriterInterface
{
    public function __construct(private string $path)
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }

    public function write(DlqRecord $record): void
    {
        $payload = [
            'job_type' => $record->job->type(),
            'subject_type' => $record->job->subjectType(),
            'subject_id' => $record->job->subjectId(),
            'attempts' => $record->attempts,
            'trace_id' => $record->job->traceId(),
            'idempotency_key' => $record->job->idempotencyKey(),
            'payload' => $record->job->payload()->toArray(),
            'error' => $record->error->toArray(),
            'failed_at' => $record->failedAt->format(DATE_ATOM),
        ];

        file_put_contents($this->path, json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }
}
