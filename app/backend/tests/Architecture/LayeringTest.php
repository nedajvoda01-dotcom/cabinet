<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Architecture;

use Cabinet\Backend\Tests\TestCase;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class LayeringTest extends TestCase
{
    private const BASE_PATH = __DIR__ . '/../../src';
    
    private const LAYER_RULES = [
        'Domain' => [
            'forbidden' => [
                'Cabinet\Backend\Http\\',
                'Cabinet\Backend\Infrastructure\\',
                'Cabinet\Backend\Bootstrap\\',
            ],
        ],
        'Application' => [
            'forbidden' => [
                'Cabinet\Backend\Http\\',
                'Cabinet\Backend\Bootstrap\\',
            ],
        ],
        'Http' => [
            'forbidden' => [
                'Cabinet\Backend\Infrastructure\\',
            ],
            'excluded_paths' => [
                'Http/Security/Pipeline',
                'Http/Kernel',
            ],
        ],
    ];

    public function testDomainLayerBoundaries(): void
    {
        $this->checkLayerBoundaries('Domain');
    }

    public function testApplicationLayerBoundaries(): void
    {
        $this->checkLayerBoundaries('Application');
    }

    public function testHttpLayerBoundaries(): void
    {
        $this->checkLayerBoundaries('Http');
    }

    /**
     * Check that a layer does not depend on forbidden namespaces
     */
    private function checkLayerBoundaries(string $layer): void
    {
        $layerPath = self::BASE_PATH . '/' . $layer;
        
        if (!is_dir($layerPath)) {
            throw new Exception("Layer directory not found: {$layerPath}");
        }

        $rules = self::LAYER_RULES[$layer] ?? null;
        if ($rules === null) {
            throw new Exception("No rules defined for layer: {$layer}");
        }

        $files = $this->collectPhpFiles($layerPath);
        $violations = [];

        foreach ($files as $file) {
            $relativeFile = str_replace(self::BASE_PATH . '/', '', $file);
            
            // Check if this file is in an excluded path
            if (isset($rules['excluded_paths'])) {
                $excluded = false;
                foreach ($rules['excluded_paths'] as $excludedPath) {
                    if (str_starts_with($relativeFile, $excludedPath)) {
                        $excluded = true;
                        break;
                    }
                }
                if ($excluded) {
                    continue;
                }
            }
            
            $content = file_get_contents($file);
            if ($content === false) {
                throw new Exception("Failed to read file: {$file}");
            }

            $imports = $this->extractImports($content);
            
            foreach ($imports as $import) {
                foreach ($rules['forbidden'] as $forbidden) {
                    if (str_starts_with($import, $forbidden)) {
                        $violations[] = sprintf(
                            "Illegal dependency: %s → %s (file: %s, import: %s)",
                            $layer,
                            $this->extractTargetLayer($forbidden),
                            $relativeFile,
                            $import
                        );
                    }
                }
            }
        }

        if (count($violations) > 0) {
            throw new Exception(
                sprintf(
                    "Layer boundary violations found:\n%s",
                    implode("\n", $violations)
                )
            );
        }
    }

    /**
     * Recursively collect all .php files in a directory
     * 
     * @return array<string>
     */
    private function collectPhpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Extract all use statements and fully-qualified class references
     * 
     * @return array<string>
     */
    private function extractImports(string $content): array
    {
        $imports = [];

        // Extract use statements
        if (preg_match_all('/^use\s+([^;]+);/m', $content, $matches)) {
            foreach ($matches[1] as $import) {
                // Handle aliased imports (use Foo\Bar as Baz)
                $import = trim($import);
                if (str_contains($import, ' as ')) {
                    $import = trim(explode(' as ', $import)[0]);
                }
                $imports[] = $import;
            }
        }

        // Extract fully-qualified class names (new \Cabinet\Backend\...)
        if (preg_match_all('/new\s+\\\\(Cabinet\\\\Backend\\\\[^(;\s]+)/', $content, $matches)) {
            foreach ($matches[1] as $fqcn) {
                $imports[] = str_replace('\\\\', '\\', $fqcn);
            }
        }

        return $imports;
    }

    /**
     * Extract the target layer name from a namespace
     */
    private function extractTargetLayer(string $namespace): string
    {
        if (str_contains($namespace, 'Infrastructure')) {
            return 'Infrastructure';
        }
        if (str_contains($namespace, 'Http')) {
            return 'Http';
        }
        if (str_contains($namespace, 'Bootstrap')) {
            return 'Bootstrap';
        }
        return 'Unknown';
    }
}
