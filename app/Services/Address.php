<?php

namespace App\Services;

use App\Models\AddressHistory;
use App\Models\LogApi;
use BotMan\BotMan\Storages\Storage;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Str;
use Throwable;
use Tightenco\Collect\Support\Collection;

class Address
{

    public const MAX_ADDRESS_LENGTH = 120;
    public const MAX_ADDRESSES_COUNT = 25;
    public const MAX_ADDRESSES_FOR_BUTTONS = 24;

    /**
     * @param string $query Адрес, который ввел пользователь
     * @param          $cities
     * @param Storage $storage
     *
     * @return array|\Illuminate\Support\Collection|Collection
     * @throws Throwable
     */
    public static function getAddresses(string $query, $cities, Storage $storage)
    {
        $endpoint = 'https://sk-taxi.ru/tmapi/api.php?method=%2Fcommon_api%2F1.0%2Fget_addresses_like2';
        $client = new Client();
        $promises = collect();

        $cities = self::getCitiesWithOneDistrict($cities, $storage->get('city'), $storage->get('district'));


        foreach ($cities as $city) {
            $paramJson = json_encode(
                [
                    'address' => $query,
                    'get_streets' => true,
                    'get_points' => true,
                    'get_houses' => true,
                    'city' => $city->name,
                ]
            );
            $request = new Request(
                'POST',
                $endpoint,
                ['Content-Type' => 'application/json'],
                $paramJson
            );
            self::_log($endpoint, collect($query)->toJson() . ' в городе ' . $city->name, null);
            $promises->push($client->sendAsync($request));
        }

        $results = Utils::unwrap($promises->toArray());
        Utils::settle($promises)->wait();
        $addresses = collect();


        foreach ($results as $result) {
            $body = json_decode($result->getBody()->getContents(), true);
            if ($body) {
                if (isset($body['code'])) {
                    if ($body['code'] == 0) {
                        $addresses = $addresses->merge($body['data']['addresses']);
                    }
                }
            }
        }

        self::_log($endpoint, $cities->toJson(JSON_UNESCAPED_UNICODE), json_encode($addresses->take(25), JSON_UNESCAPED_UNICODE));

        $result = self::sortAddresses($addresses, $storage);
        return $result->values() ?? [];
    }


    public static function getCitiesWithOneDistrict($cities, $cityName, $district)
    {
        $cities = collect($cities);
        return $cities->filter(function ($city) use ($district, $cityName) {
            if ($city->name == $cityName) {
                return $city;
            }

            if ($district != '' && Str::upper($city->district) == Str::upper($district)) {
                return $city;
            }
        });
    }

    public static function sortAddresses($addresses, $storage)
    {
        return collect($addresses)->sort(function ($a, $b) use ($storage) {
            $city = $storage->get('city');
            if ($a['city'] == $city && $b['city'] != $city) {
                return -1;
            }
            if ($a['city'] != $city && $b['city'] == $city) {
                return 1;
            }
            $kinds = ['house' => 99, 'street' => 50, 'point' => 1];
            $aKind = $kinds[$a['kind']];
            $bKind = $kinds[$b['kind']];
            if ($aKind > $bKind) {
                return -1;
            }
            if ($aKind < $bKind) {
                return 1;
            }
            if ($aKind == $bKind && $b['kind'] == 'house') {
                return strcasecmp($a['house'], $b['house']);
            }

            return 0;
        });
    }

    public static function getCitiesWithOneCrewId($cities, $crewId)
    {
        $cities = collect($cities);
        return $cities->filter(function ($city) use ($crewId) {
            if ($city->crewGroupId == $crewId) {
                return $city;
            }
        });
    }

    public static function haveEndAddressFromStorageAndAllAdressesIsReal(Storage $userStorage)
    {
        if (count((array)$userStorage->get('lat')) > 1) {
            $address = (array)$userStorage->get('lat');
            foreach ($address as $item) {
                if ($item == 0) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public static function haveFirstAddressFromStorageAndFirstAddressesIsReal(Storage $userStorage)
    {
        if (count((array)$userStorage->get('lat')) > 0) {
            $address = collect((array)$userStorage->get('lat'))->first();

            if ($address == 0) {
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    public static function findByAnswer($addressesList, $answer)
    {
        return collect($addressesList)->filter(function ($item) use ($answer) {
            if (Address::toString($item) === self::removeEllipsisFromAddressIfExists($answer->getText())) {
                return $item;
            }
//            if (stripos(
//                    Address::toString($item),
//                    self::removeEllipsisFromAddressIfExists($answer->getText())
//                ) !== false) {
//                return $item;
//            }
        })->first();
    }

    public static function toString($address)
    {
        if ($address['kind'] == 'house') {
            return self::subStrAddress($address['street'] . ' ' . $address['house'] . ' (' . $address['city'] . ')');
        }
        if ($address['kind'] == 'point') {
            $street = '';
            if (!empty($address['street'])) {
                $street .= ', ' . $address['street'];
            }
            if (!empty($address['house'])) {
                $street .= ' ' . $address['house'];
            }
            return self::subStrAddress($address['point'] . ' (' . $address['city'] . $street . ')');
        }
        if ($address['kind'] == 'street') {
            return self::subStrAddress($address['street'] . ' (' . $address['city'] . ')');
        }
    }

    public static function subStrAddress($address)
    {
        if (strlen($address) > self::MAX_ADDRESS_LENGTH) {
            return mb_substr($address, 0, self::MAX_ADDRESS_LENGTH);
        } else {
            return $address;
        }
    }

    public static function removeEllipsisFromAddressIfExists($address)
    {
        // $address = utf8_decode($address);
        if (substr($address, -3) === '...') {
            return substr($address, 0, strlen($address) - 3);
        } elseif (mb_strcut($address, -1, null, 'utf-8') == '…') {
            return mb_strcut($address, 0, strlen($address) - 1, 'utf-8');
        } else {
            return $address;
        }
    }

    public static function hasDigit($str)
    {
        $a = str_split($str);
        sort($a);
        if (is_numeric($a[0])) {
            return true;
        } else {
            return false;
        }
    }

    public static function isAddressChangedFromState($oldState, $newState, $userId)
    {
        AddressHistory::createIfNotExistsEverywhere($userId, $oldState->source, $oldState->source_lat, $oldState->source_lon);
        $stops = array_reverse($newState->stops);
        foreach ($newState->stops as $stop) {
            AddressHistory::createIfNotExistsEverywhere($userId, $stop->address, $stop->lat, $stop->lon);
        }
        if ($newState->destination) {
            AddressHistory::createIfNotExistsEverywhere($userId, $newState->destination, $newState->destination_lat, $newState->destination_lon);
        }


        if ($newState->source != $oldState->source || $newState->destination != $oldState->destination) {
            return true;
        }

        if (count($newState->stops)) {
            if (count($newState->stops) != count($oldState->stops)) {
                return true;
            }
            foreach ($newState->stops as $key => $stop) {
                if ($stop != $oldState->stops[$key]) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function updateAddressesInStorage($orderState, Storage $storage)
    {
        $addresses = collect();
        $lat = collect();
        $lon = collect();


        $addresses->push($orderState->source);
        $lat->push($orderState->source_lat);
        $lon->push($orderState->source_lon);

        foreach ($orderState->stops as $stop) {
            $addresses->push($stop->address);
            $lat->push($stop->lat);
            $lon->push($stop->lon);
        }

        if ($orderState->destination) {
            $addresses->push($orderState->destination);
            $lat->push($orderState->destination_lat);
            $lon->push($orderState->destination_lon);
        } else {
            $storage->save(['second_address_will_say_to_driver_flag' => 1]);
        }



        $storage->save(['address' => $addresses->toArray(), 'lat' => $lat->toArray(), 'lon' => $lon->toArray()]);
    }

    private static function _log($url, $params, $result)
    {
        $log = new LogApi();
        $log->params = $params;
        $log->url = $url;

        $log->result = substr($result, 0, 65000);
        $log->save();
    }

}