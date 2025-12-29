<?php

/**
 * Text Audit Tool
 * 
 * Scans codebase for forbidden language patterns.
 * Fails with non-zero exit code if violations found.
 */

function scanDirectory(string $basePath, array $extensions): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $ext = pathinfo($file->getPathname(), PATHINFO_EXTENSION);
        if (in_array($ext, $extensions, true)) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

function scanFile(string $filePath, array $forbiddenTokens): array
{
    $violations = [];
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);

    foreach ($forbiddenTokens as $token) {
        $pattern = '/\b' . preg_quote($token, '/') . '\b/i';
        
        foreach ($lines as $lineNum => $lineContent) {
            if (preg_match($pattern, $lineContent)) {
                if (isAllowedException($token, $lineContent)) {
                    continue;
                }
                
                $violations[] = [
                    'file' => $filePath,
                    'line' => $lineNum + 1,
                    'token' => $token,
                    'content' => trim($lineContent),
                ];
            }
        }
    }

    return $violations;
}

function isAllowedException(string $token, string $line): bool
{
    $token = strtolower($token);
    $line = strtolower($line);
    
    if ($token === 'session') {
        $technicalContexts = [
            'encryption session',
            'crypto session',
            'session key',
            'key exchange',
            'session binding',
            'valid session',
            'session validity',
            'sessionid',
            'jwt',
            'bound to the session',
            'session-bound',
            'short-lived',
            'session validation',
        ];
        
        foreach ($technicalContexts as $context) {
            if (strpos($line, $context) !== false) {
                return true;
            }
        }
    }
    
    if ($token === 'review') {
        $technicalContexts = [
            'code review',
            'peer review',
            'manual review',
        ];
        
        foreach ($technicalContexts as $context) {
            if (strpos($line, $context) !== false) {
                return false;
            }
        }
        
        if (strpos($line, 'review') !== false && 
            (strpos($line, 'scope') !== false || 
             strpos($line, 'invariant') !== false ||
             strpos($line, 'constraint') !== false)) {
            return true;
        }
    }
    
    return false;
}

function main(): int
{
    $basePath = dirname(__DIR__);
    $extensions = ['php', 'md', 'ts', 'tsx'];
    
    $forbiddenTokens = [
        'AI',
        'agent',
        'Copilot',
        'Claude',
        'Anthropic',
        'summary',
        'successfully',
        'PR',
        'pull request',
        'merge',
        'review',
        'GitHub',
        'workflow',
        'session',
    ];

    $excludePaths = [
        '/vendor/',
        '/node_modules/',
        '/.git/',
        '/dist/',
        '/build/',
        '/tools/text_audit.php',
    ];

    echo "Scanning for forbidden language...\n\n";

    $files = scanDirectory($basePath, $extensions);
    $allViolations = [];

    foreach ($files as $file) {
        $skip = false;
        foreach ($excludePaths as $excludePath) {
            if (strpos($file, $excludePath) !== false) {
                $skip = true;
                break;
            }
        }
        
        if ($skip) {
            continue;
        }

        $violations = scanFile($file, $forbiddenTokens);
        $allViolations = array_merge($allViolations, $violations);
    }

    if (empty($allViolations)) {
        echo "✓ No forbidden language found.\n";
        return 0;
    }

    echo "✗ Found " . count($allViolations) . " violation(s):\n\n";

    foreach ($allViolations as $violation) {
        $relativePath = str_replace($basePath . '/', '', $violation['file']);
        echo sprintf(
            "%s:%d - Token: '%s'\n",
            $relativePath,
            $violation['line'],
            $violation['token']
        );
    }

    return 1;
}

exit(main());
