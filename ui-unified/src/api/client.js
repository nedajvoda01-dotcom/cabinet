// API Client
// Handles communication with the platform

import { session } from '../state/session.js';
import { caps } from '../state/caps.js';

const PLATFORM_URL = window.location.origin + '/api';
const UI_ID = 'cabinet';

/**
 * Fetch capabilities from platform
 * This is called on app startup
 * NOTE: Role is determined by the server based on authentication,
 * NOT sent by the client (untrusted UI principle)
 */
export async function fetchCapabilities() {
    try {
        const params = new URLSearchParams({
            ui: UI_ID
            // Role is determined server-side from auth context, NOT sent by client
        });
        
        const headers = {
            'Content-Type': 'application/json'
        };
        
        // Add X-API-Key if we have a token (simulates authentication)
        if (session.token) {
            headers['X-API-Key'] = session.token;
        }
        
        const response = await fetch(`${PLATFORM_URL}/capabilities?${params}`, {
            method: 'GET',
            headers: headers
        });
        
        if (!response.ok) {
            throw new Error(`Failed to fetch capabilities: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Update session with server-determined role (if different from what we thought)
        if (data.role && data.role !== session.role) {
            session.role = data.role;
            session.save();
        }
        
        // Store capabilities
        const capsList = data.capabilities || [];
        const profile = data.ui_profile || session.uiProfile;
        
        caps.set(capsList, profile);
        
        return {
            capabilities: capsList,
            profile: profile,
            ui: data.ui,
            role: data.role
        };
    } catch (error) {
        console.error('Failed to fetch capabilities:', error);
        // Set empty capabilities on error
        caps.set([], 'public');
        throw error;
    }
}

/**
 * Invoke a capability on the platform
 * NOTE: Role and UI are determined server-side from authentication,
 * NOT sent by client (follows untrusted UI principle)
 */
export async function invoke(capability, payload) {
    try {
        const requestData = {
            capability: capability,
            payload: payload
            // role, ui, user_id are determined server-side from auth context
        };
        
        const headers = {
            'Content-Type': 'application/json'
        };
        
        // Add X-API-Key if we have a token (simulates authentication)
        if (session.token) {
            headers['X-API-Key'] = session.token;
        }
        
        const response = await fetch(`${PLATFORM_URL}/invoke`, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(requestData)
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || data.error || 'Request failed');
        }
        
        return data;
    } catch (error) {
        console.error('Invoke failed:', error);
        throw error;
    }
}

/**
 * Get platform version
 */
export async function getVersion() {
    try {
        const response = await fetch(`${PLATFORM_URL}/version`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch version');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Failed to fetch version:', error);
        throw error;
    }
}
