<?php
/**
 * ResultGate - Result filtering and post-processing
 * Applied after adapter response
 */

class ResultGate {
    private Policy $policy;
    
    public function __construct(Policy $policy) {
        $this->policy = $policy;
    }
    
    /**
     * Filter result based on scopes and permissions
     */
    public function filter(array $result, string $capability, array $scopes): array {
        // Apply scope-based filtering
        $filtered = $result;
        
        // If no admin scope, remove sensitive fields
        if (!in_array('admin', $scopes)) {
            $filtered = $this->removeSensitiveFields($filtered);
        }
        
        // Add metadata
        $filtered = [
            'data' => $filtered,
            'filtered' => true,
            'capability' => $capability,
            'timestamp' => time()
        ];
        
        return $filtered;
    }
    
    /**
     * Remove sensitive fields from result
     */
    private function removeSensitiveFields(array $data): array {
        $sensitiveFields = ['password', 'secret', 'token', 'api_key', 'private_key'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }
        
        // Recursively filter nested arrays
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->removeSensitiveFields($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Validate result structure
     */
    public function validate(array $result): bool {
        // Basic validation - result should be an array
        if (!is_array($result)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Transform error to safe format
     */
    public function transformError(string $message, int $code = 500): array {
        return [
            'error' => true,
            'message' => $message,
            'code' => $code,
            'timestamp' => time()
        ];
    }
}
