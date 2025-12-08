<?php
// backend/src/Server/App.php

namespace Backend\Server;

use Backend\Middlewares\CorsMiddleware;
use Backend\Middlewares\AuthMiddleware;
use Backend\Middlewares\AdminMiddleware;

final class App
{
    public function __construct(
        private Container $c,
        private $router // твой Router instance
    ) {}

    public function bootstrap(): void
    {
        // global middlewares (если роутер поддерживает)
        if (method_exists($this->router, 'middleware')) {
            $this->router->middleware([
                CorsMiddleware::class,
            ]);
        }

        // routes
        $routesFile = __DIR__ . '/../Routes/routes.php';
        $registerRoutes = require $routesFile;
        $registerRoutes($this->router);

        // bind middleware instances if router supports container
        if (method_exists($this->router, 'setContainer')) {
            $this->router->setContainer($this->c);
        }
    }

    public function handle(): void
    {
        $this->router->dispatch(); // adapt if your router uses another method
    }
}
