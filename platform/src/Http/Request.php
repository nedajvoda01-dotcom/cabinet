<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http;

final class Request
{
    private string $method;

    private string $path;

    /** @var array<string, string> */
    private array $headers;

    private ?string $requestId = null;

    private ?string $traceId = null;

    private string $body;

    /** @var array<string, mixed> */
    private array $attributes = [];

    /**
     * @param array<string, string> $headers
     */
    public function __construct(string $method, string $path, array $headers = [], string $body = '')
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->headers = [];
        $this->body = $body;

        foreach ($headers as $name => $value) {
            $this->headers[strtolower($name)] = $value;
        }
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        return new self($method, $path, self::extractHeaders(), (string) file_get_contents('php://input'));
    }

    /**
     * @return array<string, string>
     */
    private static function extractHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if ($headers !== false) {
                return array_change_key_case($headers, CASE_LOWER);
            }
        }

        $collected = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($key, 5)));
                $collected[$headerName] = (string) $value;
            }
        }

        return $collected;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name): ?string
    {
        $normalized = strtolower($name);

        return $this->headers[$normalized] ?? null;
    }

    public function withRequestId(string $requestId): void
    {
        $this->requestId = $requestId;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function withTraceId(string $traceId): void
    {
        $this->traceId = $traceId;
    }

    public function traceId(): ?string
    {
        return $this->traceId;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function withAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function attribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }
}
