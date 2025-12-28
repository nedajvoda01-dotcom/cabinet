<?php

declare(strict_types=1);

use Cabinet\Contracts\CanonicalJson;

final class ParityTest
{
    public function run(): array
    {
        return [
            $this->testEnumCoverage(),
            $this->testCanonicalParity(),
        ];
    }

    private function testEnumCoverage(): string
    {
        $definitions = require __DIR__ . '/../primitives/definitions.php';
        foreach ($definitions as $name => $definition) {
            if (($definition['kind'] ?? null) !== 'enum') {
                continue;
            }

            $class = "Cabinet\\Contracts\\{$name}";
            if (!class_exists($class) && !enum_exists($class)) {
                throw new RuntimeException("Missing generated class for {$name}");
            }

            $generated = array_map(static fn ($case) => $case->value, $class::cases());
            sort($generated);
            $expected = $definition['values'];
            sort($expected);

            if ($generated !== $expected) {
                throw new RuntimeException("Enum {$name} does not match primitive definition");
            }
        }

        return __METHOD__;
    }

    private function testCanonicalParity(): string
    {
        $vectors = [
            __DIR__ . '/../vectors/trace_context.json',
            __DIR__ . '/../vectors/actor.json',
            __DIR__ . '/../vectors/scopes.json',
            __DIR__ . '/../vectors/protocol_headers.json',
            __DIR__ . '/../vectors/signature_v1.json',
        ];

        foreach ($vectors as $vectorPath) {
            $payload = json_decode(file_get_contents($vectorPath), true, flags: JSON_THROW_ON_ERROR);
            $phpCanonical = CanonicalJson::encode($payload);

            $command = sprintf('node %s %s', escapeshellarg(__DIR__ . '/parity.js'), escapeshellarg($vectorPath));
            $output = [];
            $status = 0;
            exec($command, $output, $status);
            if ($status !== 0) {
                throw new RuntimeException("Node canonicalization failed for {$vectorPath}");
            }

            $nodeCanonical = implode("\n", $output);
            if ($phpCanonical !== $nodeCanonical) {
                throw new RuntimeException("Canonical JSON mismatch for {$vectorPath}");
            }
        }

        return __METHOD__;
    }
}
