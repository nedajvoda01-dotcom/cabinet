<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests;

use Cabinet\Backend\Http\Security\Protocol\ProtocolHeaders;

final class SecurityPipelineTest extends TestCase
{
    public function testPublicEndpointBypassesSecurity(): void
    {
        $kernel = $this->createKernel();
        $response = $kernel->handle(new \Cabinet\Backend\Http\Request('GET', '/health'));

        $this->assertEquals(200, $response->statusCode());
        $this->assertEquals('ok', $response->payload()['status']);
    }

    public function testMissingRequirementsFailClosed(): void
    {
        $kernel = $this->createKernel();
        $request = $this->buildSignedRequest(
            'POST',
            '/security/missing-requirements',
            '{}',
            'nonce-123456789012',
            'user:user-123',
            'key-1',
            'secret-key-user-123'
        );

        $response = $kernel->handle($request);
        $this->assertEquals(403, $response->statusCode());
        $this->assertEquals('missing_requirements', $response->payload()['error']['code']);
    }

    public function testSecuredEndpointRequiresHeaders(): void
    {
        $kernel = $this->createKernel();
        $response = $kernel->handle(new \Cabinet\Backend\Http\Request('POST', '/security/echo'));

        $this->assertEquals(403, $response->statusCode());
        $this->assertEquals('missing_header', $response->payload()['error']['code']);
    }

    public function testNonceReuseIsDenied(): void
    {
        $kernel = $this->createKernel();
        $nonce = 'nonce-unique-123456';
        $request1 = $this->buildSignedRequest('POST', '/security/echo', '{}', $nonce, 'user:user-123', 'key-1', 'secret-key-user-123');
        $request2 = $this->buildSignedRequest('POST', '/security/echo', '{}', $nonce, 'user:user-123', 'key-1', 'secret-key-user-123');

        $first = $kernel->handle($request1);
        $second = $kernel->handle($request2);

        $this->assertEquals(200, $first->statusCode());
        $this->assertEquals(403, $second->statusCode());
        $this->assertEquals('nonce_reuse', $second->payload()['error']['code']);
    }

    public function testInvalidSignatureIsDenied(): void
    {
        $kernel = $this->createKernel();
        $request = $this->buildSignedRequest(
            'POST',
            '/security/echo',
            '{}',
            'nonce-invalid-1234',
            'user:user-123',
            'key-1',
            'secret-key-user-123',
            overrideSignature: 'bad-signature'
        );

        $response = $kernel->handle($request);
        $this->assertEquals(403, $response->statusCode());
        $this->assertEquals('signature_invalid', $response->payload()['error']['code']);
    }

    public function testSignatureRunsBeforeEncryption(): void
    {
        $kernel = $this->createKernel();
        $request = $this->buildSignedRequest(
            'POST',
            '/security/encrypted-echo',
            'not-json',
            'nonce-encrypted-123',
            'user:user-123',
            'key-1',
            'secret-key-user-123',
            overrideSignature: 'not-valid',
            extraHeaders: [ProtocolHeaders::ENCRYPTION => 'v1']
        );

        $response = $kernel->handle($request);
        $this->assertEquals(403, $response->statusCode());
        $this->assertEquals('signature_invalid', $response->payload()['error']['code']);
    }

    public function testScopeFailureIsDenied(): void
    {
        $kernel = $this->createKernel();
        $request = $this->buildSignedRequest(
            'POST',
            '/security/echo',
            '{}',
            'nonce-scope-123456',
            'user:limited',
            'key-3',
            'limited-secret'
        );

        $response = $kernel->handle($request);
        $this->assertEquals(403, $response->statusCode());
        $this->assertEquals('scope_missing', $response->payload()['error']['code']);
    }

    public function testRoleFailureIsDenied(): void
    {
        $kernel = $this->createKernel();
        $request = $this->buildSignedRequest(
            'POST',
            '/security/admin-echo',
            '{}',
            'nonce-role-123456',
            'user:user-123',
            'key-1',
            'secret-key-user-123'
        );

        $response = $kernel->handle($request);
        $this->assertEquals(403, $response->statusCode());
        $this->assertEquals('role_insufficient', $response->payload()['error']['code']);
    }

    public function testRateLimitIsEnforced(): void
    {
        $kernel = $this->createKernel();
        $request1 = $this->buildSignedRequest('POST', '/security/echo', '{}', 'nonce-rate-1-23456', 'user:user-123', 'key-1', 'secret-key-user-123');
        $request2 = $this->buildSignedRequest('POST', '/security/echo', '{}', 'nonce-rate-2-23456', 'user:user-123', 'key-1', 'secret-key-user-123');
        $request3 = $this->buildSignedRequest('POST', '/security/echo', '{}', 'nonce-rate-3-23456', 'user:user-123', 'key-1', 'secret-key-user-123');

        $kernel->handle($request1);
        $kernel->handle($request2);
        $response = $kernel->handle($request3);

        $this->assertEquals(403, $response->statusCode());
        $this->assertEquals('rate_limited', $response->payload()['error']['code']);
    }
}
