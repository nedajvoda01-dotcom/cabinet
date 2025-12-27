<?php
// tests/unit/backend/retryPolicy.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Queues\RetryPolicy;

final class RetryPolicyTest extends TestCase
{
    public function testRetryableNetworkAnd5xx(): void
    {
        $policy = new RetryPolicy(3, [60, 120, 240]);

        $networkError = ['code' => 'network_error'];
        $timeoutError = ['code' => 'timeout'];
        $serverError = ['code' => 'robot_http_503', 'meta' => ['status' => 503]];

        $this->assertTrue($policy->isRetryableError($networkError));
        $this->assertTrue($policy->isRetryableError($timeoutError));
        $this->assertTrue($policy->isRetryableError($serverError));
        $this->assertTrue($policy->shouldRetry(1));
    }

    public function testFatalContractMismatchGoesDead(): void
    {
        $policy = new RetryPolicy(3, [60, 120, 240]);

        $contractError = ['code' => 'contract_mismatch', 'fatal' => true];

        $this->assertFalse($policy->isRetryableError($contractError));
        $this->assertTrue($policy->shouldRetry(2));
        $this->assertFalse($policy->isRetryableError(['fatal' => true]));
    }
}
