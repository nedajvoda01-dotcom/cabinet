<?php

/**
 * Architectural Boundary Tests
 * 
 * Enforces strict dependency rules:
 * 1. Platform cannot import adapters or UI
 * 2. Adapters cannot import platform
 * 3. UI cannot import platform
 * 4. All can import shared
 */

namespace Cabinet\Tests\Architecture;

class BoundaryTests
{
    private array $violations = [];
    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;
    }

    public function run(): bool
    {
        echo "🏗️  Running Architectural Boundary Tests...\n\n";

        $this->testPlatformBoundary();
        $this->testAdapterBoundary();
        $this->testUIBoundary();
        $this->testSharedDependencies();

        if (empty($this->violations)) {
            echo "✅ All architectural boundaries respected!\n";
            return true;
        }

        echo "❌ Architectural violations found:\n";
        foreach ($this->violations as $violation) {
            echo "  - {$violation}\n";
        }
        return false;
    }

    private function testPlatformBoundary(): void
    {
        echo "Testing Platform boundary...\n";
        
        $platformFiles = $this->findPHPFiles($this->projectRoot . '/platform/src');
        
        foreach ($platformFiles as $file) {
            $content = file_get_contents($file);
            
            // Platform should not import adapters
            if (preg_match('/use\s+Cabinet\\\\Adapters\\\\/', $content)) {
                $this->violations[] = "Platform imports adapter: " . basename($file);
            }
            
            // Platform should not import UI
            if (preg_match('/use\s+Cabinet\\\\UI\\\\/', $content)) {
                $this->violations[] = "Platform imports UI: " . basename($file);
            }
        }
    }

    private function testAdapterBoundary(): void
    {
        echo "Testing Adapter boundary...\n";
        
        $adapterDirs = glob($this->projectRoot . '/adapters/*', GLOB_ONLYDIR);
        
        foreach ($adapterDirs as $adapterDir) {
            $adapterFiles = $this->findPHPFiles($adapterDir);
            
            foreach ($adapterFiles as $file) {
                $content = file_get_contents($file);
                
                // Adapters should not import platform
                if (preg_match('/use\s+Cabinet\\\\Platform\\\\/', $content)) {
                    $this->violations[] = "Adapter imports platform: " . basename($file) . " in " . basename($adapterDir);
                }
            }
        }
    }

    private function testUIBoundary(): void
    {
        echo "Testing UI boundary...\n";
        
        $uiFiles = $this->findTSFiles($this->projectRoot . '/ui');
        
        foreach ($uiFiles as $file) {
            $content = file_get_contents($file);
            
            // UI should only communicate via API
            if (preg_match('/import.*from.*platform/', $content)) {
                $this->violations[] = "UI imports platform code: " . basename($file);
            }
        }
    }

    private function testSharedDependencies(): void
    {
        echo "Testing Shared dependencies...\n";
        
        // Shared should not depend on platform, adapters, or UI
        $sharedFiles = $this->findPHPFiles($this->projectRoot . '/shared');
        
        foreach ($sharedFiles as $file) {
            $content = file_get_contents($file);
            
            if (preg_match('/use\s+Cabinet\\\\Platform\\\\/', $content)) {
                $this->violations[] = "Shared imports platform: " . basename($file);
            }
            
            if (preg_match('/use\s+Cabinet\\\\Adapters\\\\/', $content)) {
                $this->violations[] = "Shared imports adapter: " . basename($file);
            }
        }
    }

    private function findPHPFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }

    private function findTSFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['ts', 'tsx'])) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
}

// Run tests if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $projectRoot = dirname(__DIR__, 2);
    $tests = new BoundaryTests($projectRoot);
    $success = $tests->run();
    exit($success ? 0 : 1);
}
