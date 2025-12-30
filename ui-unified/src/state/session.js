// Session State Management
// Handles user session, token, and authentication state

class SessionState {
    constructor() {
        this.token = null;
        this.userId = null;
        this.role = 'guest';  // default to guest
        this.uiProfile = 'public';  // default to public
        this.isAuthenticated = false;
        
        // Load from localStorage if exists
        this.load();
    }
    
    /**
     * Load session from localStorage
     */
    load() {
        try {
            const stored = localStorage.getItem('cabinet_session');
            if (stored) {
                const session = JSON.parse(stored);
                this.token = session.token;
                this.userId = session.userId;
                this.role = session.role || 'guest';
                this.uiProfile = session.uiProfile || 'public';
                this.isAuthenticated = !!this.token;
            }
        } catch (e) {
            console.error('Failed to load session:', e);
            this.clear();
        }
    }
    
    /**
     * Save session to localStorage
     */
    save() {
        try {
            const session = {
                token: this.token,
                userId: this.userId,
                role: this.role,
                uiProfile: this.uiProfile
            };
            localStorage.setItem('cabinet_session', JSON.stringify(session));
        } catch (e) {
            console.error('Failed to save session:', e);
        }
    }
    
    /**
     * Set session data (after login)
     */
    set(data) {
        this.token = data.token;
        this.userId = data.userId;
        this.role = data.role || 'guest';
        this.uiProfile = data.uiProfile || 'public';
        this.isAuthenticated = true;
        this.save();
    }
    
    /**
     * Clear session (logout)
     */
    clear() {
        this.token = null;
        this.userId = null;
        this.role = 'guest';
        this.uiProfile = 'public';
        this.isAuthenticated = false;
        localStorage.removeItem('cabinet_session');
    }
    
    /**
     * Get current session data
     */
    get() {
        return {
            token: this.token,
            userId: this.userId,
            role: this.role,
            uiProfile: this.uiProfile,
            isAuthenticated: this.isAuthenticated
        };
    }
    
    /**
     * Check if user is admin
     */
    isAdmin() {
        return this.role === 'admin';
    }
}

// Export singleton instance
export const session = new SessionState();
