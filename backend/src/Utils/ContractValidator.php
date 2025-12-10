<?php
// backend/src/Utils/ContractValidator.php

declare(strict_types=1);

namespace App\Utils;

use App\Adapters\AdapterException;

final class ContractValidator
{
    /** @var array<string, array> */
    private array $cache = [];

    public function validate(array $data, string $schemaPath): void
    {
        $schema = $this->loadSchema($schemaPath);
        $this->assertValid($data, $schema, $schemaPath, '$');
    }

    /**
     * @return array<string, mixed>
     */
    private function loadSchema(string $path): array
    {
        if (isset($this->cache[$path])) {
            return $this->cache[$path];
        }

        if (!is_file($path)) {
            throw new AdapterException("Schema not found: {$path}", 'contract_mismatch', false);
        }

        $json = file_get_contents($path);
        $decoded = json_decode((string) $json, true);
        if (!is_array($decoded)) {
            throw new AdapterException("Schema invalid JSON: {$path}", 'contract_mismatch', false);
        }

        return $this->cache[$path] = $decoded;
    }

    /**
     * @param mixed $value
     * @param array<string, mixed> $schema
     */
    private function assertValid($value, array $schema, string $schemaPath, string $pointer): void
    {
        if (isset($schema['$ref'])) {
            [$refSchema, $refPath] = $this->resolveRef($schema['$ref'], $schemaPath);
            $this->assertValid($value, $refSchema, $refPath, $pointer);
            return;
        }

        if (isset($schema['type'])) {
            $this->assertType($value, $schema['type'], $pointer);
        }

        if (isset($schema['enum']) && !in_array($value, (array) $schema['enum'], true)) {
            $allowed = implode(',', (array) $schema['enum']);
            $this->fail("{$pointer}: value not in enum [{$allowed}]", $pointer);
        }

        if (is_string($schema['type'] ?? null) && $schema['type'] === 'string') {
            if (isset($schema['minLength']) && strlen((string) $value) < (int) $schema['minLength']) {
                $this->fail("{$pointer}: string shorter than minLength", $pointer);
            }
        }

        if (is_string($schema['type'] ?? null) && ($schema['type'] === 'integer' || $schema['type'] === 'number')) {
            $numericValue = (int) $value;
            if (isset($schema['minimum']) && $numericValue < (int) $schema['minimum']) {
                $this->fail("{$pointer}: value below minimum", $pointer);
            }
            if (isset($schema['maximum']) && $numericValue > (int) $schema['maximum']) {
                $this->fail("{$pointer}: value above maximum", $pointer);
            }
        }

        if (($schema['type'] ?? null) === 'object' && is_array($value)) {
            $this->assertObject($value, $schema, $schemaPath, $pointer);
        }

        if (($schema['type'] ?? null) === 'array' && is_array($value)) {
            $this->assertArray($value, $schema, $schemaPath, $pointer);
        }
    }

    /**
     * @param array<string, mixed> $value
     * @param array<string, mixed> $schema
     */
    private function assertObject(array $value, array $schema, string $schemaPath, string $pointer): void
    {
        $required = (array) ($schema['required'] ?? []);
        foreach ($required as $req) {
            if (!array_key_exists($req, $value)) {
                $this->fail("{$pointer}: missing required property {$req}", $pointer);
            }
        }

        $properties = (array) ($schema['properties'] ?? []);
        foreach ($properties as $prop => $propSchema) {
            if (!array_key_exists($prop, $value)) {
                continue;
            }
            $this->assertValid($value[$prop], (array) $propSchema, $schemaPath, "{$pointer}.{$prop}");
        }

        $allowAdditional = $schema['additionalProperties'] ?? true;
        if ($allowAdditional === false) {
            $allowedKeys = array_keys($properties);
            foreach ($value as $key => $_) {
                if (!in_array($key, $allowedKeys, true)) {
                    $this->fail("{$pointer}: additional property {$key} is not allowed", $pointer);
                }
            }
        }
    }

    /**
     * @param array<int, mixed> $value
     * @param array<string, mixed> $schema
     */
    private function assertArray(array $value, array $schema, string $schemaPath, string $pointer): void
    {
        if (isset($schema['minItems']) && count($value) < (int) $schema['minItems']) {
            $this->fail("{$pointer}: fewer items than minItems", $pointer);
        }

        if (!isset($schema['items'])) {
            return;
        }

        $itemSchema = (array) $schema['items'];
        foreach ($value as $idx => $item) {
            $this->assertValid($item, $itemSchema, $schemaPath, "{$pointer}[{$idx}]");
        }
    }

    /**
     * @param mixed $value
     * @param string|array<int, string> $type
     */
    private function assertType($value, $type, string $pointer): void
    {
        $types = (array) $type;
        foreach ($types as $t) {
            if ($this->typeMatches($value, $t)) {
                return;
            }
        }

        $this->fail("{$pointer}: type mismatch", $pointer);
    }

    private function typeMatches($value, string $type): bool
    {
        return match ($type) {
            'object' => is_array($value),
            'array' => is_array($value),
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_numeric($value),
            'boolean' => is_bool($value),
            'null' => $value === null,
            default => true,
        };
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function resolveRef(string $ref, string $schemaPath): array
    {
        [$file, $fragment] = array_pad(explode('#', $ref, 2), 2, null);
        $baseDir = dirname($schemaPath);
        $targetPath = $file === '' || $file === null ? $schemaPath : realpath($baseDir . '/' . $file);
        if (!$targetPath) {
            throw new AdapterException("Schema ref not found: {$ref}", 'contract_mismatch', false);
        }
        $schema = $this->loadSchema($targetPath);

        if ($fragment) {
            $parts = array_values(array_filter(explode('/', $fragment)));
            foreach ($parts as $part) {
                if (!is_array($schema) || !array_key_exists($part, $schema)) {
                    throw new AdapterException("Schema ref path invalid: {$ref}", 'contract_mismatch', false);
                }
                $schema = $schema[$part];
            }
        }

        if (!is_array($schema)) {
            throw new AdapterException("Schema ref payload invalid: {$ref}", 'contract_mismatch', false);
        }

        return [$schema, $targetPath];
    }

    private function fail(string $message, string $pointer): void
    {
        throw new AdapterException($message, 'contract_mismatch', false, ['path' => $pointer]);
    }
}
