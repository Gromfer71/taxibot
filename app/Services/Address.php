<?php

namespace App\Services;

use App\Models\LogApi;
use App\Models\User;
use BotMan\BotMan\Storages\Storage;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Str;

/**
 * Работа с адресами - поиск, фильтр.
 */
class Address
{

    const MAX_ADDRESS_LENGTH = 200;
    /**
     * @param  string  $query  Адрес который ввел пользователь
     * @param          $cities
     * @param          $storage
     *
     * @return array|mixed
     * @throws \Throwable
     */
	public static function getAddresses($query, $cities, Storage $storage)
    {
        $endpoint = "https://sk-taxi.ru/tmapi/api.php?method=%2Fcommon_api%2F1.0%2Fget_addresses_like2";
        $client = new Client();
        $promises = collect();

        $cities = self::getCitiesWithOneDistrict($cities,$storage->get('city'), $storage->get('district'));

        foreach ($cities as $city) {
            $paramJson = json_encode(
                [
                    'address'     => $query,
                    'get_streets' => true,
                    'get_points'  => true,
                    'get_houses'  => true,
                    'city'        => $city->name,
                ]
            );
            $request = new Request(
                'POST',
                $endpoint,
                ['Content-Type' => 'application/json'],
                $paramJson
            );
            self::_log($endpoint, collect($query)->toJson() . ' в городе '.$city->name,null);
            $promises->push($client->sendAsync($request));
        }

        $results = \GuzzleHttp\Promise\Utils::unwrap($promises->toArray());
        \GuzzleHttp\Promise\Utils::settle($promises)->wait();
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

        self::_log($endpoint,$cities->toJson(JSON_UNESCAPED_UNICODE),json_encode($addresses,JSON_UNESCAPED_UNICODE));

        $result = self::sortAddresses($addresses,$storage);
        return $result ?? [];
    }

    private static function _log($url,$params,$result){
        $log = new LogApi();
        $log->params = $params;
        $log->url = $url;
        $log->result = $result;
        $log->save();
    }

    public static function getCitiesWithOneCrewId($cities, $crewId)
    {
        $cities = collect($cities);
        $cities = $cities->filter(function ($city) use ($crewId) {
            if($city->crewGroupId == $crewId) {
                return $city;
            }
        });

        return $cities;

    }

    public static function getCitiesWithOneDistrict($cities,$cityName, $district)
    {

        $cities = collect($cities);
        $cities = $cities->filter(function ($city) use ($district,$cityName) {
            if ($city->name == $cityName) return $city;
            if($district != '' && Str::upper($city->district) == Str::upper($district)) {
                return $city;
            }
        });

        return $cities;

    }

	public static function sortAddresses($addresses,$storage)
	{
        $addresses = collect($addresses)->sort(function ($a, $b) use ($storage){
            $city = $storage->get('city');
            if ($a['city'] == $city && $b['city'] != $city) return -1;
            if ($a['city'] != $city && $b['city'] == $city) return 1;
            $kinds = ['house' => 99,'street' => 50,'point' => 1];
            $aKind = $kinds[$a['kind']];
            $bKind = $kinds[$b['kind']];
            if ($aKind > $bKind) return -1;
            if ($aKind < $bKind) return 1;
            if ($aKind==$bKind && $b['kind'] == 'house'){
                return strcasecmp($a['house'],$b['house']);
            }

            return 0;
        });
        return $addresses;

	}

	public static function haveEndAddressFromStorageAndAllAdressesIsReal(Storage $userStorage)
	{
		if (count((array)$userStorage->get('lat')) > 1) {
		    $address = (array)$userStorage->get('lat');
           foreach ($address as $item){
               if ($item == 0) return false;
           }
			return true;
		} else {
			return false;
		}
	}

    public static function haveFirstAddressFromStorageAndFirstAdressesIsReal(Storage $userStorage)
    {
        if (count((array)$userStorage->get('lat')) > 0) {
            $address = collect((array)$userStorage->get('lat'))->first();
            if ($address == 0) return false;
            return true;
        } else {
            return false;
        }
    }

    public static function findByAnswer($addressesList,$answer){
        return collect($addressesList)->filter(function ($item) use ($answer) {
            if (Address::toString($item) == $answer->getText()) {
                return $item;
            }
        })->first();
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

	public static function toString($address){
	    if ($address['kind'] ==  'house'){
	        return  $address['street'] . ' ' . $address['house'] . ' (' . $address['city'] . ')';
        }
        if ($address['kind'] ==  'point'){
            $street = '';
            if (!empty($address['street'])) $street .=', '.$address['street'];
            if (!empty($address['house'])) $street .=' '.$address['house'];
            return  $address['point'] . ' (' . $address['city'] .$street. ')';
        }
        if ($address['kind'] ==  'street'){
            return  $address['street'] . ' (' . $address['city'] . ')';
        }
    }

    public static function subStrAddress($address)
    {
        if(strlen($address) > self::MAX_ADDRESS_LENGTH) {
            return  mb_substr($address, 0, self::MAX_ADDRESS_LENGTH);
        } else {
            return $address;
        }
    }

}