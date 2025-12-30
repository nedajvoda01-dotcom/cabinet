// Login Page
// NOTE: In production, role is determined server-side based on authentication
// This demo simulates different users with different API keys

import { createLayout } from '../components/Layout.js';
import { session } from '../state/session.js';
import { fetchCapabilities } from '../api/client.js';
import { router } from '../routes/router.js';

export function render() {
    const content = `
        <div class="section">
            <h2>Login</h2>
            <p class="info">Note: This is a demo authentication system.</p>
            <p class="info">In production, your role is determined server-side based on your credentials.</p>
            
            <div class="form-group">
                <label>Select User Account:</label>
                <select id="userAccount" class="form-control">
                    <option value="guest">Guest User (Public Access - API Key: public_secret_key_67890)</option>
                    <option value="admin">Admin User (Full Access - API Key: admin_secret_key_12345)</option>
                </select>
                <small class="text-muted">
                    This simulates different users with different API keys.
                    The server determines your role based on the API key.
                </small>
            </div>
            
            <button class="btn btn-primary" id="loginBtn">Login</button>
            <a href="#/register" class="btn btn-secondary ml-md">Register</a>
            
            <div id="loginResult" class="mt-md"></div>
        </div>
    `;
    
    createLayout(content);
    attachEvents();
}

function attachEvents() {
    const loginBtn = document.getElementById('loginBtn');
    const resultDiv = document.getElementById('loginResult');
    
    loginBtn.addEventListener('click', async () => {
        const userAccount = document.getElementById('userAccount').value;
        
        try {
            loginBtn.disabled = true;
            loginBtn.textContent = 'Logging in...';
            
            // Simulate login with different API keys
            // In production, this would call a real auth API that returns a session token
            const apiKeys = {
                'guest': 'public_secret_key_67890',
                'admin': 'admin_secret_key_12345'
            };
            
            const apiKey = apiKeys[userAccount];
            const userId = userAccount === 'admin' ? 'admin_user' : 'public_user';
            
            // Store API key as token (this simulates session management)
            // NOTE: displayRole is only for UI display purposes
            // The actual role is determined server-side from the API key
            session.set({
                token: apiKey,
                userId: userId,
                displayRole: userAccount, // Only for UI display, server ignores this
                uiProfile: userAccount === 'admin' ? 'admin' : 'public'
            });
            
            // Fetch capabilities - the server will determine role from the API key
            await fetchCapabilities();
            
            resultDiv.innerHTML = '<div class="success">Login successful! Redirecting...</div>';
            
            // Redirect to home
            setTimeout(() => {
                router.navigate('/');
            }, 500);
            
        } catch (error) {
            resultDiv.innerHTML = `<div class="error">Login failed: ${error.message}</div>`;
            loginBtn.disabled = false;
            loginBtn.textContent = 'Login';
        }
    });
}
