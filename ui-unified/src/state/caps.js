// Capabilities State Management
// Stores and manages allowed capabilities for current user

class CapabilitiesState {
    constructor() {
        this.capabilities = new Set();
        this.uiProfile = 'public';
        this.loaded = false;
    }
    
    /**
     * Set capabilities from API response
     */
    set(capsList, profile = 'public') {
        this.capabilities.clear();
        if (Array.isArray(capsList)) {
            capsList.forEach(cap => {
                // Handle both string format and object format
                const capName = typeof cap === 'string' ? cap : cap.name;
                this.capabilities.add(capName);
            });
        }
        this.uiProfile = profile;
        this.loaded = true;
    }
    
    /**
     * Check if capability is allowed
     */
    has(capability) {
        return this.capabilities.has(capability);
    }
    
    /**
     * Check if any of the capabilities is allowed
     */
    hasAny(...capabilities) {
        return capabilities.some(cap => this.has(cap));
    }
    
    /**
     * Check if all capabilities are allowed
     */
    hasAll(...capabilities) {
        return capabilities.every(cap => this.has(cap));
    }
    
    /**
     * Get all capabilities as array
     */
    getAll() {
        return Array.from(this.capabilities);
    }
    
    /**
     * Clear capabilities
     */
    clear() {
        this.capabilities.clear();
        this.uiProfile = 'public';
        this.loaded = false;
    }
    
    /**
     * Get UI profile
     */
    getProfile() {
        return this.uiProfile;
    }
    
    /**
     * Check if capabilities are loaded
     */
    isLoaded() {
        return this.loaded;
    }
}

// Export singleton instance
export const caps = new CapabilitiesState();
