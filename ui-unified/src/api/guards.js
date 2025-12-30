// Capability Guards
// Provides capability checking and safe invocation

import { caps } from '../state/caps.js';
import { invoke } from './client.js';

/**
 * Check if user has a capability
 */
export function can(capability) {
    return caps.has(capability);
}

/**
 * Check if user has any of the capabilities
 */
export function canAny(...capabilities) {
    return caps.hasAny(...capabilities);
}

/**
 * Check if user has all of the capabilities
 */
export function canAll(...capabilities) {
    return caps.hasAll(...capabilities);
}

/**
 * Safe invoke - only invoke if capability is allowed
 * Throws FORBIDDEN_UI error if capability is not allowed
 */
export async function invokeSafe(capability, payload) {
    if (!can(capability)) {
        throw new Error('FORBIDDEN_UI: Capability not allowed');
    }
    
    return await invoke(capability, payload);
}

/**
 * Guard a function - only execute if capability is allowed
 */
export function guard(capability, fn) {
    return async (...args) => {
        if (!can(capability)) {
            throw new Error('FORBIDDEN_UI: Capability not allowed');
        }
        return await fn(...args);
    };
}

/**
 * Show element only if capability is allowed
 */
export function showIfCan(element, capability) {
    if (can(capability)) {
        element.classList.remove('hidden');
    } else {
        element.classList.add('hidden');
    }
}

/**
 * Enable/disable element based on capability
 */
export function enableIfCan(element, capability) {
    if (can(capability)) {
        element.disabled = false;
    } else {
        element.disabled = true;
    }
}
