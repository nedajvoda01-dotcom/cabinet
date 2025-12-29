<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests;

use Cabinet\Backend\Bootstrap\Clock;
use Cabinet\Backend\Bootstrap\Config;
use Cabinet\Backend\Bootstrap\Container;
use Cabinet\Backend\Http\Kernel\HttpKernel;
use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Security\Protocol\ProtocolHeaders;
use Cabinet\Backend\Infrastructure\Security\Signatures\SignatureCanonicalizer;
use Cabinet\Backend\Infrastructure\Security\Signatures\StringToSignBuilder;
use Exception;

abstract class TestCase
{
    /**
     * @return array<string>
     */
    public function run(): array
    {
        $executed = [];
        foreach (get_class_methods($this) as $method) {
            if (str_starts_with($method, 'test')) {
                $this->$method();
                $executed[] = $method;
            }
        }

        return $executed;
    }

    protected function createKernel(): HttpKernel
    {
        $config = Config::fromEnvironment();
        $clock = new Clock();
        $container = new Container($config, $clock);

        return $container->httpKernel();
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new Exception($message !== '' ? $message : sprintf('Expected %s but received %s', var_export($expected, true), var_export($actual, true)));
        }
    }

    protected function assertTrue(bool $condition, string $message = ''): void
    {
        if ($condition !== true) {
            throw new Exception($message !== '' ? $message : 'Condition is not true');
        }
    }

    protected function assertNotEmpty(string $value, string $message = ''): void
    {
        if ($value === '') {
            throw new Exception($message !== '' ? $message : 'Value should not be empty');
        }
    }

    /**
     * @param array<string, string> $extraHeaders
     */
    protected function buildSignedRequest(
        string $method,
        string $path,
        string $body,
        string $nonce,
        string $actor,
        string $kid,
        string $secret,
        string $traceId = 'trace-123456789012',
        array $extraHeaders = [],
        ?string $overrideSignature = null
    ): Request {
        $builder = new StringToSignBuilder();
        $canonicalizer = new SignatureCanonicalizer();
        $stringToSign = $builder->build(new Request($method, $path, [], $body), $nonce, $kid, $traceId);
        $canonical = $canonicalizer->canonicalize($stringToSign);
        $signature = $overrideSignature ?? base64_encode(hash_hmac('sha256', $canonical, $secret, true));

        $headers = array_merge([
            ProtocolHeaders::ACTOR => $actor,
            ProtocolHeaders::NONCE => $nonce,
            ProtocolHeaders::TRACE => $traceId,
            ProtocolHeaders::KEY_ID => $kid,
            ProtocolHeaders::SIGNATURE => $signature,
        ], $extraHeaders);

        return new Request($method, $path, $headers, $body);
    }
}
