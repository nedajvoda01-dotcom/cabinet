<?php
/**
 * Storage - Minimal state management
 * Handles runs and audit logs
 */

class Storage {
    private string $storagePath;
    
    public function __construct(string $storagePath = '/var/lib/cabinet/storage') {
        $this->storagePath = $storagePath;
        $this->ensureDirectories();
    }
    
    private function ensureDirectories(): void {
        $dirs = [
            $this->storagePath,
            $this->storagePath . '/runs',
            $this->storagePath . '/audit'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Save a run record
     */
    public function saveRun(string $runId, array $data): void {
        $data['run_id'] = $runId;
        $data['timestamp'] = time();
        
        $file = $this->storagePath . '/runs/' . $runId . '.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get a run record
     */
    public function getRun(string $runId): ?array {
        $file = $this->storagePath . '/runs/' . $runId . '.json';
        
        if (!file_exists($file)) {
            return null;
        }
        
        $content = file_get_contents($file);
        return json_decode($content, true);
    }
    
    /**
     * Log an audit entry
     */
    public function logAudit(array $entry): void {
        $entry['timestamp'] = time();
        $entry['date'] = date('Y-m-d H:i:s');
        
        $date = date('Y-m-d');
        $file = $this->storagePath . '/audit/' . $date . '.log';
        
        $line = json_encode($entry) . "\n";
        file_put_contents($file, $line, FILE_APPEND);
    }
    
    /**
     * Get audit logs for a date
     */
    public function getAuditLog(string $date): array {
        $file = $this->storagePath . '/audit/' . $date . '.log';
        
        if (!file_exists($file)) {
            return [];
        }
        
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $entries = [];
        
        foreach ($lines as $line) {
            $entries[] = json_decode($line, true);
        }
        
        return $entries;
    }
}
