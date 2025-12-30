// Login Page

import { createLayout } from '../components/Layout.js';
import { session } from '../state/session.js';
import { fetchCapabilities } from '../api/client.js';
import { router } from '../routes/router.js';

export function render() {
    const content = `
        <div class="section">
            <h2>Login</h2>
            <p class="info">Note: This is a demo. Any credentials will work.</p>
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" id="username" placeholder="Enter username" value="demo">
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" id="password" placeholder="Enter password" value="password">
            </div>
            
            <div class="form-group">
                <label>Role:</label>
                <select id="role">
                    <option value="guest">Guest (Public Access)</option>
                    <option value="admin">Admin (Full Access)</option>
                </select>
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
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const role = document.getElementById('role').value;
        
        if (!username || !password) {
            resultDiv.innerHTML = '<div class="error">Please enter username and password</div>';
            return;
        }
        
        try {
            loginBtn.disabled = true;
            loginBtn.textContent = 'Logging in...';
            
            // Simulate login (in real app, this would call auth API)
            session.set({
                token: 'demo_token_' + Date.now(),
                userId: username,
                role: role,
                uiProfile: role === 'admin' ? 'admin' : 'public'
            });
            
            // Fetch capabilities for the new role
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
