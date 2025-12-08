<?php
// backend/src/WS/WsEmitter.php

namespace App\WS;

final class WsEmitter
{
    public function __construct(private WsServerInterface $server) {}

    /**
     * @param string $event   имя события (WsEventNames::*)
     * @param array  $data    payload
     * @param string|null $correlationId для трассировки сквозных операций
     */
    public function emit(string $event, array $data, ?string $correlationId = null): void
    {
        $envelope = [
            'event' => $event,
            'data' => $data,
            'ts' => date('c'),
        ];
        if ($correlationId) {
            $envelope['correlation_id'] = $correlationId;
        }

        try {
            $this->server->broadcast($envelope);
        } catch (\Throwable $e) {
            // WS не должен валить воркеры / пайплайн
            // логирование можно сделать через Logger слой, если он есть
        }
    }

    /**
     * Точечная отправка (если когда-то будет персональный канал).
     */
    public function emitTo(string $clientId, string $event, array $data, ?string $correlationId = null): void
    {
        $envelope = [
            'event' => $event,
            'data' => $data,
            'ts' => date('c'),
        ];
        if ($correlationId) {
            $envelope['correlation_id'] = $correlationId;
        }

        try {
            $this->server->sendTo($clientId, $envelope);
        } catch (\Throwable $e) {
            // silent fail
        }
    }
}
