<?php

namespace App\Services;

use Dadata\DadataClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class DadataAddress
{
    public static function getAddressByCoords($lat, $lon)
    {
        $dadata = new DadataClient(config('dadata.token'), config('dadata.secret'));
        $addresses = $dadata->geolocate('address', $lat, $lon, 100, 1);
        $firstAddress = collect($addresses)->first();
        if (!$firstAddress) {
            return null;
        }
        if (!Arr::get($firstAddress, 'data.street')) {
            return null;
        }
        if (!Arr::get($firstAddress, 'data.house')) {
            return null;
        }

        return [
            'address' => Arr::get($firstAddress, 'data.street') . ' ' . Arr::get($firstAddress, 'data.house') . (Arr::get($firstAddress, 'data.city') ? (' (' . Arr::get(
                        $firstAddress,
                        'data.city'
                    ) . ')') : ''),
            'city' => Arr::get($firstAddress, 'data.city'),
            'lat' => $lat,
            'lon' => $lon,
        ];
    }
}