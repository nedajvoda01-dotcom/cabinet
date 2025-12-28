<?php

declare(strict_types=1);

namespace Cabinet\Backend\Domain\Shared\ValueObject;

final class ScopeSet
{
    /** @var array<string> */
    private readonly array $scopes;

    /**
     * @param array<Scope> $scopes
     */
    private function __construct(array $scopes)
    {
        // Extract string values and sort for deterministic ordering
        $stringScopes = array_map(fn(Scope $s) => $s->toString(), $scopes);
        $stringScopes = array_unique($stringScopes);
        sort($stringScopes);
        $this->scopes = $stringScopes;
    }

    /**
     * @param array<Scope> $scopes
     */
    public static function fromScopes(array $scopes): self
    {
        return new self($scopes);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function has(Scope $scope): bool
    {
        return in_array($scope->toString(), $this->scopes, true);
    }

    /**
     * @param array<Scope> $requiredScopes
     */
    public function hasAll(array $requiredScopes): bool
    {
        foreach ($requiredScopes as $scope) {
            if (!$this->has($scope)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array<string>
     */
    public function toArray(): array
    {
        return $this->scopes;
    }
}
