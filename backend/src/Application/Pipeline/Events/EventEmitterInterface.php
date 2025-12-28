<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Events;

interface EventEmitterInterface
{
    public function emit(PipelineEvent $event): void;
}
