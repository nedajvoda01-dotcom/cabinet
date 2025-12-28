# Integrations

This directory contains all external service integrations used by the Cabinet platform.

An integration represents a **boundary between the orchestrator and an external system**. Cabinet never embeds business logic of integrations and never assumes their availability or correctness.

Every integration is designed to be replaceable, observable, and failure-tolerant.

---

## Integration Model

Each integration follows a strict structural pattern:

- **Application Port**  
  Defined in `Application/Integrations/*`  
  Describes required capabilities and expected behavior.

- **Infrastructure Adapter**  
  Implements the port using concrete protocols and technologies.

- **Fallback (Fake) Adapter**  
  Provides minimal behavior when the real service is unavailable.

This guarantees that pipelines continue operating even under partial system failure.

---

## Directory Structure

Each integration directory typically contains:

- `<IntegrationName>Integration.php` — integration entry point
- `Real/` — real adapters calling external services
- `Fallback/` — fallback implementations
- Optional fixtures or simulators for degraded modes

Shared utilities are located in `Integrations/Shared`.

---

## Supported Integrations

The following integrations are planned and pre-allocated:

- **Parser**  
  External data extraction services.

- **PhotoProcessor**  
  Media processing services (e.g. masking, transformations).

- **Robot**  
  Automated publishing and synchronization services.

- **BrowserContext**  
  Browser automation and session-based operations.

- **Storage**  
  Persistent object storage and content management.

The orchestrator does not assume any specific business semantics for these services.

---

## Fallback Strategy

Fallback adapters are mandatory.

They are used when:
- the external service is unreachable
- the service returns invalid responses
- circuit breakers are open
- explicit degradation policies apply

Fallbacks are not mocks.  
They are minimal functional substitutes required to keep pipelines alive.

---

## Shared Integration Utilities

The `Shared` directory contains reusable integration infrastructure:

- HTTP clients
- request signing and encryption
- circuit breakers
- health caching
- error normalization
- configuration guards

These utilities must not encode integration-specific logic.

---

## Integration Registry

Registries provide:

- integration discovery
- certificate management
- capability introspection

Registries are read-only at runtime.

---

## Error Handling

Integration errors are classified and normalized.

They:
- never leak raw external errors
- never alter pipeline state directly
- are observable and auditable

All error handling is explicit.

---

## Extension Rules

When adding a new integration:

1. Define a port in the Application layer
2. Implement a real adapter
3. Implement a fallback adapter
4. Register capabilities
5. Add observability hooks
6. Add contract and boundary tests

No integration is allowed without a fallback.

---

## Design Guarantees

- Replaceable integrations
- Explicit failure handling
- Pipeline continuity
- No hidden dependencies
- Strict separation of concerns

---

## Status

Integrations evolve independently from the orchestrator core.  
Backward compatibility at the port level is strongly preferred.
