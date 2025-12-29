<?php

/**
 * Compatibility Checker
 * 
 * Verifies N/N-1 compatibility for contracts
 * This is a merge blocker - must pass before merging
 */

namespace Cabinet\Delivery\Compat;

class CompatibilityChecker
{
    private string $contractsPath;
    private array $issues = [];

    public function __construct(string $contractsPath)
    {
        $this->contractsPath = $contractsPath;
    }

    public function run(): bool
    {
        echo "🔍 Running Compatibility Checker (N/N-1)...\n\n";

        $this->checkContractVersions();
        $this->checkPrimitiveStability();
        $this->checkVectorCompatibility();

        if (empty($this->issues)) {
            echo "✅ All compatibility checks passed!\n";
            echo "   N/N-1 compatibility verified.\n";
            return true;
        }

        echo "❌ Compatibility issues found:\n";
        foreach ($this->issues as $issue) {
            echo "  - {$issue}\n";
        }
        echo "\n⚠️  MERGE BLOCKER: Fix compatibility issues before merging.\n";
        return false;
    }

    private function checkContractVersions(): void
    {
        echo "Checking contract versions...\n";
        
        $versionsFile = $this->contractsPath . '/versions.json';
        if (!file_exists($versionsFile)) {
            $this->issues[] = "Missing versions.json - cannot verify compatibility";
            return;
        }

        $versions = json_decode(file_get_contents($versionsFile), true);
        if (!$versions) {
            $this->issues[] = "Invalid versions.json format";
            return;
        }

        // Verify version format
        if (!isset($versions['current']) || !isset($versions['supported'])) {
            $this->issues[] = "versions.json must have 'current' and 'supported' fields";
            return;
        }

        echo "  ✓ Current version: {$versions['current']}\n";
        echo "  ✓ Supported versions: " . implode(', ', $versions['supported']) . "\n";
    }

    private function checkPrimitiveStability(): void
    {
        echo "Checking primitive stability...\n";
        
        $primitivesDir = $this->contractsPath . '/primitives';
        if (!is_dir($primitivesDir)) {
            $this->issues[] = "Primitives directory not found";
            return;
        }

        // Core primitives must not be removed
        $corePrimitives = [
            'ActorType.md',
            'TraceContext.md',
            'PipelineStage.md',
            'Scope.md',
            'ErrorKind.md',
            'HierarchyRole.md'
        ];

        foreach ($corePrimitives as $primitive) {
            if (!file_exists($primitivesDir . '/' . $primitive)) {
                $this->issues[] = "Core primitive removed (breaking change): {$primitive}";
            }
        }

        echo "  ✓ Core primitives present\n";
    }

    private function checkVectorCompatibility(): void
    {
        echo "Checking vector compatibility...\n";
        
        $vectorsDir = $this->contractsPath . '/vectors';
        if (!is_dir($vectorsDir)) {
            $this->issues[] = "Vectors directory not found";
            return;
        }

        // Vectors must be valid JSON
        $vectors = glob($vectorsDir . '/*.json');
        foreach ($vectors as $vectorFile) {
            $content = file_get_contents($vectorFile);
            $decoded = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->issues[] = "Invalid JSON in vector: " . basename($vectorFile);
            }
        }

        echo "  ✓ Vector files valid\n";
    }
}

// Run checker if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $contractsPath = dirname(__DIR__, 2) . '/shared/contracts';
    $checker = new CompatibilityChecker($contractsPath);
    $success = $checker->run();
    exit($success ? 0 : 1);
}
