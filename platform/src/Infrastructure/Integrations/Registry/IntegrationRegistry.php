<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Integrations\Registry;

use Cabinet\Backend\Application\Integrations\ParserIntegration;
use Cabinet\Backend\Application\Integrations\PhotosIntegration;
use Cabinet\Backend\Application\Integrations\PublisherIntegration;
use Cabinet\Backend\Application\Integrations\ExportIntegration;
use Cabinet\Backend\Application\Integrations\CleanupIntegration;

final class IntegrationRegistry
{
    private ParserIntegration $parserIntegration;
    private PhotosIntegration $photosIntegration;
    private PublisherIntegration $publisherIntegration;
    private ExportIntegration $exportIntegration;
    private CleanupIntegration $cleanupIntegration;

    public function __construct(
        ParserIntegration $parserIntegration,
        PhotosIntegration $photosIntegration,
        PublisherIntegration $publisherIntegration,
        ExportIntegration $exportIntegration,
        CleanupIntegration $cleanupIntegration
    ) {
        $this->parserIntegration = $parserIntegration;
        $this->photosIntegration = $photosIntegration;
        $this->publisherIntegration = $publisherIntegration;
        $this->exportIntegration = $exportIntegration;
        $this->cleanupIntegration = $cleanupIntegration;
    }

    public function parser(): ParserIntegration
    {
        return $this->parserIntegration;
    }

    public function photos(): PhotosIntegration
    {
        return $this->photosIntegration;
    }

    public function publisher(): PublisherIntegration
    {
        return $this->publisherIntegration;
    }

    public function export(): ExportIntegration
    {
        return $this->exportIntegration;
    }

    public function cleanup(): CleanupIntegration
    {
        return $this->cleanupIntegration;
    }
}
