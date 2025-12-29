<?php

declare(strict_types=1);

use Cabinet\Backend\Bootstrap\AppKernel;
use Cabinet\Backend\Bootstrap\Clock;
use Cabinet\Backend\Bootstrap\Config;
use Cabinet\Backend\Bootstrap\Container;
use Cabinet\Backend\Http\Request;

require __DIR__ . '/../../../vendor/autoload.php';

$config = Config::fromEnvironment();
$clock = new Clock();
$container = new Container($config, $clock);
$kernel = new AppKernel($container);

$request = Request::fromGlobals();
$response = $kernel->handle($request);
$response->send();
