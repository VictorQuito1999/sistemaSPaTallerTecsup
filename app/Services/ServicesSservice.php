<?php

use App\Models\Service;

class ServicesSservice
{
    public function getServices()
    {
        $service = Service::all();

        return $service;
    }
}
