# Phase 6 Architecture Diagram

## Network Topology

```
┌─────────────────────────────────────────────────────────────────┐
│                          HOST MACHINE                            │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    EDGE NETWORK (public)                  │  │
│  │                                                            │  │
│  │  ┌──────────┐     ┌──────────┐     ┌──────────────────┐ │  │
│  │  │ UI Admin │     │ UI Public│     │    Platform      │ │  │
│  │  │  :3000   │────▶│  :3001   │────▶│      :8080       │ │  │
│  │  └──────────┘     └──────────┘     └────────┬─────────┘ │  │
│  │                                              │            │  │
│  └──────────────────────────────────────────────┼────────────┘  │
│                                                 │                │
│  ┌──────────────────────────────────────────────┼────────────┐  │
│  │                    MESH NETWORK (private)    │            │  │
│  │                                              │            │  │
│  │                                    ┌─────────▼─────────┐  │  │
│  │                                    │    Platform       │  │  │
│  │                                    │   (mesh side)     │  │  │
│  │                                    └─────────┬─────────┘  │  │
│  │                                              │            │  │
│  │         ┌────────────────────┬───────────────┼─────────┐  │  │
│  │         │                    │               │         │  │  │
│  │  ┌──────▼──────┐   ┌────────▼──────┐  ┌────▼──────┐  │  │
│  │  │ car-storage │   │    pricing    │  │ automation│  │  │
│  │  │   adapter   │   │    adapter    │  │  adapter  │  │  │
│  │  │  (no port)  │   │  (no port)    │  │ (no port) │  │  │
│  │  └─────────────┘   └───────────────┘  └───────────┘  │  │
│  │                                                        │  │
│  │  Adapters CANNOT communicate with each other          │  │
│  │  Adapters CANNOT reach UI                             │  │
│  │  Adapters CANNOT be accessed from outside             │  │
│  └────────────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────────────┘

Legend:
  ────▶  Allowed communication
  ─ ─ ▶  Blocked communication
```

## Capability Execution Pipeline

```
┌─────────────────────────────────────────────────────────────────┐
│                     HTTP Request from UI                         │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    CapabilityExecutor                            │
│  (Unified pipeline for ALL capability invocations)              │
│                                                                   │
│  Step 1: Authentication                                          │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ • Verify X-API-Key                                       │   │
│  │ • Extract actor (user_id, role, ui)                     │   │
│  │ • Track in audit log                                     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                             │                                    │
│                             ▼                                    │
│  Step 2: Policy Check                                           │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ • Check UI access (registry/ui.yaml)                    │   │
│  │ • Check role scopes (registry/policy.yaml)              │   │
│  │ • Check internal capability rules                        │   │
│  │ • Validate capability chains                             │   │
│  │ • DENY BY DEFAULT                                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                             │                                    │
│                             ▼                                    │
│  Step 3: Limits Check                                           │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ • Rate limiting (per user, per capability)              │   │
│  │ • Request size check                                     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                             │                                    │
│                             ▼                                    │
│  Step 4: Routing                                                │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ • Find adapter for capability                            │   │
│  │ • Load adapter configuration                             │   │
│  └─────────────────────────────────────────────────────────┘   │
│                             │                                    │
│                             ▼                                    │
│  Step 5: Invoke                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ • Call adapter via HTTP                                  │   │
│  │ • Apply timeout                                          │   │
│  │ • Pass actor context                                     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                             │                                    │
│                             ▼                                    │
│  Step 6: ResultGate                                             │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ • Check response size                                    │   │
│  │ • Apply result profile (UI-specific fields)             │   │
│  │ • Block dangerous content (HTML/JS)                      │   │
│  │ • Limit array sizes                                      │   │
│  │ • Remove sensitive fields                                │   │
│  └─────────────────────────────────────────────────────────┘   │
│                             │                                    │
│                             ▼                                    │
│  Step 7: Audit                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ • Log success/failure                                    │   │
│  │ • Record timing                                          │   │
│  │ • Track chain context                                    │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Filtered Response to UI                        │
└─────────────────────────────────────────────────────────────────┘
```

## Capability Chain Example

```
User Action: Import CSV
     │
     ▼
┌──────────────────────────────────────────────────────────┐
│ UI calls: import.run                                      │
│ • Actor: {user_id: "admin", role: "admin", ui: "admin"} │
│ • Payload: {filename, csv_data}                          │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ Platform: CapabilityExecutor.executeCapability()         │
│ • Checks: import.run is PUBLIC capability                │
│ • Checks: admin UI can call import.run                   │
│ • Checks: admin role has write scope                     │
│ • ✓ ALLOWED                                              │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ Adapter: car-storage handles import.run                  │
│ • Calculate content hash (SHA256)                        │
│ • Needs to register import...                            │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ Internal Call: storage.imports.register                  │
│ • Context: {parent_capability: "import.run"}             │
│ • Actor: {ui: "internal", role: "admin"}                │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ Platform: CapabilityExecutor.executeCapability()         │
│ • Checks: storage.imports.register is INTERNAL           │
│ • Checks: parent_capability is import.run                │
│ • Checks: import.run → storage.imports.register ALLOWED  │
│ • ✓ ALLOWED (chain valid)                                │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ Adapter: Registers import, returns status                │
│ • If duplicate → return "duplicate"                      │
│ • If new → proceed with import                           │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ Internal Call: storage.listings.upsert_batch             │
│ • Context: {parent_capability: "import.run"}             │
│ • Payload: {listings: [...]}                             │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ Platform: CapabilityExecutor.executeCapability()         │
│ • Checks: storage.listings.upsert_batch is INTERNAL      │
│ • Checks: parent_capability is import.run                │
│ • Checks: import.run → storage.listings.upsert_batch OK  │
│ • ✓ ALLOWED (chain valid)                                │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ Adapter: Upserts listings by external_id                 │
│ • Create if new, update if exists                        │
│ • Returns: {created: N, updated: M, failed: 0}          │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ Internal Call: storage.imports.mark_done                 │
│ • Context: {parent_capability: "import.run"}             │
│ • Stats: {created, updated, failed}                      │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ Response to UI: Filtered by result profile               │
│ • Admin UI sees: full stats                              │
│ • Public UI would see: limited stats                     │
└──────────────────────────────────────────────────────────┘

BLOCKED Example:
┌──────────────────────────────────────────────────────────┐
│ UI calls: storage.listings.upsert_batch (DIRECT)         │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ Platform: CapabilityExecutor.executeCapability()         │
│ • Checks: storage.listings.upsert_batch is INTERNAL      │
│ • Checks: no parent_capability in context                │
│ • ✗ DENIED - Internal capability cannot be called        │
│   directly from UI                                        │
│ • Returns: HTTP 403 Forbidden                            │
└──────────────────────────────────────────────────────────┘
```

## Result Profile Filtering

```
Adapter Returns Full Data:
┌─────────────────────────────────────────────────────────┐
│ {                                                        │
│   "id": "listing_123",                                  │
│   "brand": "Toyota",                                    │
│   "model": "Camry",                                     │
│   "year": 2020,                                         │
│   "price": 25000,                                       │
│   "status": "available",                                │
│   "vin": "ABC123",                                      │
│   "owner_id": "owner_456",                              │
│   "owner_name": "John Doe",                             │
│   "owner_email": "john@example.com",                    │
│   "owner_phone": "+1234567890",                         │
│   "internal_notes": "VIP customer",                     │
│   "cost_price": 20000,                                  │
│   "profit_margin": 5000                                 │
│ }                                                        │
└─────────────────────────────────────────────────────────┘
                         │
        ┌────────────────┼────────────────┐
        │                │                │
        ▼                ▼                ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  Admin UI    │  │  Public UI   │  │Operations UI │
│ (internal_ui)│  │ (public_ui)  │  │  (ops_ui)    │
└──────┬───────┘  └──────┬───────┘  └──────┬───────┘
       │                 │                  │
       ▼                 ▼                  ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ ALL FIELDS   │  │ PUBLIC ONLY  │  │OPERATIONAL   │
│              │  │              │  │              │
│ ✓ id         │  │ ✓ id         │  │ ✓ id         │
│ ✓ brand      │  │ ✓ brand      │  │ ✓ brand      │
│ ✓ model      │  │ ✓ model      │  │ ✓ model      │
│ ✓ year       │  │ ✓ year       │  │ ✓ year       │
│ ✓ price      │  │ ✓ price      │  │ ✓ price      │
│ ✓ status     │  │ ✓ status     │  │ ✓ status     │
│ ✓ vin        │  │ ✗ vin        │  │ ✓ vin        │
│ ✓ owner_id   │  │ ✗ owner_id   │  │ ✓ owner_id   │
│ ✓ owner_name │  │ ✗ owner_name │  │ ✓ owner_name │
│ ✓ owner_email│  │ ✗ owner_email│  │ ✗ owner_email│
│ ✓ owner_phone│  │ ✗ owner_phone│  │ ✗ owner_phone│
│ ✓ notes      │  │ ✗ notes      │  │ ✗ notes      │
│ ✓ cost_price │  │ ✗ cost_price │  │ ✗ cost_price │
│ ✓ profit     │  │ ✗ profit     │  │ ✗ profit     │
└──────────────┘  └──────────────┘  └──────────────┘
```

## Import Idempotency Flow

```
CSV File: "cars.csv"
SHA256: abc123...

First Import:
┌─────────────────────────────────────────────────────────┐
│ 1. Calculate hash: abc123...                            │
│ 2. Check imports table: NOT FOUND                       │
│ 3. Register import: status=pending                      │
│ 4. Parse CSV rows                                       │
│ 5. Batch upsert:                                        │
│    - EXT001: CREATE (new external_id)                   │
│    - EXT002: CREATE (new external_id)                   │
│    - EXT003: CREATE (new external_id)                   │
│ 6. Mark done: status=done, stats={created:3}           │
│ 7. Return: {created:3, updated:0, failed:0}            │
└─────────────────────────────────────────────────────────┘

Second Import (Same File):
┌─────────────────────────────────────────────────────────┐
│ 1. Calculate hash: abc123...                            │
│ 2. Check imports table: FOUND (status=done)            │
│ 3. Return: {status: "duplicate", message: "Already     │
│             imported", created:0, updated:0}            │
│ 4. No database changes made                             │
└─────────────────────────────────────────────────────────┘

Re-Import with Updated Data:
┌─────────────────────────────────────────────────────────┐
│ 1. Calculate hash: xyz789... (different content)        │
│ 2. Check imports table: NOT FOUND                       │
│ 3. Register import: status=pending                      │
│ 4. Parse CSV rows (with price updates)                 │
│ 5. Batch upsert:                                        │
│    - EXT001: UPDATE (existing external_id)              │
│    - EXT002: UPDATE (existing external_id)              │
│    - EXT004: CREATE (new external_id)                   │
│ 6. Mark done: status=done, stats={created:1,updated:2} │
│ 7. Return: {created:1, updated:2, failed:0}            │
└─────────────────────────────────────────────────────────┘
```

## Security Layers

```
┌────────────────────────────────────────────────────────────┐
│                    DEFENSE IN DEPTH                         │
│                                                             │
│  Layer 1: Network Isolation                                │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ • Adapters on private mesh network                   │ │
│  │ • No published ports on adapters                     │ │
│  │ • UI isolated from adapters                          │ │
│  └──────────────────────────────────────────────────────┘ │
│                         │                                   │
│  Layer 2: Authentication                                   │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ • X-API-Key required                                 │ │
│  │ • Actor identification                               │ │
│  │ • Audit logging                                      │ │
│  └──────────────────────────────────────────────────────┘ │
│                         │                                   │
│  Layer 3: Authorization                                    │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ • UI access control (registry/ui.yaml)               │ │
│  │ • Role-based scopes (registry/policy.yaml)           │ │
│  │ • Capability chain validation                        │ │
│  │ • Internal capability protection                     │ │
│  │ • DENY BY DEFAULT                                    │ │
│  └──────────────────────────────────────────────────────┘ │
│                         │                                   │
│  Layer 4: Rate Limiting & Size Limits                      │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ • Per-user rate limits                               │ │
│  │ • Per-capability rate limits                         │ │
│  │ • Request size limits                                │ │
│  │ • Response size limits                               │ │
│  └──────────────────────────────────────────────────────┘ │
│                         │                                   │
│  Layer 5: Result Filtering                                 │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ • UI-specific field filtering                        │ │
│  │ • Dangerous content blocking (HTML/JS)               │ │
│  │ • Array size limiting                                │ │
│  │ • Sensitive field removal                            │ │
│  └──────────────────────────────────────────────────────┘ │
│                         │                                   │
│  Layer 6: Audit & Monitoring                               │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ • All operations logged                              │ │
│  │ • Chain context tracked                              │ │
│  │ • Success/failure recorded                           │ │
│  │ • Actor tracking                                     │ │
│  └──────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────┘
```
