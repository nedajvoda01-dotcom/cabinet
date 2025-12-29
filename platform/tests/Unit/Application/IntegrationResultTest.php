<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Application;

use Cabinet\Backend\Application\Shared\IntegrationResult;
use Cabinet\Backend\Tests\TestCase;
use Cabinet\Contracts\ErrorKind;

final class IntegrationResultTest extends TestCase
{
    public function testSucceededResult(): void
    {
        $payload = ['key' => 'value', 'count' => 42];
        $result = IntegrationResult::succeeded($payload);

        $this->assertTrue($result->isSuccess(), 'Result should be successful');
        $this->assertTrue(!$result->isFailed(), 'Result should not be failed');
        $this->assertTrue(!$result->isRetryable(), 'Successful result should not be retryable');
        $this->assertTrue($result->payload() === $payload, 'Payload should match');
        $this->assertTrue($result->errorKind() === null, 'Error kind should be null for success');
    }

    public function testSucceededResultWithEmptyPayload(): void
    {
        $result = IntegrationResult::succeeded();

        $this->assertTrue($result->isSuccess(), 'Result should be successful');
        $this->assertTrue($result->payload() === [], 'Payload should be empty array');
    }

    public function testFailedResultRetryable(): void
    {
        $result = IntegrationResult::failed(ErrorKind::INTEGRATION_UNAVAILABLE, true);

        $this->assertTrue($result->isFailed(), 'Result should be failed');
        $this->assertTrue(!$result->isSuccess(), 'Result should not be successful');
        $this->assertTrue($result->isRetryable(), 'Result should be retryable');
        $this->assertTrue($result->errorKind() === ErrorKind::INTEGRATION_UNAVAILABLE, 'Error kind should match');
        $this->assertTrue($result->payload() === [], 'Failed result should have empty payload');
    }

    public function testFailedResultNonRetryable(): void
    {
        $result = IntegrationResult::failed(ErrorKind::VALIDATION_ERROR, false);

        $this->assertTrue($result->isFailed(), 'Result should be failed');
        $this->assertTrue(!$result->isRetryable(), 'Result should not be retryable');
        $this->assertTrue($result->errorKind() === ErrorKind::VALIDATION_ERROR, 'Error kind should match');
    }

    public function testDifferentErrorKinds(): void
    {
        $kinds = [
            ErrorKind::VALIDATION_ERROR,
            ErrorKind::SECURITY_DENIED,
            ErrorKind::NOT_FOUND,
            ErrorKind::INTERNAL_ERROR,
            ErrorKind::INTEGRATION_UNAVAILABLE,
            ErrorKind::RATE_LIMITED,
        ];

        foreach ($kinds as $kind) {
            $result = IntegrationResult::failed($kind, true);
            $this->assertTrue($result->errorKind() === $kind, "Error kind should be {$kind->value}");
        }
    }
}
