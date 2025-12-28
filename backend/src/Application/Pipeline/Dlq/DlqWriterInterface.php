<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Dlq;

interface DlqWriterInterface
{
    public function write(DlqRecord $record): void;
}
