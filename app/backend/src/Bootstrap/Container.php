<?php

declare(strict_types=1);

namespace Cabinet\Backend\Bootstrap;

use Cabinet\Backend\Http\Controllers\HealthController;
use Cabinet\Backend\Http\Controllers\ReadinessController;
use Cabinet\Backend\Http\Controllers\SecurityController;
use Cabinet\Backend\Http\Controllers\VersionController;
use Cabinet\Backend\Http\Controllers\AccessController;
use Cabinet\Backend\Http\Controllers\TasksController;
use Cabinet\Backend\Http\Controllers\AdminController;
use Cabinet\Backend\Http\Kernel\HttpKernel;
use Cabinet\Backend\Http\Routing\Router;
use Cabinet\Backend\Http\Security\Pipeline\AuthStep;
use Cabinet\Backend\Http\Security\Pipeline\EncryptionStep;
use Cabinet\Backend\Http\Security\Pipeline\HierarchyStep;
use Cabinet\Backend\Http\Security\Pipeline\NonceStep;
use Cabinet\Backend\Http\Security\Pipeline\RateLimitStep;
use Cabinet\Backend\Http\Security\Pipeline\ScopeStep;
use Cabinet\Backend\Http\Security\Pipeline\SecurityPipelineMiddleware;
use Cabinet\Backend\Http\Security\Pipeline\SignatureStep;
use Cabinet\Backend\Http\Security\Requirements\EndpointRequirementsResolver;
use Cabinet\Backend\Http\Security\Requirements\RouteRequirementsMap;
use Cabinet\Backend\Http\Validation\Protocol\NonceFormatValidator;
use Cabinet\Backend\Infrastructure\Observability\Logging\StructuredLogger;
use Cabinet\Backend\Infrastructure\Security\AttackProtection\RateLimiter;
use Cabinet\Backend\Infrastructure\Security\Encryption\SymmetricEncryption;
use Cabinet\Backend\Infrastructure\Security\Identity\InMemoryActorRegistry;
use Cabinet\Backend\Infrastructure\Security\Nonce\InMemoryNonceRepository;
use Cabinet\Backend\Infrastructure\Security\Nonce\NonceRepository;
use Cabinet\Backend\Infrastructure\Security\Signatures\SignatureCanonicalizer;
use Cabinet\Backend\Infrastructure\Security\Signatures\SignatureVerifier;
use Cabinet\Backend\Infrastructure\Security\Signatures\StringToSignBuilder;
use Cabinet\Backend\Application\Bus\CommandBus;
use Cabinet\Backend\Application\Handlers\RequestAccessHandler;
use Cabinet\Backend\Application\Handlers\ApproveAccessHandler;
use Cabinet\Backend\Application\Handlers\CreateTaskHandler;
use Cabinet\Backend\Application\Handlers\AdvancePipelineHandler;
use Cabinet\Backend\Application\Handlers\RetryJobHandler;
use Cabinet\Backend\Application\Commands\Access\RequestAccessCommand;
use Cabinet\Backend\Application\Commands\Access\ApproveAccessCommand;
use Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand;
use Cabinet\Backend\Application\Commands\Pipeline\AdvancePipelineCommand;
use Cabinet\Backend\Application\Commands\Pipeline\TickTaskCommand;
use Cabinet\Backend\Application\Commands\Admin\RetryJobCommand;
use Cabinet\Backend\Application\Ports\UserRepository;
use Cabinet\Backend\Application\Ports\AccessRequestRepository;
use Cabinet\Backend\Application\Ports\TaskRepository;
use Cabinet\Backend\Application\Ports\PipelineStateRepository;
use Cabinet\Backend\Application\Ports\TaskOutputRepository;
use Cabinet\Backend\Application\Ports\UnitOfWork;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryUserRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryAccessRequestRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryTaskRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryPipelineStateRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\NoOpUnitOfWork;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\UuidIdGenerator;
use Cabinet\Backend\Infrastructure\Persistence\PDO\ConnectionFactory;
use Cabinet\Backend\Infrastructure\Persistence\PDO\MigrationsRunner;
use Cabinet\Backend\Infrastructure\Persistence\PDO\PDOUnitOfWork;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\UsersRepository;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\AccessRequestsRepository;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\TasksRepository;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\PipelineStatesRepository;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\TaskOutputsRepository;
use Cabinet\Backend\Infrastructure\Integrations\Registry\IntegrationRegistry;
use Cabinet\Backend\Infrastructure\Integrations\Fallback\DemoParserAdapter;
use Cabinet\Backend\Infrastructure\Integrations\Fallback\DemoPhotosAdapter;
use Cabinet\Backend\Infrastructure\Integrations\Fallback\DemoPublisherAdapter;
use Cabinet\Backend\Infrastructure\Integrations\Fallback\DemoExportAdapter;
use Cabinet\Backend\Infrastructure\Integrations\Fallback\DemoCleanupAdapter;
use Cabinet\Backend\Application\Handlers\TickTaskHandler;
use Cabinet\Backend\Application\Queries\GetTaskOutputsQuery;
use PDO;

final class Container
{
    private Config $config;

    private Clock $clock;

    private ?StructuredLogger $logger = null;

    private ?Router $router = null;

    private ?HttpKernel $httpKernel = null;

    private ?RouteRequirementsMap $requirementsMap = null;

    private ?EndpointRequirementsResolver $requirementsResolver = null;

    private ?SecurityPipelineMiddleware $securityPipeline = null;

    private ?NonceRepository $nonceRepository = null;

    private ?InMemoryActorRegistry $actorRegistry = null;

    private ?RateLimiter $rateLimiter = null;

    private ?CommandBus $commandBus = null;

    private ?UserRepository $userRepository = null;

    private ?AccessRequestRepository $accessRequestRepository = null;

    private ?TaskRepository $taskRepository = null;

    private ?PipelineStateRepository $pipelineStateRepository = null;

    private ?TaskOutputRepository $taskOutputRepository = null;

    private ?IntegrationRegistry $integrationRegistry = null;

    private ?UnitOfWork $unitOfWork = null;

    private ?GetTaskOutputsQuery $getTaskOutputsQuery = null;

    private ?\Cabinet\Backend\Application\Queries\ListTasksQuery $listTasksQuery = null;

    private ?\Cabinet\Backend\Application\Queries\GetTaskDetailsQuery $getTaskDetailsQuery = null;

    private ?UuidIdGenerator $idGenerator = null;

    private ?PDO $pdo = null;

    private bool $migrationsRun = false;

    public function __construct(Config $config, Clock $clock)
    {
        $this->config = $config;
        $this->clock = $clock;
        $this->runMigrations();
    }

    private function runMigrations(): void
    {
        if ($this->migrationsRun) {
            return;
        }

        $runMigrations = getenv('RUN_MIGRATIONS');
        $appEnv = $this->config->environment();
        
        // Run migrations in dev/test OR if explicitly enabled via RUN_MIGRATIONS=1
        if ($appEnv !== 'prod' || $runMigrations === '1') {
            $useSqlite = getenv('USE_SQLITE') !== 'false';
            
            if ($useSqlite) {
                $pdo = $this->pdo();
                $runner = new MigrationsRunner($pdo);
                $runner->run();
            }
        }

        $this->migrationsRun = true;
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function clock(): Clock
    {
        return $this->clock;
    }

    public function logger(): StructuredLogger
    {
        if ($this->logger === null) {
            $this->logger = new StructuredLogger($this->clock);
        }

        return $this->logger;
    }

    public function requirementsMap(): RouteRequirementsMap
    {
        if ($this->requirementsMap === null) {
            $this->requirementsMap = new RouteRequirementsMap();
        }

        return $this->requirementsMap;
    }

    public function endpointRequirementsResolver(): EndpointRequirementsResolver
    {
        if ($this->requirementsResolver === null) {
            $this->requirementsResolver = new EndpointRequirementsResolver($this->requirementsMap());
        }

        return $this->requirementsResolver;
    }

    public function nonceRepository(): NonceRepository
    {
        if ($this->nonceRepository === null) {
            $this->nonceRepository = new InMemoryNonceRepository();
        }

        return $this->nonceRepository;
    }

    public function actorRegistry(): InMemoryActorRegistry
    {
        if ($this->actorRegistry === null) {
            $this->actorRegistry = new InMemoryActorRegistry();
        }

        return $this->actorRegistry;
    }

    public function rateLimiter(): RateLimiter
    {
        if ($this->rateLimiter === null) {
            $this->rateLimiter = new RateLimiter();
        }

        return $this->rateLimiter;
    }

    public function idGenerator(): UuidIdGenerator
    {
        if ($this->idGenerator === null) {
            $this->idGenerator = new UuidIdGenerator();
        }

        return $this->idGenerator;
    }

    private function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = ConnectionFactory::create();
        }

        return $this->pdo;
    }

    public function userRepository(): UserRepository
    {
        if ($this->userRepository === null) {
            $useSqlite = getenv('USE_SQLITE') !== 'false';
            
            if ($useSqlite) {
                $this->userRepository = new UsersRepository($this->pdo());
            } else {
                $this->userRepository = new InMemoryUserRepository();
            }
        }

        return $this->userRepository;
    }

    public function accessRequestRepository(): AccessRequestRepository
    {
        if ($this->accessRequestRepository === null) {
            $useSqlite = getenv('USE_SQLITE') !== 'false';
            
            if ($useSqlite) {
                $this->accessRequestRepository = new AccessRequestsRepository($this->pdo());
            } else {
                $this->accessRequestRepository = new InMemoryAccessRequestRepository();
            }
        }

        return $this->accessRequestRepository;
    }

    public function taskRepository(): TaskRepository
    {
        if ($this->taskRepository === null) {
            $useSqlite = getenv('USE_SQLITE') !== 'false';
            
            if ($useSqlite) {
                $this->taskRepository = new TasksRepository($this->pdo());
            } else {
                $this->taskRepository = new InMemoryTaskRepository();
            }
        }

        return $this->taskRepository;
    }

    public function pipelineStateRepository(): PipelineStateRepository
    {
        if ($this->pipelineStateRepository === null) {
            $useSqlite = getenv('USE_SQLITE') !== 'false';
            
            if ($useSqlite) {
                $this->pipelineStateRepository = new PipelineStatesRepository($this->pdo());
            } else {
                $this->pipelineStateRepository = new InMemoryPipelineStateRepository();
            }
        }

        return $this->pipelineStateRepository;
    }

    public function taskOutputRepository(): TaskOutputRepository
    {
        if ($this->taskOutputRepository === null) {
            $useSqlite = getenv('USE_SQLITE') !== 'false';
            
            if ($useSqlite) {
                $this->taskOutputRepository = new TaskOutputsRepository($this->pdo());
            } else {
                // For in-memory, we could use a simple array-based implementation
                // For now, always use SQLite for task outputs in dev/test
                $this->taskOutputRepository = new TaskOutputsRepository($this->pdo());
            }
        }

        return $this->taskOutputRepository;
    }

    public function integrationRegistry(): IntegrationRegistry
    {
        if ($this->integrationRegistry === null) {
            // Check config flags - default to false (use fallback)
            $parserEnabled = getenv('INTEGRATION_PARSER_ENABLED') === 'true';
            $photosEnabled = getenv('INTEGRATION_PHOTOS_ENABLED') === 'true';
            $publishEnabled = getenv('INTEGRATION_PUBLISH_ENABLED') === 'true';
            $exportEnabled = getenv('INTEGRATION_EXPORT_ENABLED') === 'true';
            $cleanupEnabled = getenv('INTEGRATION_CLEANUP_ENABLED') === 'true';

            // For now, always use demo/fallback adapters
            $this->integrationRegistry = new IntegrationRegistry(
                new DemoParserAdapter(),
                new DemoPhotosAdapter(),
                new DemoPublisherAdapter(),
                new DemoExportAdapter(),
                new DemoCleanupAdapter()
            );
        }

        return $this->integrationRegistry;
    }

    public function unitOfWork(): UnitOfWork
    {
        if ($this->unitOfWork === null) {
            $useSqlite = getenv('USE_SQLITE') !== 'false';
            
            if ($useSqlite) {
                $this->unitOfWork = new PDOUnitOfWork($this->pdo());
            } else {
                $this->unitOfWork = new NoOpUnitOfWork();
            }
        }

        return $this->unitOfWork;
    }

    public function getTaskOutputsQuery(): GetTaskOutputsQuery
    {
        if ($this->getTaskOutputsQuery === null) {
            $this->getTaskOutputsQuery = new GetTaskOutputsQuery(
                $this->taskOutputRepository()
            );
        }

        return $this->getTaskOutputsQuery;
    }

    public function listTasksQuery(): \Cabinet\Backend\Application\Queries\ListTasksQuery
    {
        if ($this->listTasksQuery === null) {
            $this->listTasksQuery = new \Cabinet\Backend\Application\Queries\ListTasksQuery(
                $this->taskRepository(),
                $this->pipelineStateRepository()
            );
        }

        return $this->listTasksQuery;
    }

    public function getTaskDetailsQuery(): \Cabinet\Backend\Application\Queries\GetTaskDetailsQuery
    {
        if ($this->getTaskDetailsQuery === null) {
            $this->getTaskDetailsQuery = new \Cabinet\Backend\Application\Queries\GetTaskDetailsQuery(
                $this->taskRepository(),
                $this->pipelineStateRepository()
            );
        }

        return $this->getTaskDetailsQuery;
    }

    public function commandBus(): CommandBus
    {
        if ($this->commandBus === null) {
            $bus = new CommandBus();
            
            // Register handlers
            $bus->register(
                RequestAccessCommand::class,
                new RequestAccessHandler($this->accessRequestRepository(), $this->idGenerator())
            );
            
            $bus->register(
                ApproveAccessCommand::class,
                new ApproveAccessHandler(
                    $this->accessRequestRepository(),
                    $this->userRepository(),
                    $this->idGenerator()
                )
            );
            
            $bus->register(
                CreateTaskCommand::class,
                new CreateTaskHandler(
                    $this->taskRepository(),
                    $this->pipelineStateRepository(),
                    $this->idGenerator()
                )
            );
            
            $bus->register(
                AdvancePipelineCommand::class,
                new AdvancePipelineHandler(
                    $this->taskRepository(),
                    $this->pipelineStateRepository()
                )
            );
            
            $bus->register(
                RetryJobCommand::class,
                new RetryJobHandler($this->pipelineStateRepository())
            );
            
            $bus->register(
                TickTaskCommand::class,
                new TickTaskHandler(
                    $this->taskRepository(),
                    $this->pipelineStateRepository(),
                    $this->taskOutputRepository(),
                    $this->integrationRegistry(),
                    $this->unitOfWork()
                )
            );
            
            $this->commandBus = $bus;
        }

        return $this->commandBus;
    }

    public function securityPipeline(): SecurityPipelineMiddleware
    {
        if ($this->securityPipeline === null) {
            $auth = new AuthStep($this->actorRegistry());
            $nonce = new NonceStep($this->nonceRepository(), new NonceFormatValidator());
            $signature = new SignatureStep(new StringToSignBuilder(), new SignatureCanonicalizer(), new SignatureVerifier());
            $encryption = new EncryptionStep(new SymmetricEncryption());
            $scope = new ScopeStep();
            $hierarchy = new HierarchyStep();
            $rateLimit = new RateLimitStep($this->rateLimiter());

            $this->securityPipeline = new SecurityPipelineMiddleware(
                $auth,
                $nonce,
                $signature,
                $encryption,
                $scope,
                $hierarchy,
                $rateLimit,
                $this->logger()
            );
        }

        return $this->securityPipeline;
    }

    public function router(): Router
    {
        if ($this->router === null) {
            $router = new Router();
            $router->get('/health', [new HealthController(), 'health']);
            $router->get('/readiness', [new ReadinessController(), 'readiness']);
            $router->get('/version', [new VersionController($this->config), 'version']);
            $router->post('/security/echo', [new SecurityController(), 'echo']);
            $router->post('/security/encrypted-echo', [new SecurityController(), 'encryptedEcho']);
            $router->post('/security/admin-echo', [new SecurityController(), 'echo']);
            $router->post('/security/missing-requirements', [new SecurityController(), 'echo']);
            
            // Application layer routes
            $accessController = new AccessController($this->commandBus());
            $router->post('/access/request', [$accessController, 'requestAccess']);
            $router->post('/admin/access/approve', [$accessController, 'approveAccess']);
            
            $tasksController = new TasksController(
                $this->commandBus(), 
                $this->getTaskOutputsQuery(),
                $this->listTasksQuery(),
                $this->getTaskDetailsQuery()
            );
            $router->get('/tasks', [$tasksController, 'list']);
            $router->get('/tasks/{id}', [$tasksController, 'details']);
            $router->post('/tasks/create', [$tasksController, 'create']);
            $router->post('/tasks/{id}/tick', [$tasksController, 'tick']);
            $router->get('/tasks/{id}/outputs', [$tasksController, 'outputs']);
            
            $adminController = new AdminController($this->commandBus());
            $router->post('/admin/pipeline/retry', [$adminController, 'retryJob']);

            $this->router = $router;
        }

        return $this->router;
    }

    public function httpKernel(): HttpKernel
    {
        if ($this->httpKernel === null) {
            $this->httpKernel = new HttpKernel(
                $this->router(),
                $this->logger(),
                $this->config,
                $this->securityPipeline(),
                $this->endpointRequirementsResolver()
            );
        }

        return $this->httpKernel;
    }
}
