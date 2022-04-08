<?php

namespace App\Services;

use App\Models\Config;
use Dadata\DadataClient;
use Illuminate\Support\Arr;

class DadataAddress
{
    public static function getAddressByCoords($lat, $lon)
    {
        $dadata = new DadataClient(config('dadata.token'), config('dadata.secret'));
        $addresses = $dadata->geolocate('address', $lat, $lon, Config::where('name', 'addresses_search_radius')->first()->value ?? 100, 1);
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