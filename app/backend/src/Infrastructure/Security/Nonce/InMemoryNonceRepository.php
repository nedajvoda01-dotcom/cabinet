<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Security\Nonce;

final class InMemoryNonceRepository implements NonceRepository
{
    /** @var array<string, int> */
    private array $nonces = [];

    public function consume(string $nonce): bool
    {
        $now = time();
        $ttlSeconds = 600;

        foreach ($this->nonces as $value => $storedAt) {
            if ($storedAt + $ttlSeconds < $now) {
                unset($this->nonces[$value]);
            }
        }

        if (isset($this->nonces[$nonce])) {
            return false;
        }

        $this->nonces[$nonce] = $now;

        return true;
    }
}
