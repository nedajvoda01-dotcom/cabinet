<?php
// tests/unit/backend/legacyBoundaries.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class LegacyBoundariesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = realpath(__DIR__ . '/../../..');
    }

    public function test_legacy_quarantine_directories_exist(): void
    {
        $this->assertDirectoryExists($this->backendLegacyPath(), 'backend/_legacy must exist as the quarantine');
        $this->assertDirectoryExists($this->frontendLegacyPath(), 'frontend/src/_legacy must exist as the quarantine');
    }

    public function test_non_legacy_code_does_not_reference_legacy(): void
    {
        foreach ($this->phpFiles($this->backendSrcPath()) as $file) {
            if (str_contains($file, DIRECTORY_SEPARATOR . '_legacy' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $contents = file_get_contents($file);

            $this->assertFalse(
                (bool) preg_match('/^\s*use\s+[^;]*_legacy[^;]*;/mi', $contents),
                "File {$file} must not import anything from _legacy"
            );

            $this->assertFalse(
                (bool) preg_match('/\b(require|include)(_once)?\s*\(?[^;]*_legacy[^;]*[);]/i', $contents),
                "File {$file} must not include or require _legacy paths"
            );
        }
    }

    private function backendSrcPath(): string
    {
        return $this->root . '/backend/src';
    }

    private function backendLegacyPath(): string
    {
        return $this->root . '/backend/_legacy';
    }

    private function frontendLegacyPath(): string
    {
        return $this->root . '/frontend/src/_legacy';
    }

    /**
     * @return iterable<int, string>
     */
    private function phpFiles(string $root): iterable
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                yield $file->getPathname();
            }
        }
    }
}
