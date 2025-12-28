Cabinet — Codex Agent Rules (STRICT)
This repository is Cabinet: an internal orchestration system (secure command gateway + pipeline engine + integrations). The agent must operate under strict architectural and security constraints.

0) ABSOLUTE RULE: STRUCTURE IS LAW
You MUST follow the repository structure exactly. You MUST NOT invent new top-level folders, rename existing folders, or relocate modules unless explicitly instructed.

✅ STRUCTURE REFERENCE (PASTE HERE)
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

Enforcement:

If a requested change does not fit the structure above, STOP and propose the closest compliant location.

If you need a new file, create it only inside the allowed directories.

Empty directories must be tracked with .gitkeep (only where applicable).

What Cabinet is (do not redefine) Cabinet is a frozen orchestrator:
It does not “understand business meaning” of payloads.

It securely transports commands and coordinates pipeline stages.

Domain logic stays in external services/integrations. Cabinet coordinates them.

Allowed actions You MAY:
create/edit files strictly within the approved structure,

add documentation, tests, scripts, config consistent with the repo patterns,

generate minimal stubs for ports/adapters/workers/commands,

keep PHP and TypeScript contract parity (shared/contracts).

Forbidden actions You MUST NOT:
weaken the security protocol (nonce/signature/encryption where required),

add public/self-service signup flows (registration is request → super admin approval),

duplicate UI variants per role (one UI, gated by capabilities),

move responsibilities across boundaries (Domain ↔ Application ↔ Infrastructure).

Non-negotiable invariants 4.1 Security-first Requests follow the security pipeline: Auth → Nonce → Signature → Encryption → Scope → Hierarchy → RateLimit (as required per endpoint).
4.2 Pipeline reliability Idempotency keys for state-changing commands

Locks for concurrency

Retry policy + classification

DLQ for terminal failures

4.3 Integrations Each integration must have:

Port in Application/Integrations/*Port.php

Real adapter in Infrastructure/Integrations/*/Real

Fallback adapter/scenario in Infrastructure/Integrations/*/Fallback

Fallback exists to avoid pipeline breakage when external services fail.

4.4 One UI UI is one. Features are hidden/disabled by role/scope/hierarchy/capabilities. UI gating is not security — backend enforces permissions.

Decision process If uncertain:
Find an existing similar pattern in the repo.

Follow naming conventions and folder boundaries.

Ask only if it is critical (e.g., endpoint security requirements).

yaml

Updated rule reminder (so you don’t have to repeat it)
All further .md content I write for you will be English and Codex-ready (explicit, enforceable, not poetic).
If you want, next I can:

Convert your existing HIERARCHY-GUIDE.md, ENCRYPTION-SCHEME.md, SECURITY-IMPLEMENTATION.md into a more Codex-enforceable style (with “MUST/SHOULD/MUST NOT”, checklists, and implementation hooks), OR
Start filling the remaining .md files you have (you listed several at the repo root earlier).
Say which file you want next and I’ll output the full contents in English.
