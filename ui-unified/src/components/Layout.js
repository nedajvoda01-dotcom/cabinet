// Layout Component
// Main application layout with header and sidebar

import { session } from '../state/session.js';
import { caps } from '../state/caps.js';
import { router } from '../routes/router.js';

export function createLayout(content) {
    const container = document.getElementById('app');
    if (!container) return;
    
    container.innerHTML = `
        <div class="layout">
            ${createHeader()}
            <div class="layout-body">
                ${createSidebar()}
                <main class="layout-main">
                    ${content}
                </main>
            </div>
        </div>
    `;
    
    attachLayoutEvents();
}

function createHeader() {
    const sessionData = session.get();
    const profile = caps.getProfile();
    
    return `
        <header class="header">
            <div class="header-content">
                <h1>Cabinet</h1>
                <div class="header-info">
                    <span class="profile-badge">${profile}</span>
                    ${sessionData.isAuthenticated ? `
                        <span class="user-info">${sessionData.userId}</span>
                        <button class="btn btn-secondary btn-sm" id="logoutBtn">Logout</button>
                    ` : `
                        <a href="#/login" class="btn btn-primary btn-sm">Login</a>
                    `}
                </div>
            </div>
        </header>
    `;
}

function createSidebar() {
    const hasAdminAccess = caps.hasAny('catalog.listing.use', 'import.run', 'car.create');
    
    return `
        <aside class="sidebar">
            <nav>
                <a href="#/" class="nav-link">Home</a>
                <a href="#/catalog" class="nav-link">Catalog</a>
                ${hasAdminAccess ? `
                    <hr>
                    <div class="nav-section-title">Admin</div>
                    ${caps.has('catalog.listing.use') ? `
                        <a href="#/admin/content" class="nav-link">Content</a>
                    ` : ''}
                    ${caps.has('import.run') ? `
                        <a href="#/admin/export" class="nav-link">Export/Import</a>
                    ` : ''}
                ` : ''}
            </nav>
        </aside>
    `;
}

function attachLayoutEvents() {
    // Logout button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            session.clear();
            caps.clear();
            router.navigate('/login');
        });
    }
    
    // Active link highlighting
    updateActiveLink();
}

function updateActiveLink() {
    const currentPath = router.getCurrentPath();
    document.querySelectorAll('.nav-link').forEach(link => {
        const href = link.getAttribute('href').substring(1); // Remove #
        if (href === currentPath || (href !== '/' && currentPath.startsWith(href))) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Export for use in router after hooks
export { updateActiveLink };
