<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Security\AttackProtection;

final class RateLimiter
{
    /** @var array<string, array<int>> */
    private array $requests = [];

    public function allow(string $actorId, string $routeId, int $limitPerWindow, int $windowSeconds = 60): bool
    {
        if ($limitPerWindow <= 0) {
            return true;
        }

        $key = sprintf('%s:%s', $actorId, $routeId);
        $now = time();

        $this->requests[$key] = array_filter(
            $this->requests[$key] ?? [],
            static fn (int $timestamp) => ($timestamp + $windowSeconds) >= $now
        );

        if (count($this->requests[$key]) >= $limitPerWindow) {
            return false;
        }

        $this->requests[$key][] = $now;

        return true;
    }
}
