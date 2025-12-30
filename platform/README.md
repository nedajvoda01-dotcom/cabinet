# Cabinet Platform - Security Model

**Phase 5 & 6: MVP Security with "fail-closed" approach**

## Core Principle

**Nothing happens unless authenticated/authorized/observable**

The Cabinet Platform implements a strict security model where:
1. All requests must be authenticated (fail-closed by default)
2. All actions must be explicitly authorized via policy
3. All operations are audited and observable
4. All responses are filtered and sanitized

## Phase 5: Security Minimum (Fail-Closed)

### 5.1 Authentication

#### X-API-Key Authentication
The platform uses X-API-Key header authentication for internal UI communication.

**Header Format:**
```
X-API-Key: <api_key>
```

**API Key Configuration:**
API keys are configured via environment variables in the format:
```
API_KEY_<NAME>=<key_value>|<ui>|<role>|<user_id>
```

**Example:**
```bash
API_KEY_ADMIN=admin_secret_key_12345|admin|admin|admin_user
API_KEY_PUBLIC=public_secret_key_67890|public|guest|public_user
```

**Behavior:**
- If `ENABLE_AUTH=true`: Authentication is required (fail-closed)
- If `ENABLE_AUTH=false`: Development mode, requests proceed with default actor
- Missing or invalid API key: Returns HTTP 401 Unauthorized

**Implementation:**
- Location: `platform/src/Http/Security/Authentication.php`
- Used by: `InvokeController` before any capability invocation

### 5.2 Authorization

#### Policy-Based Access Control
Authorization is managed through `registry/policy.yaml` with a **deny-by-default** approach.

**Policy Structure:**
```yaml
roles:
  admin:
    scopes: [admin, read, write, delete]
    rate_limit: 1000
    max_request_size: 10485760
  
capability_policies:
  car.create:
    required_scopes: [write]
    rate_limit: 10
```

**Authorization Flow:**
1. Extract actor's role from authenticated request
2. Retrieve allowed scopes for role from policy
3. Check if UI is allowed to use the capability (via `registry/ui.yaml`)
4. Check if role has required scopes for the capability
5. **Deny by default** if any check fails

**Implementation:**
- Location: `platform/Policy.php`
- Methods: `isAllowed()`, `validateUIAccess()`, `getScopesForRole()`

#### Actor Role + UI Mapping
```
actor { role: "admin", ui: "admin" } 
  → ui.yaml allows capability 
  → policy.yaml grants scopes 
  → capability executed
```

### 5.3 Limits

#### Request Body Size Limit
Enforced before processing request payload.

**Configuration:**
```yaml
roles:
  admin:
    max_request_size: 10485760  # 10MB
  guest:
    max_request_size: 524288    # 512KB
```

**Behavior:**
- Exceeds limit: Returns HTTP 413 Payload Too Large
- Implementation: `Limits::checkRequestSize()`

#### Adapter Call Timeout
Prevents slow adapters from blocking the platform.

**Configuration:**
```bash
DEFAULT_TIMEOUT=30  # seconds
```

**Behavior:**
- Timeout enforced on all adapter invocations
- Implementation: `Limits::enforceTimeout()`

#### Rate Limiting Per Actor
Sliding window rate limiting per user/capability.

**Configuration:**
```yaml
capability_policies:
  car.create:
    rate_limit: 10  # requests per minute
```

**Behavior:**
- Exceeds limit: Returns HTTP 429 Too Many Requests
- Window: 60 seconds (sliding)
- Storage: File-based (production should use Redis)
- Implementation: `Limits::checkRateLimit()`

### 5.4 Audit Logging

#### Comprehensive Audit Trail
Every capability invocation is logged with full context.

**Logged Events:**
- `authentication_failed`: Failed authentication attempt
- `capability_invocation_start`: Capability invocation initiated
- `capability_invocation_success`: Successful execution
- `capability_invocation_error`: Failed execution

**Audit Entry Format:**
```json
{
  "run_id": "run_abc123",
  "event": "capability_invocation_success",
  "capability": "car.create",
  "adapter": "car-storage",
  "ui": "admin",
  "user_id": "admin_user",
  "role": "admin",
  "result": "ok",
  "timestamp": 1234567890,
  "date": "2024-01-01 12:00:00",
  "ip": "192.168.1.1"
}
```

**Storage:**
- Location: `$STORAGE_PATH/audit/YYYY-MM-DD.log`
- Format: JSON lines (one entry per line)
- Retention: Managed externally

**Implementation:**
- Location: `platform/Storage.php`
- Method: `logAudit()`
- Called by: `InvokeController` at all decision points

## Phase 6: ResultGate "Light" (UI Protection)

### Goal
UI always receives a "safe" result - no garbage, no PII, no dangerous content.

### 6.1 Allowlist Fields

#### Capability-Specific Field Filtering
Each capability defines which fields are allowed in the response.

**Configuration in `registry/capabilities.yaml`:**
```yaml
capabilities:
  car.read:
    adapter: car-storage
    description: "Read car information"
    allowed_fields:
      - id
      - brand
      - model
      - year
      - price
      - status
```

**Behavior:**
- Only fields in `allowed_fields` are returned
- Unlisted fields are stripped from response
- Nested arrays are filtered recursively
- Missing `allowed_fields` or `*`: No filtering applied

**Implementation:**
- Location: `platform/ResultGate.php`
- Method: `applyAllowlist()`

### 6.2 Response Size Limits

Prevents memory exhaustion from large adapter responses.

**Configuration:**
```bash
MAX_RESPONSE_SIZE=10485760  # 10MB
```

**Behavior:**
- Response exceeds limit: Returns error before processing
- Size measured: JSON-encoded response
- Implementation: `ResultGate::checkResponseSize()`

### 6.3 Dangerous Content Blocking

#### HTML/JS Sanitization
Blocks script tags, iframes, and JavaScript protocols.

**Detected Patterns:**
- `<script>` tags
- `<iframe>` tags
- `javascript:` protocol
- Event handlers (`onclick`, `onload`, etc.)

**Behavior:**
- Dangerous string detected: Replaced with `[BLOCKED: Dangerous content detected]`
- Applied recursively to all string values
- Implementation: `ResultGate::sanitizeDangerousContent()`

#### Large Array Limiting
Prevents UI from receiving massive arrays.

**Configuration:**
```bash
MAX_ARRAY_SIZE=1000  # items
```

**Behavior:**
- Array exceeds limit: Truncated to max size
- Metadata added: `_truncated=true`, `_total_count=N`
- Applied recursively to nested arrays
- Implementation: `ResultGate::limitArraySizes()`

### 6.4 Sensitive Field Removal

Automatically removes sensitive fields for non-admin users.

**Sensitive Fields:**
- `password`
- `secret`
- `token`
- `api_key`
- `private_key`

**Behavior:**
- No admin scope: Fields removed
- Admin scope: Fields preserved
- Applied recursively
- Implementation: `ResultGate::removeSensitiveFields()`

## Security Pipeline

Every request goes through this pipeline:

```
1. Authentication (X-API-Key)
   ↓ (401 if fails)
   
2. Authorization Check
   - UI allowed to use capability?
   - Role has required scopes?
   ↓ (403 if fails)
   
3. Limits Check
   - Rate limit OK?
   - Request size OK?
   ↓ (429/413 if fails)
   
4. Adapter Invocation
   - With timeout enforcement
   ↓ (timeout if slow)
   
5. ResultGate Filtering
   - Size check
   - Allowlist fields
   - Sanitize dangerous content
   - Limit arrays
   - Remove sensitive fields
   ↓
   
6. Audit Logging
   - Log success/error
   
7. Return Filtered Response
```

## Testing Security

### Test Authentication
```bash
# Should fail without API key (if ENABLE_AUTH=true)
curl -X POST http://localhost:8080/api/invoke \
  -H "Content-Type: application/json" \
  -d '{"capability": "car.list", "payload": {}}'

# Should succeed with valid API key
curl -X POST http://localhost:8080/api/invoke \
  -H "Content-Type: application/json" \
  -H "X-API-Key: admin_secret_key_12345" \
  -d '{"capability": "car.list", "payload": {}}'
```

### Test Authorization (Deny by Default)
```bash
# Guest trying admin-only capability - should fail
curl -X POST http://localhost:8080/api/invoke \
  -H "X-API-Key: public_secret_key_67890" \
  -H "Content-Type: application/json" \
  -d '{"capability": "car.create", "payload": {"brand": "Toyota"}}'
```

### Test Rate Limiting
```bash
# Send 20 requests quickly - should hit rate limit
for i in {1..20}; do
  curl -X POST http://localhost:8080/api/invoke \
    -H "X-API-Key: admin_secret_key_12345" \
    -H "Content-Type: application/json" \
    -d '{"capability": "car.create", "payload": {}}'
done
```

### Test ResultGate Filtering
The ResultGate automatically filters all responses - check audit logs to verify filtering occurred.

## Configuration Files

### registry/policy.yaml
Defines roles, scopes, and capability-specific policies.

### registry/capabilities.yaml
Maps capabilities to adapters and defines allowed fields.

### registry/ui.yaml
Defines which UIs can use which capabilities.

### .env
Contains security configuration and API keys.

## Best Practices

1. **Always use HTTPS in production** - API keys in plain HTTP are vulnerable
2. **Rotate API keys regularly** - Treat them as secrets
3. **Monitor audit logs** - Watch for suspicious patterns
4. **Keep allowlist fields minimal** - Only expose necessary data
5. **Set appropriate rate limits** - Balance usability and security
6. **Review policies regularly** - Ensure they match current requirements
7. **Use Redis for rate limiting in production** - File-based is for development only

## Future Enhancements

- JWT/Session authentication support
- OAuth2 integration
- Role-based field visibility (beyond admin/non-admin)
- Dynamic rate limiting based on load
- Anomaly detection in audit logs
- PII detection and redaction
- GraphQL query depth limiting
