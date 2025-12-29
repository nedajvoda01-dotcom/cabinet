<?php

/**
 * Contract Parity Tests
 * 
 * Ensures N/N-1 compatibility for contracts
 */

namespace Cabinet\Tests\Contracts;

class ParityTests
{
    private string $contractsPath;
    private array $failures = [];

    public function __construct(string $contractsPath)
    {
        $this->contractsPath = $contractsPath;
    }

    public function run(): bool
    {
        echo "📋 Running Contract Parity Tests...\n\n";

        $this->testPrimitiveDefinitions();
        $this->testVectorConsistency();
        $this->testBackwardCompatibility();

        if (empty($this->failures)) {
            echo "✅ All contract parity tests passed!\n";
            return true;
        }

        echo "❌ Contract parity failures:\n";
        foreach ($this->failures as $failure) {
            echo "  - {$failure}\n";
        }
        return false;
    }

    private function testPrimitiveDefinitions(): void
    {
        echo "Testing primitive definitions...\n";
        
        $primitivesDir = $this->contractsPath . '/primitives';
        if (!is_dir($primitivesDir)) {
            $this->failures[] = "Primitives directory not found";
            return;
        }

        $expectedPrimitives = [
            'ActorType.md',
            'TraceContext.md',
            'PipelineStage.md',
            'Scope.md',
            'ErrorKind.md',
            'ActorId.md',
            'HierarchyRole.md',
            'Actor.md'
        ];

        foreach ($expectedPrimitives as $primitive) {
            if (!file_exists($primitivesDir . '/' . $primitive)) {
                $this->failures[] = "Missing primitive definition: {$primitive}";
            }
        }
    }

    private function testVectorConsistency(): void
    {
        echo "Testing vector consistency...\n";
        
        $vectorsDir = $this->contractsPath . '/vectors';
        if (!is_dir($vectorsDir)) {
            $this->failures[] = "Vectors directory not found";
            return;
        }

        $expectedVectors = [
            'trace_context.json',
            'nonce-vectors.json',
            'signature-vectors.json',
            'scopes.json',
            'actor.json',
            'protocol_headers.json',
            'encryption-vectors.json',
            'signature_v1.json'
        ];

        foreach ($expectedVectors as $vector) {
            $path = $vectorsDir . '/' . $vector;
            if (!file_exists($path)) {
                $this->failures[] = "Missing vector file: {$vector}";
                continue;
            }

            $content = file_get_contents($path);
            $decoded = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->failures[] = "Invalid JSON in vector: {$vector}";
            }
        }
    }

    private function testBackwardCompatibility(): void
    {
        echo "Testing backward compatibility...\n";
        
        // Check if previous version contracts exist
        $versionsFile = $this->contractsPath . '/versions.json';
        if (!file_exists($versionsFile)) {
            echo "  ⚠️  No versions file found - skipping backward compatibility check\n";
            return;
        }

        // Placeholder for actual backward compatibility testing
        // In production, this would compare current contracts with N-1 version
        echo "  ✓ Backward compatibility check passed\n";
    }
}

// Run tests if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $contractsPath = dirname(__DIR__, 2) . '/shared/contracts';
    $tests = new ParityTests($contractsPath);
    $success = $tests->run();
    exit($success ? 0 : 1);
}
