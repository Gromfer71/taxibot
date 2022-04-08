<?php

namespace App\Services;

use Dadata\DadataClient;
use Illuminate\Support\Arr;

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
        $city = Arr::get($firstAddress, 'data.city') ?: Arr::get($firstAddress, 'data.settlement');

        return [
            'address' => Arr::get($firstAddress, 'data.street') . ' ' . Arr::get($firstAddress, 'data.house') . ($city ? (' (' . $city . ')') : ''),
            'city' => Arr::get($firstAddress, 'data.city'),
            'lat' => $lat,
            'lon' => $lon,
        ];
    }
}