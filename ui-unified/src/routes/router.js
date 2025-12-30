// Router
// Simple hash-based router with capability guards

import { session } from '../state/session.js';
import { caps } from '../state/caps.js';
import { pages, getPageConfig } from './pages.js';

class Router {
    constructor() {
        this.currentPath = '/';
        this.routes = new Map();
        this.beforeHooks = [];
        this.afterHooks = [];
    }
    
    /**
     * Register a route handler
     */
    on(path, handler) {
        this.routes.set(path, handler);
    }
    
    /**
     * Add before navigation hook
     */
    before(hook) {
        this.beforeHooks.push(hook);
    }
    
    /**
     * Add after navigation hook
     */
    after(hook) {
        this.afterHooks.push(hook);
    }
    
    /**
     * Navigate to a path
     */
    async navigate(path, replace = false) {
        // Run before hooks
        for (const hook of this.beforeHooks) {
            const result = await hook(path, this.currentPath);
            if (result === false) {
                return; // Navigation cancelled
            }
            if (typeof result === 'string') {
                path = result; // Redirect
            }
        }
        
        // Apply guards
        const guardResult = this.guard(path);
        if (guardResult !== true) {
            path = guardResult; // Redirect to error page
        }
        
        // Update history
        if (replace) {
            window.history.replaceState({}, '', '#' + path);
        } else {
            window.history.pushState({}, '', '#' + path);
        }
        
        this.currentPath = path;
        
        // Find and execute handler
        await this.executeRoute(path);
        
        // Run after hooks
        for (const hook of this.afterHooks) {
            await hook(path);
        }
    }
    
    /**
     * Guard route based on capabilities
     */
    guard(path) {
        const config = getPageConfig(path);
        
        if (!config) {
            return '/404'; // Page not found
        }
        
        // Check authentication
        if (config.requiresAuth && !session.isAuthenticated) {
            return '/login';
        }
        
        // Check capabilities
        if (config.capabilities && config.capabilities.length > 0) {
            // User must have at least one of the required capabilities
            const hasCapability = config.capabilities.some(cap => caps.has(cap));
            if (!hasCapability) {
                return '/403'; // Forbidden
            }
        }
        
        return true;
    }
    
    /**
     * Execute route handler
     */
    async executeRoute(path) {
        // Try exact match
        if (this.routes.has(path)) {
            await this.routes.get(path)(path, {});
            return;
        }
        
        // Try pattern matching
        for (const [pattern, handler] of this.routes.entries()) {
            if (pattern.includes(':')) {
                const params = this.matchPattern(pattern, path);
                if (params) {
                    await handler(path, params);
                    return;
                }
            }
        }
        
        // No handler found, try 404
        if (this.routes.has('/404')) {
            await this.routes.get('/404')(path, {});
        }
    }
    
    /**
     * Match path pattern and extract params
     */
    matchPattern(pattern, path) {
        const patternParts = pattern.split('/');
        const pathParts = path.split('/');
        
        if (patternParts.length !== pathParts.length) {
            return null;
        }
        
        const params = {};
        for (let i = 0; i < patternParts.length; i++) {
            if (patternParts[i].startsWith(':')) {
                const paramName = patternParts[i].substring(1);
                params[paramName] = pathParts[i];
            } else if (patternParts[i] !== pathParts[i]) {
                return null;
            }
        }
        
        return params;
    }
    
    /**
     * Get current path
     */
    getCurrentPath() {
        return this.currentPath;
    }
    
    /**
     * Start router (listen to hash changes)
     */
    start() {
        window.addEventListener('hashchange', () => {
            const path = window.location.hash.substring(1) || '/';
            this.navigate(path, true);
        });
        
        // Handle initial load
        const path = window.location.hash.substring(1) || '/';
        this.navigate(path, true);
    }
}

// Export singleton instance
export const router = new Router();
