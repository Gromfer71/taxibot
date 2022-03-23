<?php

namespace App\Services;

use Dadata\DadataClient;

class DadataAddress
{
    public static function getAddressByCoords($lat, $lon)
    {
        $dadata = new DadataClient(config('dadata.token'), config('dadata.secret'));
        $addresses = $dadata->geolocate('address', $lat, $lon, 100, 1);

        //return collect($addresses)->pluck('value', 'data.city');
        return collect($addresses)->transform(function ($item) use ($lat, $lon) {
            return [
                'address' => $item['value'],
                'city' => $item['data']['city'],
                'lat' => $lat,
                'lon' => $lon,
            ];
        })->first();
    }
}