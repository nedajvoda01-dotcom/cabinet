<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Dlq;

final class InMemoryDlqWriter implements DlqWriterInterface
{
    /** @var DlqRecord[] */
    public array $records = [];

    public function write(DlqRecord $record): void
    {
        $this->records[] = $record;
    }
}
