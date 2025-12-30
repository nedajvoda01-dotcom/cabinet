// Cabinet Application
// Main entry point - bootstraps the unified UI with capability-based access control

import { session } from './state/session.js';
import { caps } from './state/caps.js';
import { fetchCapabilities } from './api/client.js';
import { router } from './routes/router.js';
import { updateActiveLink } from './components/Layout.js';

// Import pages
import * as homePage from './pages/home.js';
import * as catalogPage from './pages/catalog.js';
import * as carPage from './pages/car.js';
import * as loginPage from './pages/login.js';
import * as registerPage from './pages/register.js';
import * as adminContentPage from './pages/admin-content.js';
import * as adminExportPage from './pages/admin-export.js';
import * as forbiddenPage from './pages/forbidden.js';
import * as notFoundPage from './pages/notfound.js';

/**
 * Bootstrap Application
 * This is called on page load
 */
async function bootstrap() {
    console.log('🚀 Cabinet UI Starting...');
    
    try {
        // Step 1: Load capabilities from platform
        console.log('📡 Fetching capabilities...');
        await fetchCapabilities();
        
        console.log('✅ Capabilities loaded:', caps.getAll());
        console.log('👤 Profile:', caps.getProfile());
        
        // Step 2: Setup router
        setupRouter();
        
        // Step 3: Start router
        router.start();
        
        console.log('✅ Cabinet UI Ready');
        
    } catch (error) {
        console.error('❌ Bootstrap failed:', error);
        
        // Show error page
        document.getElementById('app').innerHTML = `
            <div class="container">
                <div class="section">
                    <h2>Application Error</h2>
                    <div class="error">
                        <p>Failed to initialize application: ${error.message}</p>
                        <p>Please check that the platform is running and try again.</p>
                    </div>
                    <button class="btn btn-primary mt-md" onclick="location.reload()">Retry</button>
                </div>
            </div>
        `;
    }
}

/**
 * Setup Router with all routes
 */
function setupRouter() {
    // Register route handlers
    router.on('/', homePage.render);
    router.on('/catalog', catalogPage.render);
    router.on('/car/:id', carPage.render);
    router.on('/login', loginPage.render);
    router.on('/register', registerPage.render);
    router.on('/admin', adminContentPage.render);
    router.on('/admin/content', adminContentPage.render);
    router.on('/admin/export', adminExportPage.render);
    router.on('/403', forbiddenPage.render);
    router.on('/404', notFoundPage.render);
    
    // Add after navigation hook to update active links
    router.after(updateActiveLink);
    
    // Add before navigation hook for debugging
    router.before((to, from) => {
        console.log(`🔀 Navigating from ${from} to ${to}`);
        return true; // Continue navigation
    });
}

/**
 * Initialize on DOM ready
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap);
} else {
    bootstrap();
}

// Export for debugging
window.cabinet = {
    session,
    caps,
    router,
    fetchCapabilities,
    version: '1.0.0'
};

console.log('📦 Cabinet UI Loaded');
