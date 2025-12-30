<?php
/**
 * Route Requirements Map
 * Defines routes and their security/access requirements
 */

namespace Platform\Http\Security\Requirements;

class RouteRequirementsMap {
    private array $routes = [];
    
    public function __construct() {
        $this->defineRoutes();
    }
    
    /**
     * Define all routes and their requirements
     */
    private function defineRoutes(): void {
        // Public endpoints (no auth required)
        $this->routes['GET:/api/version'] = [
            'auth_required' => false,
            'description' => 'Get platform version and health status'
        ];
        
        // API endpoints (auth required)
        $this->routes['GET:/api/capabilities'] = [
            'auth_required' => true,
            'description' => 'Get capabilities filtered by UI and policy',
            'params' => ['ui']
        ];
        
        $this->routes['POST:/api/invoke'] = [
            'auth_required' => true,
            'description' => 'Invoke a capability through an adapter',
            'params' => ['capability', 'payload']
        ];
        
        // Control plane endpoints (admin only)
        $this->routes['POST:/control/reload-registry'] = [
            'auth_required' => true,
            'admin_only' => true,
            'description' => 'Reload registry configuration (dev mode)'
        ];
    }
    
    /**
     * Get requirements for a route
     */
    public function getRequirements(string $method, string $path): ?array {
        $key = strtoupper($method) . ':' . $path;
        return $this->routes[$key] ?? null;
    }
    
    /**
     * Check if route exists
     */
    public function hasRoute(string $method, string $path): bool {
        $key = strtoupper($method) . ':' . $path;
        return isset($this->routes[$key]);
    }
    
    /**
     * Get all routes
     */
    public function getAllRoutes(): array {
        return $this->routes;
    }
    
    /**
     * Check if route requires authentication
     */
    public function requiresAuth(string $method, string $path): bool {
        $requirements = $this->getRequirements($method, $path);
        return $requirements['auth_required'] ?? false;
    }
    
    /**
     * Check if route is admin-only
     */
    public function isAdminOnly(string $method, string $path): bool {
        $requirements = $this->getRequirements($method, $path);
        return $requirements['admin_only'] ?? false;
    }
}
