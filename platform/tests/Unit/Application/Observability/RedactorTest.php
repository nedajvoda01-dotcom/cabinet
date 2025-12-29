<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Application\Observability;

use Cabinet\Backend\Application\Observability\Redactor;
use Cabinet\Backend\Tests\TestCase;

final class RedactorTest extends TestCase
{
    public function testRedactsSensitiveKeys(): void
    {
        $data = [
            'task_id' => 'task-123',
            'signature' => 'secret-signature',
            'status' => 'completed',
        ];

        $redacted = Redactor::redact($data);

        $this->assertTrue($redacted['task_id'] === 'task-123', 'Safe keys should not be redacted');
        $this->assertTrue($redacted['status'] === 'completed', 'Safe keys should not be redacted');
        $this->assertTrue($redacted['signature'] === '[REDACTED]', 'Signature should be redacted');
    }

    public function testRedactsNonce(): void
    {
        $data = ['nonce' => '1234567890', 'task_id' => 'task-123'];
        $redacted = Redactor::redact($data);
        $this->assertTrue($redacted['nonce'] === '[REDACTED]', 'Nonce should be redacted');
        $this->assertTrue($redacted['task_id'] === 'task-123', 'Safe keys should not be redacted');
    }

    public function testRedactsCiphertext(): void
    {
        $data = ['ciphertext' => 'encrypted-data', 'stage' => 'parse'];
        $redacted = Redactor::redact($data);
        $this->assertTrue($redacted['ciphertext'] === '[REDACTED]', 'Ciphertext should be redacted');
        $this->assertTrue($redacted['stage'] === 'parse', 'Safe keys should not be redacted');
    }

    public function testRedactsToken(): void
    {
        $data = ['token' => 'bearer-token', 'user_id' => 'user-123'];
        $redacted = Redactor::redact($data);
        $this->assertTrue($redacted['token'] === '[REDACTED]', 'Token should be redacted');
        $this->assertTrue($redacted['user_id'] === 'user-123', 'Safe keys should not be redacted');
    }

    public function testRedactsAuthorization(): void
    {
        $data = ['authorization' => 'Basic abc123', 'endpoint' => '/tasks'];
        $redacted = Redactor::redact($data);
        $this->assertTrue($redacted['authorization'] === '[REDACTED]', 'Authorization should be redacted');
        $this->assertTrue($redacted['endpoint'] === '/tasks', 'Safe keys should not be redacted');
    }

    public function testRedactsNestedData(): void
    {
        $data = [
            'task_id' => 'task-123',
            'metadata' => [
                'signature' => 'secret-signature',
                'stage' => 'parse',
                'auth' => [
                    'token' => 'bearer-token',
                    'user' => 'user-123',
                ],
            ],
        ];

        $redacted = Redactor::redact($data);

        $this->assertTrue($redacted['task_id'] === 'task-123', 'Safe keys should not be redacted');
        $this->assertTrue($redacted['metadata']['signature'] === '[REDACTED]', 'Nested signature should be redacted');
        $this->assertTrue($redacted['metadata']['stage'] === 'parse', 'Nested safe keys should not be redacted');
        $this->assertTrue($redacted['metadata']['auth']['token'] === '[REDACTED]', 'Deeply nested token should be redacted');
        $this->assertTrue($redacted['metadata']['auth']['user'] === 'user-123', 'Deeply nested safe keys should not be redacted');
    }

    public function testRedactsCaseInsensitive(): void
    {
        $data = [
            'API_KEY' => 'my-api-key',
            'Secret' => 'my-secret',
            'SIGNATURE' => 'my-signature',
        ];

        $redacted = Redactor::redact($data);

        $this->assertTrue($redacted['API_KEY'] === '[REDACTED]', 'API_KEY should be redacted (case insensitive)');
        $this->assertTrue($redacted['Secret'] === '[REDACTED]', 'Secret should be redacted (case insensitive)');
        $this->assertTrue($redacted['SIGNATURE'] === '[REDACTED]', 'SIGNATURE should be redacted (case insensitive)');
    }

    public function testEmptyArrayRemainsEmpty(): void
    {
        $data = [];
        $redacted = Redactor::redact($data);
        $this->assertTrue(empty($redacted), 'Empty array should remain empty');
    }
}
