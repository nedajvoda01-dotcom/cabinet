<?php
// backend/src/WS/WsServer.php

namespace App\WS;

/**
 * MVP in-memory hub.
 * Реальная интеграция с Ratchet/Workerman может оборачивать этот класс.
 */
final class WsServer implements WsServerInterface
{
    /** @var array<string, callable> clientId => sender */
    private array $clients = [];

    /**
     * Register connection from low-level WS server.
     * $sender is function(array $payload): void
     */
    public function register(string $clientId, callable $sender): void
    {
        $this->clients[$clientId] = $sender;
    }

    public function unregister(string $clientId): void
    {
        unset($this->clients[$clientId]);
    }

    public function broadcast(array $envelope): void
    {
        foreach ($this->clients as $sender) {
            $sender($envelope);
        }
    }

    public function sendTo(string $clientId, array $envelope): void
    {
        if (!isset($this->clients[$clientId])) return;
        ($this->clients[$clientId])($envelope);
    }

    public function countClients(): int
    {
        return count($this->clients);
    }
}
