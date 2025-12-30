// Not Found Page (404)

import { createLayout } from '../components/Layout.js';

export function render() {
    const content = `
        <div class="section text-center">
            <h2>Page Not Found</h2>
            <p class="error">The page you're looking for doesn't exist.</p>
            <a href="#/" class="btn btn-primary">Go Home</a>
        </div>
    `;
    
    createLayout(content);
}
