<?php

declare(strict_types=1);

namespace Cabinet\Contracts;

enum PipelineStage: string
{
    case PARSE = 'parse';
    case PHOTOS = 'photos';
    case PUBLISH = 'publish';
    case EXPORT = 'export';
    case CLEANUP = 'cleanup';
}
