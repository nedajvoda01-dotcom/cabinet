// Pages Configuration
// Maps routes to pages and their required capabilities

export const pages = {
    // Public pages (no specific capabilities required)
    '/': {
        name: 'Home',
        title: 'Cabinet',
        requiresAuth: false,
        capabilities: []
    },
    '/catalog': {
        name: 'Catalog',
        title: 'Car Catalog',
        requiresAuth: false,
        capabilities: ['catalog.filters.get', 'catalog.listings.search']
    },
    '/car/:id': {
        name: 'Car Details',
        title: 'Car Details',
        requiresAuth: false,
        capabilities: ['catalog.listing.get']
    },
    
    // Auth pages
    '/login': {
        name: 'Login',
        title: 'Login',
        requiresAuth: false,
        capabilities: []
    },
    '/register': {
        name: 'Register',
        title: 'Register',
        requiresAuth: false,
        capabilities: []
    },
    
    // Admin pages (requires specific capabilities)
    '/admin': {
        name: 'Admin',
        title: 'Admin Dashboard',
        requiresAuth: true,
        capabilities: ['catalog.listing.use']
    },
    '/admin/content': {
        name: 'Content Management',
        title: 'Content Management',
        requiresAuth: true,
        capabilities: ['catalog.listing.use', 'catalog.listings.search']
    },
    '/admin/export': {
        name: 'Export',
        title: 'Export Data',
        requiresAuth: true,
        capabilities: ['import.run']
    },
    
    // Utility pages
    '/403': {
        name: 'Forbidden',
        title: 'Access Denied',
        requiresAuth: false,
        capabilities: []
    },
    '/404': {
        name: 'Not Found',
        title: 'Page Not Found',
        requiresAuth: false,
        capabilities: []
    }
};

/**
 * Get page config by path
 */
export function getPageConfig(path) {
    // Try exact match first
    if (pages[path]) {
        return pages[path];
    }
    
    // Try pattern matching for routes with params (e.g., /car/:id)
    for (const [pattern, config] of Object.entries(pages)) {
        if (pattern.includes(':')) {
            const regex = new RegExp('^' + pattern.replace(/:[^/]+/g, '[^/]+') + '$');
            if (regex.test(path)) {
                return config;
            }
        }
    }
    
    return null;
}

/**
 * Check if path requires any capabilities
 */
export function requiresCapabilities(path) {
    const config = getPageConfig(path);
    return config ? config.capabilities : [];
}
