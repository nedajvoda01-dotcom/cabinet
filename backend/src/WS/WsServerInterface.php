<?php
// backend/src/WS/WsServerInterface.php

namespace App\WS;

interface WsServerInterface
{
    /**
     * Broadcast to all connected clients.
     * @param array $envelope
     */
    public function broadcast(array $envelope): void;

    /**
     * Send to single client (optional).
     * @param string $clientId
     * @param array $envelope
     */
    public function sendTo(string $clientId, array $envelope): void;
}
