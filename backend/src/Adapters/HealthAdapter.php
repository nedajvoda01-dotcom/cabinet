<?php
// backend/src/Adapters/HealthAdapter.php

namespace App\Adapters;

final class HealthAdapter
{
    public function __construct(
        private \App\Adapters\Ports\ParserPort $parser,
        private \App\Adapters\Ports\PhotoProcessorPort $photoApi,
        private \App\Adapters\Ports\StoragePort $s3,
        private \App\Adapters\Ports\RobotPort $robot,
        private \App\Adapters\Ports\RobotProfilePort $dolphin
    ) {}

    public function checkAll(): array
    {
        $list = [];

        $list[] = $this->safe("parser", fn() => ['ok'=>true]); // poll-only, health optional
        $list[] = $this->safe("photo-api", fn() => $this->photoApi->health());
        $list[] = $this->safe("storage", fn() => ['ok'=>true, 'sample_url'=>$this->s3->publicUrl('raw/')]);
        $list[] = $this->safe("robot", fn() => $this->robot->health());
        $list[] = $this->safe("dolphin", fn() => $this->dolphin->health());

        $ok = true;
        foreach ($list as $it) if (!$it['ok']) { $ok = false; break; }

        return [
            'ok' => $ok,
            'integrations' => $list,
            'updated_at' => date('c'),
        ];
    }

    private function safe(string $name, callable $fn): array
    {
        $t0 = microtime(true);
        try {
            $res = $fn();
            $lat = (int)((microtime(true)-$t0)*1000);
            return [
                'name' => $name,
                'ok' => (bool)($res['ok'] ?? true),
                'latency_ms' => $lat,
                'meta' => $res,
                'updated_at' => date('c'),
            ];
        } catch (AdapterException $e) {
            $lat = (int)((microtime(true)-$t0)*1000);
            return [
                'name' => $name,
                'ok' => false,
                'latency_ms' => $lat,
                'last_error' => $e->toErrorArray(),
                'updated_at' => date('c'),
            ];
        } catch (\Throwable $e) {
            $lat = (int)((microtime(true)-$t0)*1000);
            return [
                'name' => $name,
                'ok' => false,
                'latency_ms' => $lat,
                'last_error' => [
                    'code' => 'health_exception',
                    'message' => $e->getMessage(),
                    'fatal' => false,
                ],
                'updated_at' => date('c'),
            ];
        }
    }
}
