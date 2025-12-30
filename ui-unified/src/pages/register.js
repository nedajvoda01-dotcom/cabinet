// Register Page

import { createLayout } from '../components/Layout.js';
import { router } from '../routes/router.js';

export function render() {
    const content = `
        <div class="section">
            <h2>Register</h2>
            <p class="info">Create a new account</p>
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" id="username" placeholder="Choose a username">
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" id="email" placeholder="your@email.com">
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" id="password" placeholder="Choose a password">
            </div>
            
            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" id="confirmPassword" placeholder="Confirm password">
            </div>
            
            <button class="btn btn-success" id="registerBtn">Register</button>
            <a href="#/login" class="btn btn-secondary ml-md">Back to Login</a>
            
            <div id="registerResult" class="mt-md"></div>
        </div>
    `;
    
    createLayout(content);
    attachEvents();
}

function attachEvents() {
    const registerBtn = document.getElementById('registerBtn');
    const resultDiv = document.getElementById('registerResult');
    
    registerBtn.addEventListener('click', async () => {
        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (!username || !email || !password) {
            resultDiv.innerHTML = '<div class="error">Please fill in all fields</div>';
            return;
        }
        
        if (password !== confirmPassword) {
            resultDiv.innerHTML = '<div class="error">Passwords do not match</div>';
            return;
        }
        
        try {
            registerBtn.disabled = true;
            registerBtn.textContent = 'Registering...';
            
            // Simulate registration (in real app, this would call auth API)
            resultDiv.innerHTML = '<div class="success">Registration successful! Redirecting to login...</div>';
            
            // Redirect to login
            setTimeout(() => {
                router.navigate('/login');
            }, 1500);
            
        } catch (error) {
            resultDiv.innerHTML = `<div class="error">Registration failed: ${error.message}</div>`;
            registerBtn.disabled = false;
            registerBtn.textContent = 'Register';
        }
    });
}
