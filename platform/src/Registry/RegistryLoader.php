<?php
/**
 * Registry Loader
 * Reads and caches registry YAML files
 * Phase 3: Registry as source of truth
 */

namespace Platform\Registry;

class RegistryLoader {
    private string $registryPath;
    private array $cache = [];
    private bool $devMode;
    
    public function __construct(string $registryPath, bool $devMode = false) {
        $this->registryPath = $registryPath;
        $this->devMode = $devMode;
    }
    
    /**
     * Load a registry file
     */
    public function load(string $filename): array {
        $cacheKey = $filename;
        
        // In dev mode, always reload. In production, use cache
        if (!$this->devMode && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $path = $this->registryPath . '/' . $filename;
        $data = $this->loadConfig($path);
        
        // Cache the loaded data
        $this->cache[$cacheKey] = $data;
        
        return $data;
    }
    
    /**
     * Load config from YAML or JSON
     * MVP Step 3: Prefer YAML as source of truth (JSON only as fallback)
     */
    private function loadConfig(string $path): array {
        // MVP Step 3: Try YAML first (source of truth)
        if (file_exists($path)) {
            if (function_exists('yaml_parse_file')) {
                $data = yaml_parse_file($path);
                if (is_array($data)) {
                    return $data;
                }
            }
        }
        
        // Fallback to JSON only if YAML doesn't exist or can't be parsed
        $jsonPath = str_replace('.yaml', '.json', $path);
        if (file_exists($jsonPath)) {
            $content = file_get_contents($jsonPath);
            $data = json_decode($content, true);
            if (is_array($data)) {
                return $data;
            }
        }
        
        throw new \Exception("Registry file not found or invalid: $path");
    }
    
    /**
     * Reload all cached registry files (hot reload)
     */
    public function reload(): void {
        $this->cache = [];
    }
    
    /**
     * Get adapters configuration
     */
    public function getAdapters(): array {
        return $this->load('adapters.yaml');
    }
    
    /**
     * Get capabilities configuration
     */
    public function getCapabilities(): array {
        return $this->load('capabilities.yaml');
    }
    
    /**
     * Get UI configuration
     */
    public function getUI(): array {
        return $this->load('ui.yaml');
    }
    
    /**
     * Get policy configuration
     */
    public function getPolicy(): array {
        return $this->load('policy.yaml');
    }
}
