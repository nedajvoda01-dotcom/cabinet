<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Responses;

final class ApiResponse
{
    private array $payload;

    private int $statusCode;

    /** @var array<string, string> */
    private array $headers;

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    public function __construct(array $payload, int $statusCode = 200, array $headers = [])
    {
        $this->payload = $payload;
        $this->statusCode = $statusCode;
        $this->headers = [];

        foreach ($headers as $name => $value) {
            $this->headers[$this->normalizeHeaderName($name)] = $value;
        }

        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
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
        $normalized = $this->normalizeHeaderName($name);

        return $this->headers[$normalized] ?? null;
    }

    public function body(): string
    {
        return (string) json_encode($this->payload, JSON_UNESCAPED_SLASHES);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$clone->normalizeHeaderName($name)] = $value;

        return $clone;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value), true);
        }

        echo $this->body();
    }

    private function normalizeHeaderName(string $name): string
    {
        return preg_replace_callback(
            '/(^|-)([a-z])/',
            static fn (array $matches) => strtoupper($matches[0]),
            strtolower($name)
        ) ?? $name;
    }
}
