// API Client
// Handles communication with the platform

import { session } from '../state/session.js';
import { caps } from '../state/caps.js';

const PLATFORM_URL = window.location.origin + '/api';
const UI_ID = 'cabinet';

/**
 * Fetch capabilities from platform
 * This is called on app startup
 */
export async function fetchCapabilities() {
    try {
        const params = new URLSearchParams({
            ui: UI_ID,
            role: session.role
        });
        
        const response = await fetch(`${PLATFORM_URL}/capabilities?${params}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`Failed to fetch capabilities: ${response.status}`);
        }
        
        const data = await response.json();
        
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
 */
export async function invoke(capability, payload) {
    try {
        const requestData = {
            capability: capability,
            payload: payload,
            ui: UI_ID,
            role: session.role,
            user_id: session.userId || 'anonymous'
        };
        
        const response = await fetch(`${PLATFORM_URL}/invoke`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
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
