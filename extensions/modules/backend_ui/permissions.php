<?php
/**
 * Backend UI Permissions
 * 
 * Minimal stub for checking role-based permissions.
 * In production, this would query policy files and kernel authz.
 * 
 * For stub: deterministic allow/deny based on simple rules.
 */

declare(strict_types=1);

/**
 * Check if a role has permission to invoke a command on a module
 * 
 * @param string $role User role (admin, editor, viewer, etc.)
 * @param string $targetModule Target module ID
 * @param string $targetCommand Target command/capability
 * @return bool True if allowed, false if denied
 */
function checkPermission(string $role, string $targetModule, string $targetCommand): bool
{
    // Stub implementation - simple allow rules
    
    // Admin can do everything
    if ($role === 'admin') {
        return true;
    }
    
    // Editor can access ads_api_parser and storage
    if ($role === 'editor') {
        $allowedModules = ['ads-api-parser', 'storage-module'];
        return in_array($targetModule, $allowedModules, true);
    }
    
    // Viewer can only read from storage
    if ($role === 'viewer') {
        if ($targetModule === 'storage-module') {
            // Only allow read operations
            $readCommands = ['storage.listings.list', 'storage.listings.get'];
            return in_array($targetCommand, $readCommands, true);
        }
        return false;
    }
    
    // Default deny
    return false;
}

/**
 * Load role configuration (stub)
 * 
 * In production, would load from config/roles.yaml
 */
function loadRoleConfig(): array
{
    // Stub configuration
    return [
        'admin' => [
            'name' => 'Administrator',
            'capabilities' => ['*'],
        ],
        'editor' => [
            'name' => 'Editor',
            'capabilities' => [
                'storage.listings.create',
                'storage.listings.update',
                'ads.parse.listings',
            ],
        ],
        'viewer' => [
            'name' => 'Viewer',
            'capabilities' => [
                'storage.listings.list',
                'storage.listings.get',
            ],
        ],
    ];
}

/**
 * Check if role has specific capability
 */
function hasCapability(string $role, string $capability): bool
{
    $config = loadRoleConfig();
    
    if (!isset($config[$role])) {
        return false;
    }
    
    $roleCaps = $config[$role]['capabilities'];
    
    // Check for wildcard
    if (in_array('*', $roleCaps, true)) {
        return true;
    }
    
    // Check for exact match
    return in_array($capability, $roleCaps, true);
}
