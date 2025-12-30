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
            $this->storagePath . '/audit',
            $this->storagePath . '/imports',
            $this->storagePath . '/listings'
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
    
    /**
     * Phase 6.4: Register import attempt
     * Returns: ['status' => 'new'|'duplicate'|'in_progress', 'import_id' => ...]
     */
    public function registerImport(string $contentHash, string $filename, string $source = 'inbox'): array {
        $importId = 'import_' . uniqid();
        $importFile = $this->storagePath . '/imports/' . $contentHash . '.json';
        
        // Check if this file was already imported
        if (file_exists($importFile)) {
            $existing = json_decode(file_get_contents($importFile), true);
            
            if ($existing['status'] === 'done') {
                return [
                    'status' => 'duplicate',
                    'import_id' => $existing['import_id'],
                    'message' => 'File already imported successfully'
                ];
            }
            
            if ($existing['status'] === 'pending') {
                $timeSinceStart = time() - $existing['started_at'];
                
                // If pending for more than 10 minutes, consider it stale
                if ($timeSinceStart < 600) {
                    return [
                        'status' => 'in_progress',
                        'import_id' => $existing['import_id'],
                        'message' => 'Import already in progress'
                    ];
                }
                
                // Stale import, allow re-import
            }
        }
        
        // Register new import
        $importData = [
            'import_id' => $importId,
            'content_hash' => $contentHash,
            'filename' => $filename,
            'source' => $source,
            'status' => 'pending',
            'started_at' => time(),
            'started_at_date' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($importFile, json_encode($importData, JSON_PRETTY_PRINT));
        
        return [
            'status' => 'new',
            'import_id' => $importId
        ];
    }
    
    /**
     * Phase 6.4: Mark import as completed
     */
    public function markImportDone(string $contentHash, array $stats = []): void {
        $importFile = $this->storagePath . '/imports/' . $contentHash . '.json';
        
        if (!file_exists($importFile)) {
            throw new \Exception("Import record not found for hash: $contentHash");
        }
        
        $importData = json_decode(file_get_contents($importFile), true);
        $importData['status'] = 'done';
        $importData['finished_at'] = time();
        $importData['finished_at_date'] = date('Y-m-d H:i:s');
        $importData['stats'] = $stats;
        
        file_put_contents($importFile, json_encode($importData, JSON_PRETTY_PRINT));
    }
    
    /**
     * Phase 6.4: Mark import as failed
     */
    public function markImportFailed(string $contentHash, string $error): void {
        $importFile = $this->storagePath . '/imports/' . $contentHash . '.json';
        
        if (!file_exists($importFile)) {
            throw new \Exception("Import record not found for hash: $contentHash");
        }
        
        $importData = json_decode(file_get_contents($importFile), true);
        $importData['status'] = 'failed';
        $importData['finished_at'] = time();
        $importData['finished_at_date'] = date('Y-m-d H:i:s');
        $importData['error'] = $error;
        
        file_put_contents($importFile, json_encode($importData, JSON_PRETTY_PRINT));
    }
    
    /**
     * Phase 6.4: Upsert listing (create or update by external_id)
     */
    public function upsertListing(array $listing): array {
        $externalId = $listing['external_id'] ?? null;
        
        if (!$externalId) {
            throw new \Exception('external_id is required for upsert');
        }
        
        // Check if listing exists
        $listingFile = $this->storagePath . '/listings/' . md5($externalId) . '.json';
        
        $isNew = !file_exists($listingFile);
        
        if ($isNew) {
            $listing['id'] = uniqid('listing_', true);
            $listing['created_at'] = time();
            $listing['created_by'] = $GLOBALS['current_user_id'] ?? 'system';
        } else {
            $existing = json_decode(file_get_contents($listingFile), true);
            $listing['id'] = $existing['id'];
            $listing['created_at'] = $existing['created_at'];
            $listing['created_by'] = $existing['created_by'];
        }
        
        $listing['updated_at'] = time();
        $listing['updated_by'] = $GLOBALS['current_user_id'] ?? 'system';
        
        file_put_contents($listingFile, json_encode($listing, JSON_PRETTY_PRINT));
        
        return [
            'id' => $listing['id'],
            'external_id' => $externalId,
            'action' => $isNew ? 'created' : 'updated'
        ];
    }
    
    /**
     * Phase 6.4: Batch upsert listings
     */
    public function upsertListingsBatch(array $listings): array {
        $results = [
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($listings as $listing) {
            try {
                $result = $this->upsertListing($listing);
                
                if ($result['action'] === 'created') {
                    $results['created']++;
                } else {
                    $results['updated']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'listing' => $listing,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}
