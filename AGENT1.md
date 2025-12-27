# AGENT1.md
## Architecture & Engineering Principles (Execution Platform / Conveyor)

> This document is written for Codex and engineers.
> It defines **what this system is**, **what it must never become**, and **how to extend it safely**.
> The goal is to keep the core **frozen**, while allowing unlimited growth of integrations (“executors”) at the edges.

---

## 0. Glossary

- **System / Platform / Conveyor / Machine** — this backend. It orchestrates execution but does not “do the work”.
- **Executor** — an external service that performs real work (robot, parser, photo processor, browser context, storage, etc.).
- **Integration** — the plug-in boundary that connects the platform to an executor.
- **Port** — an interface (contract) for an integration; the platform depends only on ports.
- **Adapter** — a concrete implementation of a port (`Real` or `Fake`).
- **Fallback** — wrapper that decides whether to use Real or Fake at runtime based on health/config/policy.
- **Driver** — “integration language”: a protocol layer describing what to provide to executors and what to expect back, so orchestrator/worker stays thin.
- **Pipeline** — execution lifecycle inside the platform (jobs, retries, DLQ, locks, idempotency).
- **Read Model** — query-side response assembly using `include/fields` to avoid heavy calls unless explicitly requested.

---

## 1. What this system is (and is not)

### 1.1 The system is an Execution Platform (a conveyor / machine)

This system exists to:

1) **accept and store neutral input data**  
2) **orchestrate execution** (jobs, ordering, retries, DLQ, timeouts)  
3) **provide favorable conditions for executors** (context, storage, observability, safety)  
4) **produce a normalized result** (internal status/error/result format)

> The platform does **not** know *how* the work is done. It only guarantees it is done **reliably** or fails **explainably**.

### 1.2 The system is NOT business logic, and knows nothing about “external world meaning”

Forbidden inside the platform (above adapters):
- naming external platforms, websites, marketplaces, vendor-specific rules
- vendor-specific statuses or domain terms
- “how-to” logic for completing tasks in an external world
- “formatting payloads” for specific external targets (unless it is purely internal-neutral and not target-specific)
- making decisions based on external rules

Allowed:
- neutral data structures (snapshots, assets, hints)
- normalized statuses and error kinds
- reliability mechanics
- safety and observability
- *capability-based* behavior (“executor supports X”) without knowing “why”

---

## 2. Core architectural principle: Frozen core, flexible edges

### 2.1 What is frozen
- Domain invariants (data correctness rules)
- Pipeline mechanics (jobs, retry, DLQ, idempotency, locks)
- Contract primitives (Status, Error, AssetRef, IntegrationHealth, Capabilities)
- Layer boundaries and “who is allowed to depend on whom”

### 2.2 What is flexible
- Integrations: new ports can be added endlessly, without changing the core design.
- Drivers: protocols can evolve as more executors appear.
- UI / frontend can evolve independently; only API contract matters.

> New executors are “plugged into the machine”. The machine’s internal mechanism remains stable.

---

## 3. Layering and dependency direction (non-negotiable)

### 3.1 Layers
HTTP
↓
Application (Commands / Queries / Pipeline)
↓
Domain (Invariants)
↓
Infrastructure (DB, Queue, Observability, Integrations)

yaml
Копировать код

### 3.2 Dependency rules
- **Domain** depends on nothing else.
- **Application** depends on Domain and on *Port interfaces* (contracts), never on Real adapters.
- **Infrastructure** provides implementations (PDO, queue, real adapters, fake adapters).
- **HTTP** calls Application only; HTTP must not talk to Integrations directly.

**Hard rule:** No file in Domain/Application imports concrete adapter classes (`Real/*Adapter.php`).

---

## 4. Why we use Ports + Adapters + Registry (integrations as plugins)

### 4.1 Integrations are plugins
The platform must support **unlimited** number of executors.
Today we plan ~5 core integrations; later there can be more without redesign.

### 4.2 Each integration is self-contained
Each integration provides:
- **Port interface**: contract
- **Real adapter**: actual external calls
- **Fake adapter**: internal fallback executor
- **Descriptor**: registration + capabilities + version

### 4.3 Registry auto-wires integrations
A registry is responsible for:
- discovering integration descriptors
- registering ports into DI
- providing aggregated integration status
- providing a stable surface for “capabilities”

This prevents container “spaghetti wiring” and makes integrations uniform.

---

## 5. Real / Fake / Fallback is the default model

### 5.1 Why Fake exists inside the platform
Fake executors exist to:
- keep pipeline stable when external services are absent
- enable deterministic local/dev/test execution
- allow gradual integration rollout without blocking core development
- prevent “missing integration = crash”

Fake is not a mock. Fake implements the same Port and returns normalized results.

### 5.2 Fallback rules
Every port in DI is represented as:

Port = Fallback(Real, Fake)

markdown
Копировать код

Fallback chooses Real vs Fake based on:
- **config presence** (no config => Fake)
- **health** (cached TTL; unhealthy => Fake)
- **circuit breaker** (open => Fake)
- **policy** (some operations must not degrade to Fake in prod)

### 5.3 Prod safety rule: “effectful commands”
Some commands produce real-world effects (publish, delete, send, etc.).
For such commands:
- in production, silent fallback to Fake is forbidden
- if Real is unavailable, return a normalized fatal error:
  - `ErrorKind = permanent` (or specific kind)
  - `code = INTEGRATION_UNAVAILABLE` (example)
- the platform must remain explainable: no “pretend success”.

This is controlled via `DegradePolicy`.

---

## 6. Drivers: the integration protocol layer (“language”)

### 6.1 Why Drivers exist
Without Drivers, workers/orchestrator grows forever:
- each new executor adds new details in workers
- workers become protocol-heavy and vendor-shaped

Drivers isolate protocol knowledge:
- what inputs are required
- what sequence of calls is required
- how to prepare favorable conditions (e.g., context allocation)
- how to normalize results
- how to cleanup resources

### 6.2 Control flow
Worker → Driver → Ports → Adapters (Real/Fake)

markdown
Копировать код

Workers stay thin and stable; Drivers evolve.

### 6.3 What Drivers may do
- collect snapshot/data from core services
- call multiple ports in a specific order
- ensure cleanup in finally/compensation steps
- enforce `DegradePolicy` and Guards

Drivers must not:
- contain vendor-specific logic about “how work is done”
- leak external status codes upward
- bypass Ports and call Real adapters directly

---

## 7. Pipeline mechanics (machine reliability)

### 7.1 The pipeline is responsible for reliability, not meaning
The pipeline does:
- job scheduling and execution
- retries and backoff
- DLQ (dead-letter queue)
- idempotency
- locks/leases
- deadlines/time budgets
- compensation/cleanup jobs

The pipeline does not:
- interpret external-world rules
- encode “how to do the task”
- depend on vendor concepts

### 7.2 Standard job model
Each job must carry:
- `type`
- `subjectId` (e.g., cardId)
- `attempt`
- `idempotencyKey`
- `traceId`
- payload (typed or validated)

### 7.3 Idempotency (mandatory for repeating operations)
Idempotency ensures:
- repeating the same command does not produce duplicate runs
- crash/retry does not create duplicates
- network timeouts do not cause double execution

Idempotency belongs to pipeline/application, not to HTTP controllers.

### 7.4 Locks / leases (mandatory for conflicting effects)
For operations that must not run concurrently on the same subject:
- use `LockService` / lease-based lock on subjectId
- ensure lock has TTL and safe release
- avoid deadlocks by consistent lock keys and timeouts

---

## 8. Selective data flow: include/fields + Read Model

### 8.1 Goal
“Data should flow easily through monolith *only when requested*”.
The system must avoid:
- bloating internal layers with optional details
- calling executors for every GET request
- over-fetching by default

### 8.2 include/fields contract
Read endpoints:
- return minimal data by default
- accept `?include=` and/or `?fields=`
- only when requested, read model builder adds:
  - assets links/presigns
  - integration status (cached)
  - history/audit
  - queue state, etc.

### 8.3 Read Model Builder
Query handlers assemble response:
- from DB
- optionally from integration health (cached TTL)
- never call “effectful” port operations on read path

---

## 9. Observability (the machine must explain itself)

### 9.1 TraceId is mandatory
- Generated at HTTP entry if missing
- Propagated into jobs
- Added to integration calls headers
- Included in error responses

### 9.2 Unified error and status format
External errors must be normalized into:
- internal `ErrorKind` (`transient`, `permanent`, `auth`, `rate_limit`, `bad_input`, etc.)
- stable error `code`
- safe `message`
- optional `details` (no secrets)

No raw external stack traces or vendor payloads are exposed publicly.

### 9.3 Integration status endpoints
Required endpoints:
- `/api/integrations/status` — technical view: per integration health, mode, reason
- `/api/capabilities` — product view: features/integrations/rbac (for UI)

These endpoints are essential to answer:
- what is connected?
- what runs in real vs fake?
- why fallback is active?

---

## 10. Security rules

Mandatory:
- redact secrets in logs and errors
- validate request sizes and schemas
- validate webhook signatures (if used)
- protect against SSRF if any URL input is accepted
- never store or echo raw tokens externally
- never let external executors shape internal domain (no vendor names above adapters)

---

## 11. Checks and validations must be layered (no “sprinkled checks”)

Checks are categorized and belong to specific layers:

### 11.1 HTTP Validators (format-level)
- JSON validity
- body size limits
- request schema
- include/fields limits and allowlists
- auth parsing and RBAC gating

### 11.2 Domain invariants (data-level)
- impossible states are forbidden
- stage transitions are validated
- internal entities remain consistent

### 11.3 Application Guards (action-level)
- can the stage be started now?
- are assets references valid?
- payload size limits before sending to executor
- URL allowlists if applicable

### 11.4 Policies (behavior-level)
- degrade policy (prod vs dev)
- access policy (RBAC at domain action level)
- limits policy (max include depth, max assets count, etc.)

### 11.5 Integration boundary checks (network/contract-level)
- config presence
- health cache TTL
- circuit breaker
- timeouts and limited retries
- contract validation for executor responses
- error normalization to internal ErrorKind

---

## 12. Frontend independence

Frontend and backend are independent.
They are connected only through:
- stable API endpoints
- backward-compatible schema evolution
- `include/fields` for optional data
- `/capabilities` for feature availability

Frontend can be reorganized and made more complex without backend changes,
unless new data or new operations are required.

---

## 13. Target Folder Tree (full)

This is the **target** structure (migrate gradually).
It includes layered checks, drivers, pipeline mechanics, and plugin integrations.

```text
backend/
├─ public/
│  └─ index.php
│
├─ src/
│  ├─ Bootstrap/
│  │  ├─ AppKernel.php
│  │  ├─ Container.php
│  │  ├─ Config.php
│  │  └─ Clock.php
│  │
│  ├─ Config/
│  │  ├─ config.php
│  │  ├─ policies.php
│  │  ├─ integrations.php
│  │  └─ features.php
│  │
│  ├─ Http/
│  │  ├─ Routes/
│  │  │  └─ api.php
│  │  ├─ Middleware/
│  │  │  ├─ CorsMiddleware.php
│  │  │  ├─ TraceIdMiddleware.php
│  │  │  ├─ JsonBodyMiddleware.php
│  │  │  ├─ BodySizeLimitMiddleware.php
│  │  │  ├─ AuthMiddleware.php
│  │  │  ├─ RbacMiddleware.php
│  │  │  ├─ WebhookAuthMiddleware.php
│  │  │  └─ ErrorHandlerMiddleware.php
│  │  ├─ Validators/
│  │  │  ├─ RequestSchemaValidator.php
│  │  │  ├─ IncludeFieldsValidator.php
│  │  │  ├─ PaginationValidator.php
│  │  │  └─ IdempotencyKeyValidator.php
│  │  ├─ Controllers/
│  │  │  ├─ AuthController.php
│  │  │  ├─ UsersController.php
│  │  │  ├─ CardsController.php
│  │  │  ├─ ParserController.php
│  │  │  ├─ PhotosController.php
│  │  │  ├─ PublishController.php
│  │  │  ├─ ExportController.php
│  │  │  ├─ AdminController.php
│  │  │  └─ IntegrationsController.php
│  │  └─ Responses/
│  │     ├─ ApiResponse.php
│  │     ├─ ApiErrorMapper.php
│  │     └─ ProblemDetails.php
│  │
│  ├─ Domain/
│  │  ├─ Shared/
│  │  │  ├─ Enum/
│  │  │  │  └─ TaskStatus.php
│  │  │  ├─ ValueObject/
│  │  │  │  ├─ Ulid.php
│  │  │  │  └─ Email.php
│  │  │  └─ Exceptions/
│  │  │     ├─ DomainException.php
│  │  │     └─ InvariantViolation.php
│  │  ├─ Cards/
│  │  │  ├─ Card.php
│  │  │  ├─ CardId.php
│  │  │  ├─ CardSnapshot.php
│  │  │  └─ CardInvariants.php
│  │  ├─ Users/
│  │  │  ├─ User.php
│  │  │  ├─ UserId.php
│  │  │  └─ Role.php
│  │  └─ Pipeline/
│  │     ├─ Stage.php
│  │     ├─ PipelineState.php
│  │     └─ StageTransitionRules.php
│  │
│  ├─ Application/
│  │  ├─ Contracts/
│  │  │  ├─ Status.php
│  │  │  ├─ Error.php
│  │  │  ├─ ErrorKind.php
│  │  │  ├─ AssetRef.php
│  │  │  ├─ IntegrationHealth.php
│  │  │  ├─ CapabilitySet.php
│  │  │  └─ TraceContext.php
│  │  ├─ Policies/
│  │  │  ├─ DegradePolicy.php
│  │  │  ├─ RateLimitPolicy.php
│  │  │  ├─ AccessPolicy.php
│  │  │  └─ LimitsPolicy.php
│  │  ├─ Guards/
│  │  │  ├─ StageGuard.php
│  │  │  ├─ AssetRefGuard.php
│  │  │  ├─ UrlGuard.php
│  │  │  └─ PayloadSizeGuard.php
│  │  ├─ Commands/
│  │  │  ├─ Auth/
│  │  │  │  ├─ LoginCommand.php
│  │  │  │  ├─ RefreshTokenCommand.php
│  │  │  │  └─ LogoutCommand.php
│  │  │  ├─ Cards/
│  │  │  │  ├─ CreateCardCommand.php
│  │  │  │  ├─ UpdateCardCommand.php
│  │  │  │  ├─ DeleteCardCommand.php
│  │  │  │  ├─ TriggerParseCommand.php
│  │  │  │  ├─ TriggerPhotosCommand.php
│  │  │  │  └─ TriggerPublishCommand.php
│  │  │  └─ Admin/
│  │  │     ├─ RetryJobCommand.php
│  │  │     ├─ CancelJobCommand.php
│  │  │     └─ ForceCleanupCommand.php
│  │  ├─ Queries/
│  │  │  ├─ Cards/
│  │  │  │  ├─ GetCardQuery.php
│  │  │  │  ├─ ListCardsQuery.php
│  │  │  │  └─ CardReadModelBuilder.php
│  │  │  ├─ Admin/
│  │  │  │  ├─ GetQueuesQuery.php
│  │  │  │  ├─ GetDlqQuery.php
│  │  │  │  └─ GetAuditQuery.php
│  │  │  └─ Integrations/
│  │  │     ├─ GetIntegrationsStatusQuery.php
│  │  │     └─ GetCapabilitiesQuery.php
│  │  ├─ Pipeline/
│  │  │  ├─ Jobs/
│  │  │  │  ├─ Job.php
│  │  │  │  ├─ JobType.php
│  │  │  │  └─ JobPayload.php
│  │  │  ├─ Retry/
│  │  │  │  ├─ RetryPolicy.php
│  │  │  │  ├─ RetryDecision.php
│  │  │  │  └─ ErrorClassifier.php
│  │  │  ├─ Idempotency/
│  │  │  │  ├─ IdempotencyKey.php
│  │  │  │  ├─ IdempotencyStore.php
│  │  │  │  └─ IdempotencyService.php
│  │  │  ├─ Locks/
│  │  │  │  ├─ LockService.php
│  │  │  │  └─ LockKey.php
│  │  │  ├─ Drivers/
│  │  │  │  ├─ StageDriverInterface.php
│  │  │  │  ├─ ParserDriver.php
│  │  │  │  ├─ PhotosDriver.php
│  │  │  │  ├─ PublishDriver.php
│  │  │  │  ├─ ExportDriver.php
│  │  │  │  └─ CleanupDriver.php
│  │  │  ├─ Workers/
│  │  │  │  ├─ WorkerDaemon.php
│  │  │  │  ├─ ParserWorker.php
│  │  │  │  ├─ PhotosWorker.php
│  │  │  │  ├─ ExportWorker.php
│  │  │  │  ├─ PublishWorker.php
│  │  │  │  └─ RobotStatusWorker.php
│  │  │  └─ Events/
│  │  │     ├─ PipelineEvent.php
│  │  │     └─ EventEmitter.php
│  │  └─ Services/
│  │     ├─ AuthService.php
│  │     ├─ UsersService.php
│  │     ├─ CardsService.php
│  │     ├─ AdminService.php
│  │     ├─ CapabilitiesService.php
│  │     └─ IntegrationsStatusService.php
│  │
│  └─ Infrastructure/
│     ├─ Persistence/
│     │  └─ PDO/
│     │     ├─ ConnectionFactory.php
│     │     ├─ MigrationsRunner.php
│     │     └─ Repositories/
│     │        ├─ CardsRepository.php
│     │        ├─ UsersRepository.php
│     │        ├─ QueueRepository.php
│     │        ├─ DlqRepository.php
│     │        ├─ AuditRepository.php
│     │        ├─ IdempotencyRepository.php
│     │        └─ LockRepository.php
│     ├─ Queue/
│     │  ├─ QueueService.php
│     │  ├─ DlqService.php
│     │  └─ QueueMetrics.php
│     ├─ Ws/
│     │  ├─ WsServer.php
│     │  └─ WsEmitter.php
│     ├─ Observability/
│     │  ├─ Logger/
│     │  │  ├─ LoggerInterface.php
│     │  │  ├─ DbLogger.php
│     │  │  └─ Redactor.php
│     │  ├─ Audit/
│     │  │  └─ AuditService.php
│     │  └─ Metrics/
│     │     ├─ MetricsEmitter.php
│     │     └─ NullMetricsEmitter.php
│     └─ Integrations/
│        ├─ _shared/
│        │  ├─ HttpClient.php
│        │  ├─ ContractValidator.php
│        │  ├─ Headers.php
│        │  ├─ HealthCache.php
│        │  ├─ CircuitBreaker.php
│        │  ├─ FallbackPort.php
│        │  ├─ ConfigGuard.php
│        │  ├─ ErrorMapper.php
│        │  └─ IntegrationException.php
│        ├─ Registry/
│        │  ├─ IntegrationDescriptorInterface.php
│        │  └─ IntegrationRegistry.php
│        ├─ Robot/
│        │  ├─ RobotPort.php
│        │  ├─ RobotIntegration.php
│        │  ├─ Real/
│        │  │  ├─ RobotHttpAdapter.php
│        │  │  ├─ RobotContracts.php
│        │  │  └─ RobotWebhookHandler.php
│        │  └─ Fake/
│        │     ├─ FakeRobotAdapter.php
│        │     └─ FakeRobotScenario.php
│        ├─ BrowserContext/
│        │  ├─ BrowserContextPort.php
│        │  ├─ BrowserContextIntegration.php
│        │  ├─ Real/BrowserContextHttpAdapter.php
│        │  └─ Fake/
│        │     ├─ FakeBrowserContextAdapter.php
│        │     └─ FakeBrowserContextPool.php
│        ├─ Parser/
│        │  ├─ ParserPort.php
│        │  ├─ ParserIntegration.php
│        │  ├─ Real/ParserHttpAdapter.php
│        │  └─ Fake/
│        │     ├─ FakeParserAdapter.php
│        │     └─ FakeParserFixtures.php
│        ├─ PhotoProcessor/
│        │  ├─ PhotoProcessorPort.php
│        │  ├─ PhotoProcessorIntegration.php
│        │  ├─ Real/PhotoProcessorHttpAdapter.php
│        │  └─ Fake/
│        │     ├─ FakePhotoProcessorAdapter.php
│        │     └─ FakePhotoPipeline.php
│        └─ Storage/
│           ├─ StoragePort.php
│           ├─ StorageIntegration.php
│           ├─ Real/S3StorageAdapter.php
│           └─ Fake/
│              ├─ FakeStorageAdapter.php
│              └─ FakeStorageFs.php
│
├─ tests/
│  ├─ Unit/
│  │  ├─ Architecture/
│  │  │  ├─ BoundariesTest.php
│  │  │  ├─ ContractParityTest.php
│  │  │  └─ IncludeFieldsLimitsTest.php
│  │  ├─ Pipeline/
│  │  │  ├─ RetryPolicyTest.php
│  │  │  ├─ ErrorClassifierTest.php
│  │  │  ├─ IdempotencyTest.php
│  │  │  └─ LockServiceTest.php
│  │  └─ Integrations/
│  │     ├─ BreakerHealthCacheTest.php
│  │     └─ FallbackSwitchTest.php
│  └─ Integration/
│     ├─ ApiSmokeTest.php
│     └─ WorkerDaemonSmokeTest.php
│
└─ _legacy/
   └─ src/
      └─ Modules/
         └─ Robot/
            ├─ RobotService.php
            └─ README.md
14. How to add a new executor (unlimited ports)
The platform supports unlimited executors. Adding a new one must be uniform.

Step-by-step
Create a new integration folder:

swift
Копировать код
src/Infrastructure/Integrations/NewThing/
Define the port contract:

Копировать код
NewThingPort.php
Implement integration descriptor:

Копировать код
NewThingIntegration.php
Descriptor must provide:

id (integration name)

contractVersion

capabilities (string list)

buildReal(config) and buildFake()

wiring through _shared/FallbackPort with health/breaker rules

Implement adapters:

sql
Копировать код
Real/NewThingHttpAdapter.php
Fake/FakeNewThingAdapter.php
If the executor requires protocol choreography, create a Driver:

swift
Копировать код
src/Application/Pipeline/Drivers/NewThingDriver.php
Register the descriptor in Config/integrations.php (or rely on autoload scanning if used).

No changes in Domain are required.
No changes in Pipeline mechanics are required.
Only new integration and (optionally) a driver are added.

15. Practical “DO / DON’T” quick list
DO:

keep all external meaning inside adapters

normalize all statuses/errors to internal contracts

keep workers thin; push protocol into drivers

use fallback by default

use include/fields to avoid expensive reads

DON’T:

do vendor-specific mapping in Domain/Application

call Real adapters directly

let controllers talk to integrations

add checks “where convenient” (checks must be layered)

silently succeed in production when effectful operations degraded to fake

16. Final definition
The system is an isolated execution conveyor.
It does not know external-world goals, rules, or formats.
It guarantees reliability, safety, and reproducibility of execution.
All real actions are performed outside the system via adapters.

## 17. Migration strategy (how to get here safely)

This architecture is **a target state**, not a big-bang rewrite.

### 17.1. Migration principles
- Do **not** stop feature work.
- Do **not** refactor everything at once.
- Do **not** introduce parallel architectures for long.

### 17.2. Recommended order
1) **Freeze rules**  
   - Introduce this document (`AGENT1.md`) as a hard reference.
   - Add architecture boundary tests if possible.

2) **Isolate integrations**
   - Move all external calls behind Ports.
   - Introduce Fake adapters for every integration.
   - Introduce Fallback as default wiring.

3) **Extract Drivers**
   - Move protocol logic out of workers into Drivers.
   - Workers become thin delegates.

4) **Normalize pipeline**
   - Introduce idempotency, retry policy, error classification.
   - Add DLQ and lock/lease where needed.

5) **Clean reads**
   - Introduce `include/fields`.
   - Move response assembly to ReadModel builders.

6) **Move legacy code**
   - Any old-style integration logic goes into `_legacy/`.
   - No two approaches should coexist in active code.

---

## 18. Testing strategy (what must be tested and where)

### 18.1. Unit tests (mandatory)
- Domain invariants (pure logic)
- Guards and Policies
- RetryPolicy and ErrorClassifier
- IdempotencyService
- LockService

### 18.2. Contract parity tests (mandatory)
For every integration:
- Real and Fake adapters must conform to the same Port contract.
- Same input → same shape of output (status/error), even if behavior differs.

### 18.3. Architecture boundary tests (strongly recommended)
Tests that assert:
- Domain does not depend on Infrastructure.
- Application does not import Real adapters.
- Controllers do not call integrations directly.

### 18.4. Smoke tests
- API boots
- Worker daemon boots
- Pipeline can enqueue and process a fake job end-to-end

---

## 19. Versioning and evolution rules

### 19.1. API evolution
- Existing fields must never be removed or changed.
- New fields must be optional.
- Breaking changes require a new API version.

### 19.2. Integration contract evolution
- Each integration exposes `contractVersion`.
- Breaking changes in executor contracts require:
  - new adapter version, or
  - new integration ID.

The core must remain backward-compatible.

---

## 20. Failure philosophy

Failures are **first-class citizens**.

Rules:
- Every failure must be classified.
- Every failure must be observable.
- Every failure must be explainable.

No:
- silent retries without trace
- silent fallback in production for effectful commands
- raw external errors exposed to clients

---

## 21. Performance and cost discipline

The platform must:
- avoid calling integrations on read paths unless explicitly requested
- cache health and capability checks with TTL
- avoid repeated retries when circuit is open
- keep fake execution cheap and deterministic

Reliability must not turn into waste.

---

## 22. What success looks like

You know the architecture is working when:
- new executors are added without touching Domain or Pipeline core
- frontend evolves without backend changes
- failures are boring and explainable
- legacy code shrinks, never grows
- no one asks “where should this logic live?”

---

## 23. Final reminder (non-negotiable)

> If the system starts to “understand” the external world,  
> the architecture is already broken.

Everything that understands **how** work is done  
must live **outside the core**, at the edges, behind adapters.

The core is a machine.  
Machines must be boring, predictable, and hard to break.
