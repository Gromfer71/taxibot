<?php

namespace App\Services;

use App\Models\Config;
use Dadata\DadataClient;
use Illuminate\Support\Arr;

class DadataAddress
{
    public static function getAddressByCoords($lat, $lon)
    {
       // $dadata = new DadataClient(config('dadata.token'), config('dadata.secret'));
       // $addresses = $dadata->geolocate('address', $lat, $lon, Config::where('name', 'addresses_search_radius')->first()->value ?? 100, 1);

        $address =  (new OrderApiService())->getNearestAddress($lat, $lon, Config::where('name', 'addresses_search_radius')->first()->value ?? 100);
        $firstAddress = $address;
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
            'address' => Address::toString($address),
            'city' => Arr::get($firstAddress, 'data.city'),
            'lat' => $lat,
            'lon' => $lon,
        ];
    }
}