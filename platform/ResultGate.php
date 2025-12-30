<?php
/**
 * ResultGate - Result filtering and post-processing
 * Applied after adapter response
 * Phase 6: Enhanced with allowlist fields, size limits, and HTML/JS blocking
 */

class ResultGate {
    private Policy $policy;
    private array $capabilitiesConfig;
    private int $maxResponseSize;
    private int $maxArraySize;
    
    public function __construct(Policy $policy, array $capabilitiesConfig = [], array $config = []) {
        $this->policy = $policy;
        $this->capabilitiesConfig = $capabilitiesConfig;
        $this->maxResponseSize = $config['max_response_size'] ?? 10485760; // 10MB default
        $this->maxArraySize = $config['max_array_size'] ?? 1000; // 1000 items default
    }
    
    /**
     * Filter result based on scopes and permissions
     * Phase 6: Enhanced with allowlist fields and size limits
     */
    public function filter(array $result, string $capability, array $scopes): array {
        // Phase 6: Check response size before processing
        $this->checkResponseSize($result);
        
        // Apply scope-based filtering
        $filtered = $result;
        
        // Phase 6: Apply allowlist fields if configured
        $filtered = $this->applyAllowlist($filtered, $capability);
        
        // Phase 6: Block dangerous content (HTML/JS)
        $filtered = $this->sanitizeDangerousContent($filtered);
        
        // Phase 6: Limit array sizes
        $filtered = $this->limitArraySizes($filtered);
        
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
    
    /**
     * Phase 6: Check response size limit
     */
    private function checkResponseSize(array $result): void {
        $size = strlen(json_encode($result));
        
        if ($size > $this->maxResponseSize) {
            throw new \Exception("Response size ($size bytes) exceeds maximum allowed ({$this->maxResponseSize} bytes)");
        }
    }
    
    /**
     * Phase 6: Apply allowlist fields based on capability configuration
     */
    private function applyAllowlist(array $data, string $capability): array {
        // Check if capability has allowlist configuration
        if (!isset($this->capabilitiesConfig['capabilities'][$capability]['allowed_fields'])) {
            // No allowlist configured, return as is
            return $data;
        }
        
        $allowedFields = $this->capabilitiesConfig['capabilities'][$capability]['allowed_fields'];
        
        // If allowlist is empty or *, allow all fields
        if (empty($allowedFields) || in_array('*', $allowedFields)) {
            return $data;
        }
        
        // Filter data to only include allowed fields
        return $this->filterByAllowlist($data, $allowedFields);
    }
    
    /**
     * Recursively filter data by allowlist
     */
    private function filterByAllowlist(array $data, array $allowedFields): array {
        $filtered = [];
        
        // Handle array of objects (list)
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $item) {
                $filtered[] = $this->filterSingleItem($item, $allowedFields);
            }
        } else {
            // Single object
            $filtered = $this->filterSingleItem($data, $allowedFields);
        }
        
        return $filtered;
    }
    
    /**
     * Filter single item by allowed fields
     */
    private function filterSingleItem(array $item, array $allowedFields): array {
        $filtered = [];
        
        foreach ($allowedFields as $field) {
            if (isset($item[$field])) {
                $filtered[$field] = $item[$field];
            }
        }
        
        return $filtered;
    }
    
    /**
     * Phase 6: Sanitize dangerous content (HTML/JS)
     */
    private function sanitizeDangerousContent(array $data): array {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Block if contains script tags or HTML tags that could execute JS
                if ($this->containsDangerousContent($value)) {
                    $data[$key] = '[BLOCKED: Dangerous content detected]';
                }
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeDangerousContent($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Check if string contains dangerous content
     */
    private function containsDangerousContent(string $value): bool {
        // Patterns for dangerous content
        $dangerousPatterns = [
            '/<script[\s\S]*?>/i',           // Script tags
            '/<iframe[\s\S]*?>/i',           // Iframes
            '/javascript:/i',                // JavaScript protocol
            '/on\w+\s*=\s*["\']?[^"\']*["\']?/i', // Event handlers (onclick, etc)
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Phase 6: Limit array sizes to prevent memory issues
     */
    private function limitArraySizes(array $data): array {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // If array is too large, truncate it
                if (count($value) > $this->maxArraySize) {
                    $data[$key] = array_slice($value, 0, $this->maxArraySize);
                    $data[$key . '_truncated'] = true;
                    $data[$key . '_total_count'] = count($value);
                } else {
                    // Recursively limit nested arrays
                    $data[$key] = $this->limitArraySizes($value);
                }
            }
        }
        
        return $data;
    }
}
