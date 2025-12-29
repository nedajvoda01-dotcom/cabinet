<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Security\Nonce;

interface NonceRepository
{
    /**
     * Attempts to persist the nonce as used.
     *
     * @return bool true if the nonce was unused and is now reserved, false if it was already present.
     */
    public function consume(string $nonce): bool;
}
