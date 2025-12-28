<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Events;

final class NullEventEmitter implements EventEmitterInterface
{
    public function emit(PipelineEvent $event): void
    {
        // No-op placeholder for future observability hooks.
    }
}
