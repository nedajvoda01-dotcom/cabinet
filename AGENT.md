# AGENT.md — CABINET INTERNAL SYSTEM GOVERNANCE

This document defines **mandatory behavior, constraints, and understanding rules**
for any AI agent, Codex-based system, automation, or developer interacting with
the Cabinet repository.

This is an **internal system**.
Creativity, assumptions, or architectural improvisation are **explicitly forbidden**.

---

### ✅ STRUCTURE REFERENCE (PASTE HERE)
Paste the authoritative tree here (from STRUCTURE.txt or your reference doc).  
Codex MUST treat it as the single source of truth.

```
cabinet/
├─ app
│  ├─ backend
│  │  ├─ public
│  │  │  └─ index.php
│  │  ├─ src
│  │  │  ├─ Application
│  │  │  │  ├─ Commands
│  │  │  │  │  ├─ Admin
│  │  │  │  │  │  ├─ CancelJobCommand.php
│  │  │  │  │  │  ├─ ForceCleanupCommand.php
│  │  │  │  │  │  └─ RetryJobCommand.php
│  │  │  │  │  ├─ Auth
│  │  │  │  │  │  ├─ LoginCommand.php
│  │  │  │  │  │  ├─ LogoutCommand.php
│  │  │  │  │  │  └─ RefreshTokenCommand.php
│  │  │  │  │  └─ Tasks
│  │  │  │  │     ├─ CreateTaskCommand.php
│  │  │  │  │     ├─ DeleteTaskCommand.php
│  │  │  │  │     ├─ TriggerParseCommand.php
│  │  │  │  │     ├─ TriggerPhotosCommand.php
│  │  │  │  │     ├─ TriggerPublishCommand.php
│  │  │  │  │     └─ UpdateTaskCommand.php
│  │  │  │  ├─ Integrations
│  │  │  │  │  ├─ BrowserContext
│  │  │  │  │  ├─ Parser
│  │  │  │  │  ├─ PhotoProcessor
│  │  │  │  │  ├─ Robot
│  │  │  │  │  └─ Storage
│  │  │  │  │     └─ StoragePort.php
│  │  │  │  ├─ Pipeline
│  │  │  │  │  ├─ Drivers
│  │  │  │  │  │  ├─ CleanupDriver.php
│  │  │  │  │  │  ├─ ExportDriver.php
│  │  │  │  │  │  ├─ ParserDriver.php
│  │  │  │  │  │  ├─ PhotosDriver.php
│  │  │  │  │  │  ├─ PublishDriver.php
│  │  │  │  │  │  └─ StageDriverInterface.php
│  │  │  │  │  ├─ Events
│  │  │  │  │  │  ├─ EventEmitter.php
│  │  │  │  │  │  └─ PipelineEvent.php
│  │  │  │  │  ├─ Idempotency
│  │  │  │  │  ├─ Jobs
│  │  │  │  │  ├─ Locks
│  │  │  │  │  ├─ Retry
│  │  │  │  │  └─ Workers
│  │  │  │  │     ├─ ExportWorker.php
│  │  │  │  │     ├─ ParserWorker.php
│  │  │  │  │     ├─ PhotosWorker.php
│  │  │  │  │     ├─ PublishWorker.php
│  │  │  │  │     ├─ RobotStatusWorker.php
│  │  │  │  │     └─ WorkerDaemon.php
│  │  │  │  ├─ Policies
│  │  │  │  │  ├─ AccessPolicy.php
│  │  │  │  │  ├─ DataMinimizationPolicy.php
│  │  │  │  │  ├─ DegradePolicy.php
│  │  │  │  │  ├─ HierarchyPolicy.php
│  │  │  │  │  ├─ LimitsPolicy.php
│  │  │  │  │  ├─ NoncePolicy.php
│  │  │  │  │  ├─ RateLimitPolicy.php
│  │  │  │  │  └─ SignaturePolicy.php
│  │  │  │  ├─ Preconditions
│  │  │  │  │  ├─ AssetRefGuard.php
│  │  │  │  │  ├─ CommandGuard.php
│  │  │  │  │  ├─ DataPrivacyGuard.php
│  │  │  │  │  ├─ PayloadSizeGuard.php
│  │  │  │  │  ├─ StageGuard.php
│  │  │  │  │  └─ UrlGuard.php
│  │  │  │  ├─ Queries
│  │  │  │  │  ├─ Admin
│  │  │  │  │  ├─ Integrations
│  │  │  │  │  ├─ Security
│  │  │  │  │  │  ├─ GetNonceUsageQuery.php
│  │  │  │  │  │  ├─ GetSecurityEventsQuery.php
│  │  │  │  │  │  └─ GetSignatureAuditQuery.php
│  │  │  │  │  └─ Tasks
│  │  │  │  ├─ Security
│  │  │  │  │  ├─ Encryption
│  │  │  │  │  │  └─ EncryptionServiceInterface.php
│  │  │  │  │  ├─ Keys
│  │  │  │  │  ├─ Nonce
│  │  │  │  │  └─ Signatures
│  │  │  │  └─ Services
│  │  │  │     ├─ AdminService.php
│  │  │  │     ├─ AuthService.php
│  │  │  │     ├─ CapabilitiesService.php
│  │  │  │     ├─ EncryptionService.php
│  │  │  │     ├─ IntegrationsStatusService.php
│  │  │  │     ├─ KeyRotationOrchestrator.php
│  │  │  │     ├─ KeyService.php
│  │  │  │     ├─ NonceService.php
│  │  │  │     ├─ SecurityService.php
│  │  │  │     ├─ SignatureService.php
│  │  │  │     ├─ TasksService.php
│  │  │  │     └─ UsersService.php
│  │  │  ├─ Bootstrap
│  │  │  │  ├─ AppKernel.php
│  │  │  │  ├─ Clock.php
│  │  │  │  ├─ Config.php
│  │  │  │  └─ Container.php
│  │  │  ├─ Domain
│  │  │  │  ├─ Pipeline
│  │  │  │  │  ├─ PipelineState.php
│  │  │  │  │  ├─ Stage.php
│  │  │  │  │  └─ StageTransitionRules.php
│  │  │  │  ├─ Shared
│  │  │  │  │  ├─ Enum
│  │  │  │  │  │  ├─ TaskStatus.php
│  │  │  │  │  │  └─ UserRole.php
│  │  │  │  │  ├─ Exceptions
│  │  │  │  │  │  ├─ DomainException.php
│  │  │  │  │  │  ├─ InvariantViolation.php
│  │  │  │  │  │  ├─ NonceReuseException.php
│  │  │  │  │  │  └─ SecurityException.php
│  │  │  │  │  └─ ValueObject
│  │  │  │  │     ├─ Email.php
│  │  │  │  │     ├─ EncryptedPayload.php
│  │  │  │  │     ├─ Nonce.php
│  │  │  │  │     └─ Ulid.php
│  │  │  │  ├─ Tasks
│  │  │  │  │  ├─ Task.php
│  │  │  │  │  ├─ TaskId.php
│  │  │  │  │  ├─ TaskInvariants.php
│  │  │  │  │  └─ TaskSnapshot.php
│  │  │  │  └─ Users
│  │  │  │     ├─ AccessRole.php
│  │  │  │     ├─ AccessScope.php
│  │  │  │     ├─ PermissionMatrix.php
│  │  │  │     ├─ User.php
│  │  │  │     ├─ UserHierarchy.php
│  │  │  │     └─ UserId.php
│  │  │  ├─ Http
│  │  │  │  ├─ Controllers
│  │  │  │  │  ├─ AdminController.php
│  │  │  │  │  ├─ AuthController.php
│  │  │  │  │  ├─ ExportController.php
│  │  │  │  │  ├─ HealthController.php
│  │  │  │  │  ├─ IntegrationsController.php
│  │  │  │  │  ├─ KeyExchangeController.php
│  │  │  │  │  ├─ ParserController.php
│  │  │  │  │  ├─ PhotosController.php
│  │  │  │  │  ├─ PublishController.php
│  │  │  │  │  ├─ SecurityController.php
│  │  │  │  │  ├─ TasksController.php
│  │  │  │  │  └─ UsersController.php
│  │  │  │  ├─ Middleware
│  │  │  │  │  ├─ BodySizeLimitMiddleware.php
│  │  │  │  │  ├─ CorsMiddleware.php
│  │  │  │  │  ├─ ErrorHandlerMiddleware.php
│  │  │  │  │  ├─ JsonBodyMiddleware.php
│  │  │  │  │  ├─ TraceIdMiddleware.php
│  │  │  │  │  └─ WebhookAuthMiddleware.php
│  │  │  │  ├─ Responses
│  │  │  │  │  ├─ ApiErrorMapper.php
│  │  │  │  │  ├─ ApiResponse.php
│  │  │  │  │  └─ ProblemDetails.php
│  │  │  │  ├─ Routes
│  │  │  │  ├─ Security
│  │  │  │  │  ├─ Pipeline
│  │  │  │  │  │  ├─ AuthStep.php
│  │  │  │  │  │  ├─ EncryptionStep.php
│  │  │  │  │  │  ├─ HierarchyStep.php
│  │  │  │  │  │  ├─ NonceStep.php
│  │  │  │  │  │  ├─ RateLimitStep.php
│  │  │  │  │  │  ├─ ScopeStep.php
│  │  │  │  │  │  ├─ SecurityPipelineMiddleware.php
│  │  │  │  │  │  └─ SignatureStep.php
│  │  │  │  │  └─ Requirements
│  │  │  │  │     ├─ EndpointRequirementsResolver.php
│  │  │  │  │     ├─ RouteRequirements.php
│  │  │  │  │     └─ RouteRequirementsMap.php
│  │  │  │  └─ Validation
│  │  │  │     ├─ Protocol
│  │  │  │     │  ├─ CommandStructureValidator.php
│  │  │  │     │  ├─ IdempotencyKeyValidator.php
│  │  │  │     │  └─ NonceFormatValidator.php
│  │  │  │     └─ Request
│  │  │  │        ├─ IncludeFieldsValidator.php
│  │  │  │        ├─ PaginationValidator.php
│  │  │  │        └─ RequestSchemaValidator.php
│  │  │  ├─ Infrastructure
│  │  │  │  ├─ BackgroundTasks
│  │  │  │  │  ├─ KeyRotationJob.php
│  │  │  │  │  ├─ KeyRotator.php
│  │  │  │  │  ├─ NonceCleanupJob.php
│  │  │  │  │  ├─ OldDataCleaner.php
│  │  │  │  │  └─ QueueOptimizer.php
│  │  │  │  ├─ Cache
│  │  │  │  │  ├─ CacheWarmer.php
│  │  │  │  │  ├─ QueryCache.php
│  │  │  │  │  └─ RedisCache.php
│  │  │  │  ├─ Integrations
│  │  │  │  │  ├─ BrowserContext
│  │  │  │  │  │  ├─ Fallback
│  │  │  │  │  │  │  ├─ FallbackBrowserContextAdapter.php
│  │  │  │  │  │  │  └─ FallbackBrowserContextPool.php
│  │  │  │  │  │  ├─ Real
│  │  │  │  │  │  │  └─ BrowserContextHttpAdapter.php
│  │  │  │  │  │  └─ BrowserContextIntegration.php
│  │  │  │  │  ├─ Parser
│  │  │  │  │  │  ├─ Fallback
│  │  │  │  │  │  │  ├─ FallbackParserAdapter.php
│  │  │  │  │  │  │  └─ FallbackParserFixtures.php
│  │  │  │  │  │  ├─ Real
│  │  │  │  │  │  │  └─ ParserHttpAdapter.php
│  │  │  │  │  │  └─ ParserIntegration.php
│  │  │  │  │  ├─ PhotoProcessor
│  │  │  │  │  │  ├─ Fallback
│  │  │  │  │  │  │  ├─ FallbackPhotoPipeline.php
│  │  │  │  │  │  │  └─ FallbackPhotoProcessorAdapter.php
│  │  │  │  │  │  ├─ Real
│  │  │  │  │  │  │  └─ PhotoProcessorHttpAdapter.php
│  │  │  │  │  │  └─ PhotoProcessorIntegration.php
│  │  │  │  │  ├─ Registry
│  │  │  │  │  │  ├─ CertificateRegistry.php
│  │  │  │  │  │  ├─ IntegrationDescriptorInterface.php
│  │  │  │  │  │  └─ IntegrationRegistry.php
│  │  │  │  │  ├─ Robot
│  │  │  │  │  │  ├─ Fallback
│  │  │  │  │  │  │  ├─ FallbackRobotAdapter.php
│  │  │  │  │  │  │  └─ FallbackRobotScenario.php
│  │  │  │  │  │  ├─ Real
│  │  │  │  │  │  │  ├─ RobotHttpAdapter.php
│  │  │  │  │  │  │  ├─ RobotMessages.php
│  │  │  │  │  │  │  └─ RobotWebhookHandler.php
│  │  │  │  │  │  └─ RobotIntegration.php
│  │  │  │  │  ├─ Shared
│  │  │  │  │  │  ├─ CircuitBreaker.php
│  │  │  │  │  │  ├─ ConfigGuard.php
│  │  │  │  │  │  ├─ ContractValidator.php
│  │  │  │  │  │  ├─ EncryptionWrapper.php
│  │  │  │  │  │  ├─ ErrorMapper.php
│  │  │  │  │  │  ├─ FallbackPort.php
│  │  │  │  │  │  ├─ Headers.php
│  │  │  │  │  │  ├─ HealthCache.php
│  │  │  │  │  │  ├─ HttpClient.php
│  │  │  │  │  │  ├─ IntegrationException.php
│  │  │  │  │  │  ├─ NonceGenerator.php
│  │  │  │  │  │  └─ SignedRequest.php
│  │  │  │  │  ├─ Storage
│  │  │  │  │  │  ├─ Fallback
│  │  │  │  │  │  │  ├─ FallbackStorageAdapter.php
│  │  │  │  │  │  │  └─ FallbackStorageFs.php
│  │  │  │  │  │  ├─ Real
│  │  │  │  │  │  │  └─ S3StorageAdapter.php
│  │  │  │  │  │  └─ StorageIntegration.php
│  │  │  │  │  └─ README.md
│  │  │  │  ├─ Observability
│  │  │  │  │  ├─ Audit
│  │  │  │  │  │  └─ AuditService.php
│  │  │  │  │  ├─ Health
│  │  │  │  │  │  ├─ DatabaseHealthCheck.php
│  │  │  │  │  │  ├─ health.php
│  │  │  │  │  │  ├─ HealthChecker.php
│  │  │  │  │  │  └─ RedisHealthCheck.php
│  │  │  │  │  ├─ Logging
│  │  │  │  │  │  ├─ DbLogger.php
│  │  │  │  │  │  ├─ Redactor.php
│  │  │  │  │  │  ├─ SecureLogger.php
│  │  │  │  │  │  ├─ SentryLogger.php
│  │  │  │  │  │  └─ StructuredLogger.php
│  │  │  │  │  ├─ Metrics
│  │  │  │  │  │  ├─ exporters
│  │  │  │  │  │  │  └─ PrometheusExporter.php
│  │  │  │  │  │  ├─ BusinessMetrics.php
│  │  │  │  │  │  └─ PrometheusMetrics.php
│  │  │  │  │  └─ Tracing
│  │  │  │  │     ├─ OpenTelemetryAdapter.php
│  │  │  │  │     └─ Tracer.php
│  │  │  │  ├─ Persistence
│  │  │  │  │  └─ PDO
│  │  │  │  │     ├─ Repositories
│  │  │  │  │     │  ├─ AuditRepository.php
│  │  │  │  │     │  ├─ DlqRepository.php
│  │  │  │  │     │  ├─ IdempotencyRepository.php
│  │  │  │  │     │  ├─ LockRepository.php
│  │  │  │  │     │  ├─ NonceRepository.php
│  │  │  │  │     │  ├─ QueueRepository.php
│  │  │  │  │     │  ├─ SecurityEventRepository.php
│  │  │  │  │     │  ├─ TasksRepository.php
│  │  │  │  │     │  └─ UsersRepository.php
│  │  │  │  │     ├─ ConnectionFactory.php
│  │  │  │  │     └─ MigrationsRunner.php
│  │  │  │  ├─ Queue
│  │  │  │  │  ├─ DlqService.php
│  │  │  │  │  ├─ QueueMetrics.php
│  │  │  │  │  └─ QueueService.php
│  │  │  │  ├─ ReadModels
│  │  │  │  │  ├─ Projections
│  │  │  │  │  │  ├─ SecurityProjection.php
│  │  │  │  │  │  └─ TaskProjection.php
│  │  │  │  │  └─ TaskReadModel.php
│  │  │  │  ├─ Security
│  │  │  │  │  ├─ AttackProtection
│  │  │  │  │  │  ├─ RateLimiter.php
│  │  │  │  │  │  ├─ SQLInjectionProtection.php
│  │  │  │  │  │  └─ XSSProtection.php
│  │  │  │  │  ├─ Audit
│  │  │  │  │  │  ├─ SecurityAuditService.php
│  │  │  │  │  │  └─ SensitiveDataLogger.php
│  │  │  │  │  ├─ Certificates
│  │  │  │  │  │  ├─ AdapterCertificate.php
│  │  │  │  │  │  ├─ CertificateManager.php
│  │  │  │  │  │  └─ CertificateValidator.php
│  │  │  │  │  ├─ Encryption
│  │  │  │  │  │  ├─ AsymmetricEncryption.php
│  │  │  │  │  │  ├─ DataEncryption.php
│  │  │  │  │  │  ├─ EncryptionEnforcer.php
│  │  │  │  │  │  ├─ HybridEncryption.php
│  │  │  │  │  │  └─ SymmetricEncryption.php
│  │  │  │  │  ├─ Identity
│  │  │  │  │  │  ├─ JwtIssuer.php
│  │  │  │  │  │  ├─ JwtVerifier.php
│  │  │  │  │  │  └─ TwoFactorAuth.php
│  │  │  │  │  ├─ Keys
│  │  │  │  │  │  ├─ KeyManagement.php
│  │  │  │  │  │  ├─ KeyRotationEngine.php
│  │  │  │  │  │  ├─ KeyStore.php
│  │  │  │  │  │  ├─ KeyVersionManager.php
│  │  │  │  │  │  └─ SessionKeyExchangeEngine.php
│  │  │  │  │  ├─ Nonce
│  │  │  │  │  │  ├─ _internal
│  │  │  │  │  │  │  ├─ AtomicNonceStore.php
│  │  │  │  │  │  │  ├─ NonceCleanupRunner.php
│  │  │  │  │  │  │  └─ NonceLockKey.php
│  │  │  │  │  │  └─ NonceValidator.php
│  │  │  │  │  ├─ Signatures
│  │  │  │  │  │  ├─ SignatureCanonicalizer.php
│  │  │  │  │  │  ├─ SignatureVerifier.php
│  │  │  │  │  │  └─ StringToSignBuilder.php
│  │  │  │  │  ├─ Vault
│  │  │  │  │  │  ├─ PolicyManager.php
│  │  │  │  │  │  ├─ SecretInjector.php
│  │  │  │  │  │  └─ VaultClient.php
│  │  │  │  │  └─ README.md
│  │  │  │  ├─ Ws
│  │  │  │  │  ├─ WsEmitter.php
│  │  │  │  │  └─ WsServer.php
│  │  │  │  └─ README.md
│  │  │  └─ README.md
│  │  ├─ tests
│  │  │  ├─ Feature
│  │  │  │  └─ .gitkeep
│  │  │  └─ Unit
│  │  │     ├─ Architecture
│  │  │     │  ├─ BoundariesTest.php
│  │  │     │  ├─ ContractParityTest.php
│  │  │     │  ├─ IncludeFieldsLimitsTest.php
│  │  │     │  ├─ NonceInternalSealedTest.php
│  │  │     │  └─ SingleNonceRepositoryImplementationTest.php
│  │  │     ├─ Domain
│  │  │     │  └─ .gitkeep
│  │  │     ├─ Integrations
│  │  │     │  └─ .gitkeep
│  │  │     ├─ Pipeline
│  │  │     │  └─ .gitkeep
│  │  │     └─ Security
│  │  │        └─ .gitkeep
│  │  └─ README.md
│  └─ frontend
│     ├─ src
│     │  ├─ app
│     │  │  └─ .gitkeep
│     │  ├─ entities
│     │  │  └─ .gitkeep
│     │  ├─ features
│     │  │  └─ .gitkeep
│     │  ├─ pages
│     │  │  └─ .gitkeep
│     │  ├─ shared
│     │  │  ├─ access
│     │  │  │  └─ .gitkeep
│     │  │  ├─ api
│     │  │  │  ├─ generated
│     │  │  │  │  ├─ openapi-parity.test.ts
│     │  │  │  │  └─ README.md
│     │  │  │  ├─ client.ts
│     │  │  │  ├─ endpoints.ts
│     │  │  │  ├─ errors.ts
│     │  │  │  └─ includeFields.ts
│     │  │  ├─ auth
│     │  │  │  └─ .gitkeep
│     │  │  ├─ runtime
│     │  │  │  └─ security
│     │  │  │     ├─ canonicalizer.ts
│     │  │  │     ├─ encryption.ts
│     │  │  │     ├─ index.ts
│     │  │  │     ├─ keyExchange.ts
│     │  │  │     ├─ keyStore.ts
│     │  │  │     ├─ nonce.ts
│     │  │  │     └─ signature.ts
│     │  │  ├─ security
│     │  │  │  └─ .gitkeep
│     │  │  ├─ trace
│     │  │  │  └─ .gitkeep
│     │  │  ├─ ui
│     │  │  │  └─ .gitkeep
│     │  │  └─ ws
│     │  │     └─ .gitkeep
│     │  └─ tests
│     │     └─ .gitkeep
│     └─ README.md
├─ automation
│  └─ .gitkeep
├─ config
│  └─ .gitkeep
├─ dev
│  └─ .gitkeep
├─ docs
│  └─ README.md
├─ governance
│  └─ .gitkeep
├─ platform
│  └─ .gitkeep
├─ scripts
│  └─ .gitkeep
├─ security
│  └─ README.md
├─ shared
│  ├─ canonicalization
│  │  └─ .gitkeep
│  ├─ contracts
│  │  ├─ implementations
│  │  │  ├─ php
│  │  │  │  ├─ AssetRef.php
│  │  │  │  ├─ CapabilitySet.php
│  │  │  │  ├─ Error.php
│  │  │  │  ├─ ErrorKind.php
│  │  │  │  ├─ IntegrationHealth.php
│  │  │  │  ├─ Status.php
│  │  │  │  └─ TraceContext.php
│  │  │  └─ typescript
│  │  │     ├─ assetRef.ts
│  │  │     ├─ capabilitySet.ts
│  │  │     ├─ errorKind.ts
│  │  │     ├─ status.ts
│  │  │     └─ traceContext.ts
│  │  ├─ primitives
│  │  │  ├─ AssetRef.md
│  │  │  ├─ CapabilitySet.md
│  │  │  ├─ ErrorKind.md
│  │  │  ├─ Status.md
│  │  │  └─ TraceContext.md
│  │  ├─ README.md
│  │  └─ vectors
│  │     ├─ encryption-vectors.json
│  │     ├─ nonce-vectors.json
│  │     └─ signature-vectors.json
│  └─ crypto
│     └─ .gitkeep
├─ tests
│  ├─ e2e
│  │  └─ .gitkeep
│  ├─ integration
│  │  └─ .gitkeep
│  └─ security
│     └─ .gitkeep
├─ tools
│  └─ .gitkeep
├─ .editorconfig
├─ .env.example
├─ .gitattributes
├─ .gitignore
├─ AGENT.md
├─ commitlint.config.js
├─ composer.json
├─ docker-compose.yml
├─ ENCRYPTION-SCHEME.md
├─ eslint.config.js
├─ HIERARCHY-GUIDE.md
├─ Makefile
├─ package.json
├─ phpcs.xml
├─ phpmd.xml
├─ phpstan.neon
├─ psalm.xml
├─ README.md
├─ SECURITY-IMPLEMENTATION.md
├─ security-rules.xml
├─ STRUCTURE.txt
└─ stylelint.config.js
```
---

## 1. WHAT CABINET IS

Cabinet is an **internal orchestration and control system**.

It is **NOT**:
- a public SaaS
- a self-service platform
- a business-logic engine
- a place where domain intelligence lives

Cabinet exists to:
- securely accept commands
- validate and authorize them
- orchestrate execution through pipelines
- route data between external systems
- enforce hierarchy, security, and invariants
- observe, audit, and control execution

Cabinet **does not care** what exact business logic is executed.
It only ensures that execution is **safe, ordered, authorized, observable, and resilient**.

---

## 2. USER & ACCESS MODEL

### 2.1 Registration

- Registration does **not** create an active account.
- Registration creates a **request for access**.
- All access requests require **manual approval** by a Super Admin.

### 2.2 Roles

Roles are hierarchical and immutable unless changed by Super Admin:

- User
- Admin
- Super Admin

Rules:
- Admins **cannot** promote users to Admin or Super Admin.
- Admins **can only invite** users.
- Super Admin can promote, demote, or revoke any user.
- Super Admin is **not special in code**, only in permissions and visibility.

### 2.3 Interface Principle

- There is **ONE interface**.
- UI is designed **once**, for Super Admin.
- Lower roles see a **strictly reduced projection** of the same interface.
- Nothing is duplicated per role.
- Visibility is reduced by **permission filtering**, not separate screens.

---

## 3. CORE PHILOSOPHY

### 3.1 Frozen Core

Cabinet’s core orchestration logic is **frozen**.

This means:
- Pipelines
- Security model
- Execution flow
- Retry logic
- Idempotency
- Locking

must remain stable.

### 3.2 Extend via Integrations

All extensibility happens via **integrations**:
- Services
- Adapters
- External systems
- Internal tools

Cabinet connects to them through **ports**.
Cabinet does not embed their logic.

---

## 4. INTEGRATIONS & FALLBACKS

### 4.1 Integration Design

Each integration follows the pattern:
- Port (Application layer)
- Integration (Infrastructure layer)
- Real adapter
- Fallback (fake) adapter

### 4.2 Fallback Philosophy

Fallbacks are **not mocks**.
Fallbacks are **minimal functional implementations** that:
- preserve pipeline continuity
- prevent total failure
- allow degraded operation

If an external service:
- is unavailable
- returns errors
- violates expectations

Cabinet must **not break**.
The fallback must activate automatically.

---

## 5. PIPELINE EXECUTION MODEL

Cabinet executes work via a **stage-based pipeline**.

Pipeline characteristics:
- Deterministic
- Idempotent
- Lock-protected
- Retry-aware
- Event-emitting
- Worker-driven

Stages are explicit.
Transitions are governed by rules.
Workers execute stages asynchronously.

Cabinet orchestrates — it does not “think”.

---

## 6. SECURITY MODEL

Security is **mandatory, layered, and non-optional**.

### 6.1 Security Pipeline

Every request passes through the security pipeline:

1. Authentication
2. Nonce validation
3. Signature verification
4. Payload encryption validation
5. Scope validation
6. Hierarchy validation
7. Rate limiting

Failure at any step **terminates the request immediately**.

### 6.2 Nonce & Idempotency

- Nonces prevent replay attacks.
- Idempotency keys prevent duplicate execution.
- Storage must enforce single-use semantics.

### 6.3 Cryptography

Cabinet enforces:
- Encrypted payloads
- Signed requests
- Explicit key exchange
- Rotation and versioning

Keys are managed, rotated, and audited.

---

## 7. OBSERVABILITY

Cabinet is observable by design.

This includes:
- Structured logging
- Security auditing
- Metrics
- Tracing
- Health checks

Silent failure is forbidden.

---

## 8. FRONTEND RULES

- Desktop-only by design.
- No responsive or mobile layouts.
- Frontend reflects backend permissions.
- Frontend never bypasses backend security.

Generated API clients must:
- match shared contracts
- be validated by parity tests
- never be manually edited

---

## 9. CONTRACTS

The term **“Contracts”** is **reserved exclusively** for:

/shared/contracts

yaml
Копировать код

Rules:
- Contracts define cross-language primitives.
- Contracts are the source of truth.
- No other code may redefine them.
- Generated implementations must match exactly.

---

## 10. STRUCTURE ENFORCEMENT (CRITICAL)

The project structure is **authoritative**.

### ABSOLUTE RULES:

- Do NOT rename directories.
- Do NOT move files between layers.
- Do NOT invent new architectural layers.
- Do NOT collapse folders.
- Do NOT mix responsibilities.

All code must live in its **designated layer**:
- Domain
- Application
- Infrastructure
- Http
- Frontend
- Shared

---

## 11. STRUCTURE SNAPSHOT (MANDATORY)

The structure below **must be followed 1:1**.

The agent must:
- analyze it before acting
- generate code only inside valid locations
- refuse to act if a request violates it

<< INSERT THE FULL PROJECT STRUCTURE HERE EXACTLY AS IN STRUCTURE.txt >>

yaml
Копировать код

---

## 12. AGENT BEHAVIOR RULES

The agent must:
- be deterministic
- be conservative
- avoid assumptions
- stop instead of guessing
- respect boundaries

The agent must NOT:
- redesign the system
- “improve” architecture
- simplify layers
- merge concepts

---

## FINAL STATEMENT

Cabinet is a **control plane**, not a playground.

Any agent operating here must behave as a **strict internal system component**.

If something is unclear — **stop and ask**.
If something violates this document — **do not proceed**.
