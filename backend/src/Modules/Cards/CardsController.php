<?php
namespace Modules\Cards;

class CardsController
{
    private CardsService $service;

    public function __construct(CardsService $service)
    {
        $this->service = $service;
    }

    // TODO: add controller actions
}
