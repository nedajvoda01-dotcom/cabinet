// Forbidden Page (403)

import { createLayout } from '../components/Layout.js';

export function render() {
    const content = `
        <div class="section text-center">
            <h2>Access Denied</h2>
            <p class="error">You don't have permission to access this page.</p>
            <p>This page requires specific capabilities that your account doesn't have.</p>
            <a href="#/" class="btn btn-primary">Go Home</a>
        </div>
    `;
    
    createLayout(content);
}
