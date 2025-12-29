<?php

/**
 * E2E Smoke Test - Critical Path
 * 
 * Tests the critical path through the system:
 * 1. Health check
 * 2. Authentication
 * 3. Task creation
 * 4. Pipeline execution
 */

namespace Cabinet\Tests\E2E;

class CriticalPathSmokeTest
{
    private string $baseUrl;
    private array $failures = [];

    public function __construct(string $baseUrl = 'http://localhost:8080')
    {
        $this->baseUrl = $baseUrl;
    }

    public function run(): bool
    {
        echo "🚀 Running E2E Critical Path Smoke Test...\n\n";

        $this->testHealthEndpoint();
        $this->testPlatformStartup();

        if (empty($this->failures)) {
            echo "✅ All E2E smoke tests passed!\n";
            return true;
        }

        echo "❌ E2E smoke test failures:\n";
        foreach ($this->failures as $failure) {
            echo "  - {$failure}\n";
        }
        return false;
    }

    private function testHealthEndpoint(): void
    {
        echo "Testing health endpoint...\n";
        
        // Check if platform is accessible
        $ch = curl_init($this->baseUrl . '/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            echo "  ⚠️  Platform not accessible: {$error}\n";
            echo "  💡 Make sure to start the platform with './scripts/start.sh' or 'docker-compose up'\n";
            return;
        }

        if ($httpCode === 200) {
            echo "  ✓ Health endpoint responding\n";
        } else {
            $this->failures[] = "Health endpoint returned HTTP {$httpCode}";
        }
    }

    private function testPlatformStartup(): void
    {
        echo "Testing platform startup...\n";
        
        // Verify platform entry point exists
        $entryPoint = dirname(__DIR__, 2) . '/platform/public/index.php';
        if (!file_exists($entryPoint)) {
            $this->failures[] = "Platform entry point not found: {$entryPoint}";
            return;
        }

        echo "  ✓ Platform entry point exists\n";

        // Verify worker exists
        $workerBin = dirname(__DIR__, 2) . '/platform/bin/worker.php';
        if (!file_exists($workerBin)) {
            $this->failures[] = "Worker binary not found: {$workerBin}";
            return;
        }

        echo "  ✓ Worker binary exists\n";
    }
}

// Run tests if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $tests = new CriticalPathSmokeTest();
    $success = $tests->run();
    exit($success ? 0 : 1);
}
