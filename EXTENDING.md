# Extending Cabinet Platform

This guide demonstrates how to add new adapters and UIs to the Cabinet platform **without modifying platform code**.

## Quick Start: Developer Scripts

Cabinet provides convenient scripts to scaffold new components:

### Create a New Adapter

```bash
./scripts/new-adapter.sh my-service
```

This creates:
- `adapters/my-service/` directory
- `invoke.php` with capability routing template
- `README.md` with integration instructions

### Add a New Capability

```bash
./scripts/new-capability.sh storage.backup storage-adapter
```

This prompts for:
- Description
- Internal-only flag
- Allowed parent capabilities (if internal)
- Automatically adds to `registry/capabilities.yaml`

### Run Smoke Tests

```bash
./scripts/run-smoke.sh
```

Wrapper over tests/run-smoke-tests.sh with proper configuration.

### Validate Architecture

```bash
./scripts/check-architecture.sh
```

Checks for:
- Direct adapter URLs in UI code (blocked)
- JSON files in registry (warning)
- Legacy Router usage (blocked)
- Adapter-to-adapter HTTP calls (blocked)
- Hardcoded chain rules (blocked)

### Validate Registry

```bash
./scripts/validate-registry.sh
```

Validates:
- Required files exist
- YAML syntax
- Cross-references (adapter → capability → UI)
- Internal-only capabilities have allowed_parents

### Run All CI Checks

```bash
./scripts/ci-verify.sh
```

Runs all merge-blocker tests:
- Registry validation
- Architecture rules
- Security tests
- Integration tests
- Capability chain tests
- Result profile tests
- Import idempotency tests
- Network isolation tests
- MVP verification

---

## Manual Process: Adding a New Adapter

### Example: Adding a "Notification" Adapter

#### 1. Create Adapter Directory and Files

```bash
mkdir -p adapters/notification
```

#### 2. Create `adapters/notification/invoke.php`

```php
<?php
/**
 * Notification Adapter
 * Handles notification sending
 */

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Health check
if ($path === '/health') {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'adapter' => 'notification']);
    exit;
}

// Invoke endpoint
if ($path === '/invoke' && $method === 'POST') {
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);
    
    $capability = $request['capability'] ?? '';
    $payload = $request['payload'] ?? [];
    
    try {
        switch ($capability) {
            case 'notification.send':
                $to = $payload['to'] ?? '';
                $message = $payload['message'] ?? '';
                // Simulate sending notification
                $response = [
                    'notification_id' => uniqid('notif_'),
                    'to' => $to,
                    'status' => 'sent',
                    'sent_at' => time()
                ];
                break;
                
            default:
                throw new Exception("Unknown capability: $capability");
        }
        
        http_response_code(200);
        echo json_encode($response);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
```

#### 3. Create `adapters/notification/.htaccess`

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /invoke.php [L,QSA]
```

#### 4. Create `adapters/notification/capabilities.yaml`

```yaml
capabilities:
  - name: notification.send
    description: Send a notification
    input_schema:
      type: object
      properties:
        to:
          type: string
          required: true
        message:
          type: string
          required: true
```

#### 5. Register in `registry/adapters.yaml` (and `.json`)

```yaml
adapters:
  # ... existing adapters ...
  
  notification:
    url: "${ADAPTER_NOTIFICATION_URL}"
    name: "Notification Adapter"
    description: "Handles notification sending"
    health_check: "/health"
    timeout: 30
```

Also update `registry/adapters.json`:
```json
{
  "adapters": {
    "notification": {
      "url": "${ADAPTER_NOTIFICATION_URL}",
      "name": "Notification Adapter",
      "description": "Handles notification sending",
      "health_check": "/health",
      "timeout": 30
    }
  }
}
```

#### 6. Map capabilities in `registry/capabilities.yaml` (and `.json`)

```yaml
capabilities:
  # ... existing capabilities ...
  
  notification.send:
    adapter: notification
    description: "Send a notification"
```

Also update `registry/capabilities.json`.

#### 7. Add to `docker-compose.yml`

```yaml
services:
  # ... existing services ...
  
  adapter-notification:
    image: php:8.2-apache
    container_name: cabinet-adapter-notification
    ports:
      - "8084:80"
    volumes:
      - ./adapters/notification:/var/www/html
    command: >
      bash -c "
        a2enmod rewrite &&
        apache2-foreground
      "
```

#### 8. Add environment variable to `.env`

```bash
ADAPTER_NOTIFICATION_URL=http://adapter-notification
```

#### 9. Update platform dependencies in `docker-compose.yml`

```yaml
  platform:
    # ...
    depends_on:
      - adapter-car-storage
      - adapter-pricing
      - adapter-automation
      - adapter-notification  # Add this
```

#### 10. Add policy in `registry/policy.yaml` (and `.json`)

```yaml
capability_policies:
  # ... existing policies ...
  
  notification.send:
    required_scopes:
      - write
    rate_limit: 50
```

#### 11. Restart and Test

```bash
docker compose down
docker compose up -d

# Test the new adapter
curl -X POST http://localhost:8080/api/invoke \
  -H "Content-Type: application/json" \
  -d '{
    "capability": "notification.send",
    "payload": {
      "to": "user@example.com",
      "message": "Hello from Cabinet!"
    },
    "ui": "admin",
    "role": "admin",
    "user_id": "admin_user"
  }'
```

## Adding a New UI

### Example: Adding a "Mobile" UI

#### 1. Create UI Directory

```bash
mkdir -p ui/mobile/src
```

#### 2. Create `ui/mobile/index.html`

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabinet - Mobile UI</title>
    <style>
        /* Mobile-optimized styles */
    </style>
</head>
<body>
    <h1>Cabinet Mobile UI</h1>
    <div id="app"></div>
    <script src="src/app.js"></script>
</body>
</html>
```

#### 3. Create `ui/mobile/src/app.js`

```javascript
const PLATFORM_URL = 'http://localhost:8080/api/invoke';
const UI_ID = 'mobile';
const ROLE = 'user';

async function callPlatform(capability, payload) {
    const response = await fetch(PLATFORM_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            capability,
            payload,
            ui: UI_ID,
            role: ROLE,
            user_id: 'mobile_user'
        })
    });
    return await response.json();
}

// Your mobile app logic here
```

#### 4. Register in `registry/ui.yaml` (and `.json`)

```yaml
ui:
  # ... existing UIs ...
  
  mobile:
    name: "Mobile UI"
    description: "Mobile interface for users"
    allowed_capabilities:
      - car.list
      - car.read
      - price.calculate
      - notification.send
    scopes:
      - read
      - write
```

Also update `registry/ui.json`.

#### 5. Add to `docker-compose.yml`

```yaml
services:
  # ... existing services ...
  
  ui-mobile:
    image: nginx:alpine
    container_name: cabinet-ui-mobile
    ports:
      - "${UI_MOBILE_PORT:-3002}:80"
    volumes:
      - ./ui/mobile:/usr/share/nginx/html
    depends_on:
      - platform
```

#### 6. Add environment variable to `.env`

```bash
UI_MOBILE_PORT=3002
```

#### 7. Restart and Access

```bash
docker compose down
docker compose up -d

# Access the new UI
open http://localhost:3002
```

## Key Principles

1. **No Platform Code Changes**: All extensions are through configuration files and new services
2. **Registry-Driven**: Everything is defined in `registry/*.yaml` (and `.json`) files
3. **Declarative**: Capabilities, policies, and UI permissions are declared, not coded
4. **Isolated**: Each adapter and UI runs in its own container
5. **Testable**: Use smoke tests to validate new capabilities

## Validation Checklist

When adding a new adapter or UI:

- [ ] Health endpoint responds correctly
- [ ] Capabilities are properly registered
- [ ] Policies are defined with appropriate scopes
- [ ] UI permissions are correctly configured
- [ ] Docker Compose service is added
- [ ] Environment variables are set
- [ ] Smoke tests pass
- [ ] Documentation is updated

## Platform Never Changes

The beauty of this architecture is that `platform/` directory code never needs to change when:
- Adding new adapters
- Adding new UIs
- Adding new capabilities
- Modifying policies
- Adjusting rate limits

All of this is pure configuration and new service deployment!
