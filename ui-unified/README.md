# Cabinet Unified UI

**Capability-based single-page application that adapts to user permissions.**

## Overview

This is a unified UI that combines both public and admin interfaces into a single application. The UI dynamically shows/hides features based on user capabilities fetched from the platform at startup.

## Key Features

### 🔐 Capability-Based Access Control
- Fetches allowed capabilities from `/api/capabilities` on startup
- Hides pages/routes that user doesn't have access to
- Disables buttons/actions if capability is not allowed
- Guards all API calls with capability checks

### 🎭 Dynamic UI Profiles
- **Public Profile**: Read-only catalog access (4 capabilities)
  - Browse catalog
  - View car details
  - View photos
  
- **Admin Profile**: Full platform access (17+ capabilities)
  - All public features
  - Content management
  - CSV import/export
  - Car CRUD operations
  - Pricing rules
  - Workflow execution

### 🛡️ Multi-Layer Security
1. **UI Layer**: Prevents unauthorized actions at the UI level
2. **Platform Layer**: Policy + ResultGate enforces permissions server-side
3. **Adapter Layer**: Business logic validates requests

## Architecture

```
ui-unified/
├── index.html              # Entry point
└── src/
    ├── app.js              # Bootstrap & router setup
    ├── api/
    │   ├── client.js       # Platform API communication
    │   └── guards.js       # Capability checking & safe invoke
    ├── state/
    │   ├── session.js      # User session management
    │   └── caps.js         # Capabilities state
    ├── routes/
    │   ├── router.js       # Hash-based router with guards
    │   └── pages.js        # Route → capability mapping
    ├── pages/
    │   ├── home.js         # Home page
    │   ├── catalog.js      # Public catalog search
    │   ├── car.js          # Car detail page
    │   ├── login.js        # Login page
    │   ├── register.js     # Registration page
    │   ├── admin-content.js    # Admin content management
    │   ├── admin-export.js     # Admin CSV import/export
    │   ├── forbidden.js    # 403 error page
    │   └── notfound.js     # 404 error page
    ├── components/
    │   └── Layout.js       # Main layout with sidebar
    └── design/
        ├── tokens.js       # Design system tokens
        └── styles.css      # Global styles
```

## How It Works

### 1. Startup Flow (Bootstrap)

```javascript
// On page load
GET /api/capabilities?ui=cabinet&role=guest

// Response
{
  "ui": "cabinet",
  "role": "guest", 
  "ui_profile": "public",
  "capabilities": [
    "catalog.filters.get",
    "catalog.listings.search",
    "catalog.listing.get",
    "catalog.photos.list"
  ]
}

// UI stores capabilities and builds navigation
```

### 2. Route Guards

```javascript
// User navigates to /admin/export
router.navigate('/admin/export')

// Router checks if user has required capabilities
if (!caps.has('import.run')) {
  redirect to /403  // Forbidden
}
```

### 3. Action Guards

```javascript
// User clicks "Import CSV" button
// Button only visible if user has capability

async function runImport() {
  // Safe invoke checks capability before calling API
  await invokeSafe('import.run', { 
    filename: 'cars.csv',
    csv_data: csvData 
  });
}

// If user somehow bypasses UI, platform will reject
```

## Capability → Page Mapping

| Page | Required Capabilities | Visible To |
|------|----------------------|------------|
| `/` (Home) | None | Everyone |
| `/catalog` | `catalog.filters.get`, `catalog.listings.search` | Everyone |
| `/car/:id` | `catalog.listing.get` | Everyone |
| `/login` | None | Everyone |
| `/register` | None | Everyone |
| `/admin/content` | `catalog.listing.use`, `catalog.listings.search` | Admin only |
| `/admin/export` | `import.run` | Admin only |

## API Endpoints Used

- `GET /api/capabilities` - Fetch allowed capabilities
- `POST /api/invoke` - Execute capability with payload
- `GET /api/version` - Platform version info

## Usage

### Development
The UI is served directly by the platform at `http://localhost:8080/ui/`

### Login Flow (Demo)
1. Click "Login" button
2. Enter any username/password
3. Select role: "Guest" or "Admin"
4. UI will fetch capabilities for selected role
5. Navigation and features adapt automatically

### Adding New Features

1. **Add capability to registry**
   ```yaml
   # registry/ui.yaml
   profiles:
     admin:
       allowed_capabilities:
         - your.new.capability
   ```

2. **Create page component**
   ```javascript
   // src/pages/your-page.js
   import { invokeSafe } from '../api/guards.js';
   
   export async function render() {
     // Your page logic
   }
   ```

3. **Register route**
   ```javascript
   // src/routes/pages.js
   '/your-page': {
     name: 'Your Page',
     requiresAuth: true,
     capabilities: ['your.new.capability']
   }
   ```

4. **Add to router**
   ```javascript
   // src/app.js
   router.on('/your-page', yourPage.render);
   ```

## Design System

The UI uses a consistent design system based on tokens:

- **Colors**: Dark theme (black/gray) with red accents
- **Spacing**: Consistent 4px/8px/16px/24px/32px scale
- **Typography**: System fonts with defined size scale
- **Components**: Cards, buttons, forms with unified styling

See `src/design/tokens.js` and `src/design/styles.css` for details.

## Security Considerations

✅ **What this provides:**
- UI-level capability enforcement
- Prevents confused deputy attacks at UI level
- Clear separation between public and admin features
- No "hidden" admin pages (they don't render without capabilities)

⚠️ **What this doesn't replace:**
- Server-side permission checks (Policy layer)
- Result filtering (ResultGate)
- Input validation
- Rate limiting

**Always remember**: UI checks are convenience + UX. Platform must enforce all security.

## Browser Support

- Modern browsers with ES6+ module support
- No build step required
- Native JavaScript (no frameworks)

## License

MIT
