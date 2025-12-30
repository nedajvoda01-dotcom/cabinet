// Design Tokens
// Based on existing UI standards (black, gray, red palette)

export const tokens = {
    colors: {
        // Primary - Dark theme
        primary: '#1a1a1a',        // Black
        primaryLight: '#2c2c2c',
        primaryDark: '#000000',
        
        // Secondary - Gray
        secondary: '#666666',
        secondaryLight: '#999999',
        secondaryDark: '#333333',
        
        // Accent - Red (for admin/important actions)
        accent: '#e74c3c',
        accentLight: '#ec7063',
        accentDark: '#c0392b',
        
        // Success
        success: '#27ae60',
        successLight: '#2ecc71',
        successDark: '#229954',
        
        // Info - Blue
        info: '#3498db',
        infoLight: '#5dade2',
        infoDark: '#2980b9',
        
        // Warning
        warning: '#f39c12',
        warningLight: '#f1c40f',
        warningDark: '#e67e22',
        
        // Error
        error: '#e74c3c',
        errorLight: '#ec7063',
        errorDark: '#c0392b',
        
        // Background
        bg: '#f5f5f5',
        bgDark: '#ecf0f1',
        bgLight: '#ffffff',
        
        // Text
        text: '#2c3e50',
        textLight: '#7f8c8d',
        textDark: '#1a1a1a',
        
        // Border
        border: '#ddd',
        borderLight: '#e8e8e8',
        borderDark: '#bdc3c7',
    },
    
    spacing: {
        xs: '4px',
        sm: '8px',
        md: '16px',
        lg: '24px',
        xl: '32px',
        xxl: '48px',
    },
    
    fontSize: {
        xs: '12px',
        sm: '14px',
        md: '16px',
        lg: '18px',
        xl: '24px',
        xxl: '32px',
    },
    
    fontWeight: {
        normal: '400',
        medium: '500',
        bold: '700',
    },
    
    borderRadius: {
        sm: '4px',
        md: '8px',
        lg: '12px',
        round: '50%',
    },
    
    shadow: {
        sm: '0 1px 2px rgba(0,0,0,0.1)',
        md: '0 2px 4px rgba(0,0,0,0.1)',
        lg: '0 4px 8px rgba(0,0,0,0.15)',
        xl: '0 8px 16px rgba(0,0,0,0.2)',
    },
    
    transition: {
        fast: '150ms ease',
        normal: '250ms ease',
        slow: '350ms ease',
    },
};
