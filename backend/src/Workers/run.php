<?php
// backend/src/Workers/run.php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

$config = require __DIR__ . '/../Config/config.php';
$container = new \Backend\Server\Container($config);

$type = $argv[1] ?? null;
if (!$type) {
    fwrite(STDERR, "Usage: php backend/src/Workers/run.php <parser|photos|export|publish|robot_status>\n");
    exit(1);
}

$sleepMs = $config['workers']['sleep_ms'] ?? 300;

$daemon = new \Backend\Workers\WorkerDaemon($container, $type, $sleepMs);
$daemon->run();
