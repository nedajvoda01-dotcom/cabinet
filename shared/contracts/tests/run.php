<?php

declare(strict_types=1);

require __DIR__ . '/../../../vendor/autoload.php';
require __DIR__ . '/ParityTest.php';

$tests = [
    new ParityTest(),
];

$failures = 0;
foreach ($tests as $test) {
    try {
        $methods = $test->run();
        echo sprintf("[PASS] %s (%d tests)\n", get_class($test), count($methods));
    } catch (Throwable $throwable) {
        $failures++;
        echo sprintf("[FAIL] %s: %s\n", get_class($test), $throwable->getMessage());
    }
}

if ($failures > 0) {
    exit(1);
}
