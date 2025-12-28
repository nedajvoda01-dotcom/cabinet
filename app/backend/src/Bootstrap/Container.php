<?php

declare(strict_types=1);

namespace Cabinet\Backend\Bootstrap;

use Cabinet\Backend\Http\Controllers\HealthController;
use Cabinet\Backend\Http\Controllers\ReadinessController;
use Cabinet\Backend\Http\Controllers\SecurityController;
use Cabinet\Backend\Http\Controllers\VersionController;
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

    public function __construct(Config $config, Clock $clock)
    {
        $this->config = $config;
        $this->clock = $clock;
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
