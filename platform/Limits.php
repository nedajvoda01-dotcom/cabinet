<?php
/**
 * Limits - Rate limiting, timeouts, and size limits
 * Applied before adapter invocation
 */

class Limits {
    private Policy $policy;
    private Storage $storage;
    private array $config;
    
    public function __construct(Policy $policy, Storage $storage, array $config) {
        $this->policy = $policy;
        $this->storage = $storage;
        $this->config = $config;
    }
    
    /**
     * Check rate limit for a request
     */
    public function checkRateLimit(string $capability, string $role, string $identifier): bool {
        $limit = $this->policy->getRateLimit($capability, $role);
        $key = "rate_limit:{$identifier}:{$capability}";
        
        // Simple file-based rate limiting (in production, use Redis)
        $rateLimitFile = sys_get_temp_dir() . '/' . md5($key) . '.json';
        
        $now = time();
        $window = 60; // 1 minute window
        
        $requests = [];
        if (file_exists($rateLimitFile)) {
            $content = file_get_contents($rateLimitFile);
            $requests = json_decode($content, true) ?? [];
        }
        
        // Remove old requests outside window
        $requests = array_filter($requests, function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        // Check if limit exceeded
        if (count($requests) >= $limit) {
            return false;
        }
        
        // Add current request
        $requests[] = $now;
        file_put_contents($rateLimitFile, json_encode($requests));
        
        return true;
    }
    
    /**
     * Check request size
     */
    public function checkRequestSize(int $size, string $role): bool {
        $maxSize = $this->policy->getMaxRequestSize($role);
        return $size <= $maxSize;
    }
    
    /**
     * Get timeout for request
     */
    public function getTimeout(string $capability): int {
        // Use default timeout from config
        return $this->config['default_timeout'] ?? 30;
    }
    
    /**
     * Enforce timeout on operation
     */
    public function enforceTimeout(callable $operation, int $timeout) {
        // Set timeout
        set_time_limit($timeout);
        
        try {
            return $operation();
        } catch (Exception $e) {
            throw new Exception("Operation timeout or failed: " . $e->getMessage());
        }
    }
}
