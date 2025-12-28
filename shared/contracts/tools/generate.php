<?php

declare(strict_types=1);

$root = realpath(__DIR__ . '/..');
$definitions = require $root . '/primitives/definitions.php';

ksort($definitions);

function ensureDirectory(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

function writeFileIfChanged(string $path, string $contents): void
{
    $current = file_exists($path) ? file_get_contents($path) : null;
    if ($current === $contents) {
        return;
    }

    ensureDirectory(dirname($path));
    file_put_contents($path, $contents);
}

function canonicalJson(mixed $value): string
{
    if (is_array($value)) {
        $isList = array_keys($value) === range(0, count($value) - 1);
        if ($isList) {
            $canonicalList = array_map(fn ($item) => json_decode(canonicalJson($item), true, flags: JSON_THROW_ON_ERROR), $value);
            return json_encode($canonicalList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        ksort($value);
        $canonicalMap = [];
        foreach ($value as $key => $item) {
            $canonicalMap[$key] = json_decode(canonicalJson($item), true, flags: JSON_THROW_ON_ERROR);
        }

        return json_encode($canonicalMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function generateMarkdown(array $definitions, string $root): void
{
    foreach ($definitions as $name => $definition) {
        $lines = ["# {$name}", ''];
        $lines[] = "Kind: **{$definition['kind']}**";
        if (!empty($definition['description'])) {
            $lines[] = '';
            $lines[] = $definition['description'];
        }

        if (($definition['kind'] ?? '') === 'enum') {
            $lines[] = '';
            $lines[] = '## Allowed Values';
            foreach ($definition['values'] as $value) {
                $lines[] = "- `{$value}`";
            }
        }

        if (($definition['kind'] ?? '') === 'string') {
            $lines[] = '';
            $lines[] = '## Constraints';
            foreach ($definition['constraints'] as $constraint) {
                $lines[] = "- {$constraint}";
            }
        }

        if (($definition['kind'] ?? '') === 'object') {
            $lines[] = '';
            $lines[] = '## Fields';
            foreach ($definition['fields'] as $fieldName => $field) {
                $required = $field['required'] ? 'required' : 'optional';
                $lines[] = "- `{$fieldName}` ({$field['type']}, {$required}) — {$field['description']}";
                if (!empty($field['constraints'])) {
                    foreach ($field['constraints'] as $constraint) {
                        $lines[] = "  - constraint: {$constraint}";
                    }
                }
            }
        }

        if (!empty($definition['examples'])) {
            $lines[] = '';
            $lines[] = '## Examples';
            foreach ($definition['examples'] as $example) {
                $lines[] = "- `{$example}`";
            }
        }

        if (!empty($definition['fields'])) {
            $objectExamples = array_filter(array_map(fn ($field) => $field['example'] ?? null, $definition['fields']));
            if (!empty($objectExamples)) {
                $lines[] = '';
                $lines[] = '## Example Payload';
                $payload = [];
                foreach ($definition['fields'] as $fieldName => $field) {
                    if (array_key_exists('example', $field)) {
                        $payload[$fieldName] = $field['example'];
                    }
                }
                $lines[] = '```json';
                $lines[] = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $lines[] = '```';
            }
        }

        $content = implode("\n", $lines) . "\n";
        writeFileIfChanged("{$root}/primitives/{$name}.md", $content);
    }
}

function generatePhp(array $definitions, string $root): void
{
    $namespace = 'Cabinet\\Contracts';
    ensureDirectory("{$root}/implementations/php");

    foreach ($definitions as $name => $definition) {
        $contents = ["<?php", '', "declare(strict_types=1);", '', "namespace {$namespace};", ''];

        if ($definition['kind'] === 'enum') {
            $contents[] = "enum {$name}: string";
            $contents[] = '{';
            foreach ($definition['values'] as $value) {
                $caseName = strtoupper(str_replace('-', '_', str_replace('.', '_', $value)));
                $contents[] = "    case {$caseName} = '{$value}';";
            }
            $contents[] = '}';
        } elseif ($definition['kind'] === 'string') {
            $contents[] = "final class {$name}";
            $contents[] = '{';
            $contents[] = '    public function __construct(private readonly string $value)';
            $contents[] = '    {';
            $contents[] = '        $this->assert($value);';
            $contents[] = '    }';
            $contents[] = '';
            $contents[] = '    public static function fromString(string $value): self';
            $contents[] = '    {';
            $contents[] = '        return new self($value);';
            $contents[] = '    }';
            $contents[] = '';
            $contents[] = '    public function value(): string';
            $contents[] = '    {';
            $contents[] = '        return $this->value;';
            $contents[] = '    }';
            $contents[] = '';
            $contents[] = '    private function assert(string $value): void';
            $contents[] = '    {';
            foreach ($definition['constraints'] as $constraint) {
                if ($constraint === 'non-empty') {
                    $contents[] = '        if ($value === "") {';
                    $contents[] = "            throw new \\InvalidArgumentException('Value must be non-empty.');";
                    $contents[] = '        }';
                } elseif (str_starts_with($constraint, 'max_length:')) {
                    $max = (int) substr($constraint, strlen('max_length:'));
                    $contents[] = "        if (mb_strlen(\$value) > {$max}) {";
                    $contents[] = "            throw new \\InvalidArgumentException('Value exceeds maximum length of {$max}.');";
                    $contents[] = '        }';
                } elseif ($constraint === 'ascii') {
                    $contents[] = '        if (!mb_check_encoding($value, "ASCII")) {';
                    $contents[] = "            throw new \\InvalidArgumentException('Value must be ASCII.');";
                    $contents[] = '        }';
                } elseif ($constraint === 'lowercase') {
                    $contents[] = '        if ($value !== strtolower($value)) {';
                    $contents[] = "            throw new \\InvalidArgumentException('Value must be lowercase.');";
                    $contents[] = '        }';
                } elseif ($constraint === 'dot-delimited segments') {
                    $contents[] = '        $segments = explode(".", $value);';
                    $contents[] = '        foreach ($segments as $segment) {';
                    $contents[] = '            if ($segment === "") {';
                    $contents[] = "                throw new \\InvalidArgumentException('Scope segments must be non-empty.');";
                    $contents[] = '            }';
                    $contents[] = '        }';
                } elseif ($constraint === 'segments use [a-z0-9]') {
                    $contents[] = '        foreach (explode(".", $value) as $segment) {';
                    $contents[] = '            if (!preg_match("/^[a-z0-9]+$/", $segment)) {';
                    $contents[] = "                throw new \\InvalidArgumentException('Scope segments must use [a-z0-9].');";
                    $contents[] = '            }';
                    $contents[] = '        }';
                }
            }
            $contents[] = '    }';
            $contents[] = '}';
        } elseif ($definition['kind'] === 'object') {
            $contents[] = "final class {$name}";
            $contents[] = '{';

            $signatureParts = [];
            $constructorChecks = [];
            foreach ($definition['fields'] as $fieldName => $field) {
                $type = $field['type'];
                $phpType = match ($type) {
                    'string', 'ActorId', 'Scope' => 'string',
                    default => $type,
                };
                $nullable = $field['required'] ? '' : '?';
                $signatureParts[] = "public readonly {$nullable}{$phpType} \${$fieldName}";

                if ($field['required']) {
                    $constructorChecks[] = "        if (\${$fieldName} === null) {";
                    $constructorChecks[] = "            throw new \\InvalidArgumentException('{$fieldName} is required.');";
                    $constructorChecks[] = '        }';
                }
                if ($type === 'ActorId') {
                    $constructorChecks[] = "        new ActorId((string) \${$fieldName});";
                }
                if ($type === 'Scope') {
                    $constructorChecks[] = "        new Scope((string) \${$fieldName});";
                }
                if ($type === 'string' && !empty($field['constraints'])) {
                    foreach ($field['constraints'] as $constraint) {
                        if ($constraint === 'non-empty') {
                            $constructorChecks[] = "        if (\${$fieldName} === '') {";
                            $constructorChecks[] = "            throw new \\InvalidArgumentException('{$fieldName} must be non-empty.');";
                            $constructorChecks[] = '        }';
                        }
                    }
                }
            }

            $contents[] = '    public function __construct(' . implode(', ', $signatureParts) . ')';
            $contents[] = '    {';
            foreach ($constructorChecks as $line) {
                $contents[] = $line;
            }
            $contents[] = '    }';
            $contents[] = '';

            $contents[] = '    public function toArray(): array';
            $contents[] = '    {';
            $contents[] = '        $data = [];';
            foreach ($definition['fields'] as $fieldName => $_) {
                $contents[] = "        if (\$this->{$fieldName} !== null) {";
                $contents[] = "            \$data['{$fieldName}'] = \$this->{$fieldName};";
                $contents[] = '        }';
            }
            $contents[] = '        return $data;';
            $contents[] = '    }';
            $contents[] = '}';
        }

        writeFileIfChanged("{$root}/implementations/php/{$name}.php", implode("\n", $contents) . "\n");
    }

    $canonical = sprintf(<<<'PHP'
<?php

declare(strict_types=1);

namespace %s;

final class CanonicalJson
{
    public static function encode(mixed $value): string
    {
        return self::encodeValue($value);
    }

    private static function encodeValue(mixed $value): string
    {
        if (is_array($value)) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                $normalized = array_map(fn ($item) => json_decode(self::encodeValue($item), true, flags: JSON_THROW_ON_ERROR), $value);
                return json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            ksort($value);
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = json_decode(self::encodeValue($item), true, flags: JSON_THROW_ON_ERROR);
            }

            return json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
PHP, $namespace);

    writeFileIfChanged("{$root}/implementations/php/CanonicalJson.php", $canonical . "\n");
}

function generateTypeScript(array $definitions, string $root): void
{
    ensureDirectory("{$root}/implementations/ts");
    $tsLines = [];

    foreach ($definitions as $name => $definition) {
        if ($definition['kind'] === 'enum') {
            $tsLines[] = "export enum {$name} {";
            foreach ($definition['values'] as $value) {
                $key = strtoupper(str_replace(['-', '.'], '_', $value));
                $tsLines[] = "  {$key} = '{$value}',";
            }
            $tsLines[] = "}";
            $tsLines[] = '';
        } elseif ($definition['kind'] === 'string') {
            $tsLines[] = "export type {$name} = string;";
            $tsLines[] = '';
        } elseif ($definition['kind'] === 'object') {
            $tsLines[] = "export interface {$name} {";
            foreach ($definition['fields'] as $fieldName => $field) {
                $optional = $field['required'] ? '' : '?';
                $tsType = match ($field['type']) {
                    'string', 'ActorId', 'Scope' => 'string',
                    default => $field['type'],
                };
                $tsLines[] = "  {$fieldName}{$optional}: {$tsType};";
            }
            $tsLines[] = "}";
            $tsLines[] = '';
        }
    }

    $tsLines[] = 'export function canonicalJson(value: unknown): string {';
    $tsLines[] = '  return encodeValue(value);';
    $tsLines[] = '}';
    $tsLines[] = '';
    $tsLines[] = 'function encodeValue(value: unknown): string {';
    $tsLines[] = '  if (Array.isArray(value)) {';
    $tsLines[] = '    const normalized = value.map((item) => JSON.parse(encodeValue(item)));';
    $tsLines[] = '    return JSON.stringify(normalized);';
    $tsLines[] = '  }';
    $tsLines[] = '';
    $tsLines[] = '  if (value !== null && typeof value === "object") {';
    $tsLines[] = '    const entries = Object.entries(value as Record<string, unknown>).sort(([a], [b]) => a.localeCompare(b));';
    $tsLines[] = '    const normalized: Record<string, unknown> = {};';
    $tsLines[] = '    for (const [key, item] of entries) {';
    $tsLines[] = '      normalized[key] = JSON.parse(encodeValue(item));';
    $tsLines[] = '    }';
    $tsLines[] = '    return JSON.stringify(normalized);';
    $tsLines[] = '  }';
    $tsLines[] = '';
    $tsLines[] = '  return JSON.stringify(value);';
    $tsLines[] = '}';

    writeFileIfChanged("{$root}/implementations/ts/index.ts", implode("\n", $tsLines) . "\n");

    $jsLines = ["'use strict';", ''];
    $exported = ['canonicalJson'];

    foreach ($definitions as $name => $definition) {
        if ($definition['kind'] === 'enum') {
            $pairs = array_map(fn ($value) => sprintf("  %s: '%s'", strtoupper(str_replace(['-', '.'], '_', $value)), $value), $definition['values']);
            $jsLines[] = "const {$name} = {";
            $jsLines[] = implode(",\n", $pairs);
            $jsLines[] = '};';
            $jsLines[] = '';
            $exported[] = $name;
        }
    }

    $jsLines[] = 'function canonicalJson(value) {';
    $jsLines[] = '  return encodeValue(value);';
    $jsLines[] = '}';
    $jsLines[] = '';
    $jsLines[] = 'function encodeValue(value) {';
    $jsLines[] = '  if (Array.isArray(value)) {';
    $jsLines[] = '    const normalized = value.map((item) => JSON.parse(encodeValue(item)));';
    $jsLines[] = '    return JSON.stringify(normalized);';
    $jsLines[] = '  }';
    $jsLines[] = '';
    $jsLines[] = '  if (value !== null && typeof value === "object") {';
    $jsLines[] = '    const entries = Object.entries(value).sort(([a], [b]) => a.localeCompare(b));';
    $jsLines[] = '    const normalized = {};';
    $jsLines[] = '    for (const [key, item] of entries) {';
    $jsLines[] = '      normalized[key] = JSON.parse(encodeValue(item));';
    $jsLines[] = '    }';
    $jsLines[] = '    return JSON.stringify(normalized);';
    $jsLines[] = '  }';
    $jsLines[] = '';
    $jsLines[] = '  return JSON.stringify(value);';
    $jsLines[] = '}';
    $jsLines[] = '';
    $jsLines[] = 'module.exports = { ' . implode(', ', $exported) . ' };';
    writeFileIfChanged("{$root}/implementations/ts/index.cjs", implode("\n", $jsLines) . "\n");
}

generateMarkdown($definitions, $root);
generatePhp($definitions, $root);
generateTypeScript($definitions, $root);

