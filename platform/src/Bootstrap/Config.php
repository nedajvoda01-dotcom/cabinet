<?php

declare(strict_types=1);

namespace Cabinet\Backend\Bootstrap;

final class Config
{
    private string $environment;

    private string $version;

    private ?string $commit;

    private function __construct(string $environment, string $version, ?string $commit)
    {
        $this->environment = $environment;
        $this->version = $version;
        $this->commit = $commit;
    }

    public static function fromEnvironment(): self
    {
        $environment = getenv('APP_ENV') ?: 'dev';
        $version = getenv('CABINET_VERSION') ?: 'dev';
        $commit = getenv('CABINET_COMMIT') ?: null;

        return new self($environment, $version, $commit);
    }

    public function environment(): string
    {
        return $this->environment;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function commit(): ?string
    {
        return $this->commit;
    }
}
