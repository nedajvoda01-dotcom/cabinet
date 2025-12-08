<?php
namespace Modules\Parser;

class ParserController
{
    private ParserService $service;

    public function __construct(ParserService $service)
    {
        $this->service = $service;
    }

    // TODO: add controller actions
}
