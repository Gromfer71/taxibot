<?php

namespace App\Services;

use Dadata\DadataClient;
use Illuminate\Support\Facades\Log;

class DadataAddress
{
    public static function getAddressByCoords($lat, $lon)
    {
        $dadata = new DadataClient(config('dadata.token'), config('dadata.secret'));
        $addresses = $dadata->geolocate('address', $lat, $lon, 100, 1);
        return collect($addresses)->pluck('value')->toJson(JSON_UNESCAPED_UNICODE);
    }
}