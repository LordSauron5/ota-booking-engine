<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class RoomService
{
    public function getProperty(): array
    {
        return $this->getData()['property'];
    }

    public function getRooms(): array
    {
        return $this->getData()['units'];
    }

    private function getData(): array
    {
        return Cache::remember('property_data', 3600, function () {
            $json = File::get(base_path('property.json'));

            return json_decode($json, true);
        });
    }
}
