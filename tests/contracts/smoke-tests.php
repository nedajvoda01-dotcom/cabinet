<?php

/**
 * Contract Smoke Tests
 * 
 * Basic smoke tests for contract usage
 */

namespace Cabinet\Tests\Contracts;

class SmokeTests
{
    private string $projectRoot;
    private array $failures = [];

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;
    }

    public function run(): bool
    {
        echo "💨 Running Contract Smoke Tests...\n\n";

        $this->testPlatformContractUsage();
        $this->testAdapterContractUsage();
        $this->testSharedContractExports();

        if (empty($this->failures)) {
            echo "✅ All contract smoke tests passed!\n";
            return true;
        }

        echo "❌ Contract smoke test failures:\n";
        foreach ($this->failures as $failure) {
            echo "  - {$failure}\n";
        }
        return false;
    }

    private function testPlatformContractUsage(): void
    {
        echo "Testing platform contract usage...\n";
        
        // Verify platform imports contracts from shared
        $platformSrc = $this->projectRoot . '/platform/src';
        if (!is_dir($platformSrc)) {
            $this->failures[] = "Platform source directory not found";
            return;
        }

        // Check for contract imports
        $hasContractImports = false;
        $files = $this->findPHPFiles($platformSrc);
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (preg_match('/use\s+Cabinet\\\\Contracts\\\\/', $content)) {
                $hasContractImports = true;
                break;
            }
        }

        if (!$hasContractImports) {
            echo "  ⚠️  Platform doesn't seem to use contracts - this might be expected\n";
        }
    }

    private function testAdapterContractUsage(): void
    {
        echo "Testing adapter contract usage...\n";
        
        // Verify adapters use contracts for communication
        $adaptersDir = $this->projectRoot . '/adapters';
        if (!is_dir($adaptersDir)) {
            $this->failures[] = "Adapters directory not found";
            return;
        }

        // Adapters should only depend on shared contracts, not platform
        $adapterDirs = glob($adaptersDir . '/*', GLOB_ONLYDIR);
        foreach ($adapterDirs as $adapterDir) {
            $files = $this->findPHPFiles($adapterDir);
            foreach ($files as $file) {
                $content = file_get_contents($file);
                if (preg_match('/use\s+Cabinet\\\\Platform\\\\/', $content)) {
                    $this->failures[] = basename($adapterDir) . " adapter imports platform code";
                }
            }
        }
    }

    private function testSharedContractExports(): void
    {
        echo "Testing shared contract exports...\n";
        
        $contractsImpl = $this->projectRoot . '/shared/contracts/implementations/php';
        if (!is_dir($contractsImpl)) {
            echo "  ⚠️  No PHP contract implementations found\n";
            return;
        }

        // Verify implementations exist
        $files = $this->findPHPFiles($contractsImpl);
        if (empty($files)) {
            echo "  ⚠️  No contract implementation files found\n";
        } else {
            echo "  ✓ Found " . count($files) . " contract implementation files\n";
        }
    }

    private function findPHPFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
}

// Run tests if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $projectRoot = dirname(__DIR__, 2);
    $tests = new SmokeTests($projectRoot);
    $success = $tests->run();
    exit($success ? 0 : 1);
}
