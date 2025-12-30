// Home Page

import { createLayout } from '../components/Layout.js';
import { caps } from '../state/caps.js';
import { session } from '../state/session.js';

export function render() {
    const sessionData = session.get();
    const profile = caps.getProfile();
    const capsList = caps.getAll();
    
    const content = `
        <div class="section">
            <h2>Welcome to Cabinet</h2>
            <p>Unified car catalog management platform</p>
            
            <div class="grid grid-2 mt-lg">
                <div class="card">
                    <div class="card-title">Session Info</div>
                    <div class="card-body">
                        <p><strong>User:</strong> ${sessionData.userId || 'Guest'}</p>
                        <p><strong>Role:</strong> ${sessionData.role}</p>
                        <p><strong>Profile:</strong> ${profile}</p>
                        <p><strong>Authenticated:</strong> ${sessionData.isAuthenticated ? 'Yes' : 'No'}</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-title">Capabilities (${capsList.length})</div>
                    <div class="card-body">
                        ${capsList.length > 0 ? `
                            <ul style="list-style: none; padding: 0;">
                                ${capsList.slice(0, 10).map(cap => `<li>✓ ${cap}</li>`).join('')}
                                ${capsList.length > 10 ? `<li>... and ${capsList.length - 10} more</li>` : ''}
                            </ul>
                        ` : `
                            <p class="info">No capabilities loaded</p>
                        `}
                    </div>
                </div>
            </div>
            
            <div class="mt-lg">
                <h3>Quick Actions</h3>
                <div class="grid grid-3 mt-md">
                    <a href="#/catalog" class="card" style="text-decoration: none; color: inherit;">
                        <div class="card-title">Browse Catalog</div>
                        <div class="card-body">
                            <p>Search and view car listings</p>
                        </div>
                    </a>
                    
                    ${caps.has('catalog.listing.use') ? `
                        <a href="#/admin/content" class="card" style="text-decoration: none; color: inherit;">
                            <div class="card-title">Manage Content</div>
                            <div class="card-body">
                                <p>Manage catalog listings</p>
                            </div>
                        </a>
                    ` : ''}
                    
                    ${caps.has('import.run') ? `
                        <a href="#/admin/export" class="card" style="text-decoration: none; color: inherit;">
                            <div class="card-title">Import Data</div>
                            <div class="card-body">
                                <p>Import CSV data</p>
                            </div>
                        </a>
                    ` : ''}
                </div>
            </div>
            
            ${!sessionData.isAuthenticated ? `
                <div class="mt-lg info">
                    <p><strong>Note:</strong> You are browsing as a guest. <a href="#/login">Login</a> to access more features.</p>
                </div>
            ` : ''}
        </div>
    `;
    
    createLayout(content);
}
