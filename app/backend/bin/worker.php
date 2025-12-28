#!/usr/bin/env php
<?php

declare(strict_types=1);

use Cabinet\Backend\Application\Commands\Pipeline\TickTaskCommand;
use Cabinet\Backend\Bootstrap\AppKernel;
use Cabinet\Backend\Bootstrap\Clock;
use Cabinet\Backend\Bootstrap\Config;
use Cabinet\Backend\Bootstrap\Container;
use Cabinet\Contracts\ErrorKind;

require __DIR__ . '/../../../vendor/autoload.php';

// Worker configuration constants
const POLLING_INTERVAL_SECONDS = 1;

echo "Starting worker...\n";

$config = Config::fromEnvironment();
$clock = new Clock();
$container = new Container($config, $clock);

$jobQueue = $container->jobQueue();
$commandBus = $container->commandBus();

$running = true;

// Handle SIGTERM and SIGINT gracefully
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, function () use (&$running) {
        echo "Received SIGTERM, shutting down...\n";
        $running = false;
    });

    pcntl_signal(SIGINT, function () use (&$running) {
        echo "Received SIGINT, shutting down...\n";
        $running = false;
    });
}

while ($running) {
    // Dispatch signals if available
    if (function_exists('pcntl_signal_dispatch')) {
        pcntl_signal_dispatch();
    }

    // Claim next job
    $claimedJob = $jobQueue->claimNext();

    if ($claimedJob === null) {
        // No jobs available, sleep for 1 second
        sleep(POLLING_INTERVAL_SECONDS);
        continue;
    }

    echo sprintf(
        "[%s] Processing job %s (kind: %s, attempt: %d)\n",
        date('Y-m-d H:i:s'),
        $claimedJob->jobId(),
        $claimedJob->kind(),
        $claimedJob->attempt()
    );

    try {
        // Execute job based on kind
        if ($claimedJob->kind() === 'advance_pipeline') {
            $command = new TickTaskCommand($claimedJob->taskId());
            $result = $commandBus->dispatch($command);

            if ($result->isSuccess()) {
                $jobQueue->markSucceeded($claimedJob->jobId());
                echo sprintf(
                    "[%s] Job %s succeeded\n",
                    date('Y-m-d H:i:s'),
                    $claimedJob->jobId()
                );
            } else {
                // Determine if error is retryable
                $error = $result->error();
                $errorCode = $error->code()->value;
                
                // Treat integration_unavailable as retryable, others as non-retryable
                $retryable = $errorCode === ErrorKind::INTEGRATION_UNAVAILABLE->value;
                
                $jobQueue->markFailed(
                    $claimedJob->jobId(),
                    $error->code(),
                    $retryable
                );
                
                echo sprintf(
                    "[%s] Job %s failed: %s (retryable: %s)\n",
                    date('Y-m-d H:i:s'),
                    $claimedJob->jobId(),
                    $error->message(),
                    $retryable ? 'yes' : 'no'
                );
            }
        } else {
            echo sprintf(
                "[%s] Unknown job kind: %s\n",
                date('Y-m-d H:i:s'),
                $claimedJob->kind()
            );
            $jobQueue->markFailed(
                $claimedJob->jobId(),
                \Cabinet\Contracts\ErrorKind::INTERNAL_ERROR,
                false
            );
        }
    } catch (\Throwable $e) {
        echo sprintf(
            "[%s] Job %s threw exception: %s\n",
            date('Y-m-d H:i:s'),
            $claimedJob->jobId(),
            $e->getMessage()
        );
        
        // Mark as failed, non-retryable for unexpected exceptions
        $jobQueue->markFailed(
            $claimedJob->jobId(),
            ErrorKind::INTERNAL_ERROR,
            false
        );
    }
}

echo "Worker stopped.\n";
