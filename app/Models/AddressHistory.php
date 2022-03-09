<?php

namespace App\Models;

use App\Services\Address;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AddressHistory
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $address
 * @property string|null $city
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static Builder|AddressHistory newModelQuery()
 * @method static Builder|AddressHistory newQuery()
 * @method static Builder|AddressHistory query()
 * @method static Builder|AddressHistory whereAddress($value)
 * @method static Builder|AddressHistory whereCreatedAt($value)
 * @method static Builder|AddressHistory whereId($value)
 * @method static Builder|AddressHistory whereUpdatedAt($value)
 * @method static Builder|AddressHistory whereUserId($value)
 * @mixin \Eloquent
 * @property string|null $lat
 * @property string|null $lon
 * @method static Builder|AddressHistory whereCity($value)
 * @method static Builder|AddressHistory whereLat($value)
 * @method static Builder|AddressHistory whereLon($value)
 */
class AddressHistory extends Model
{
    public const ENTRANCE_SIGNATURE = '*п';
    protected $table = 'address_history';
    protected $guarded = [];

    public static function clearByUserId($userId)
    {
        self::where('user_id', $userId)->delete();
    }

    public static function newAddress($userId, $address, $coords, $city)
    {
        $address = Address::removeEllipsisFromAddressIfExists($address);
        $address = Address::subStrAddress($address);
        if ($address == Translator::trans('messages.invalid message')) {
            return;
        }
        if (!FavoriteAddress::where(
            [
                'user_id' => $userId,
                'address' => $address,
            ]
        )->first()) {
            return self::firstOrCreate([
                                           'user_id' => $userId,
                                           'address' => $address,
                                           'city' => $city,
                                           'lat' => $coords['lat'] ?? null,
                                           'lon' => $coords['lon'] ?? null,
                                       ]);
        }
    }

    public static function getAddressFromAnswer(Answer $answer, $userId)
    {
        return self::where(['address' => $answer->getText(), 'user_id' => $userId])->first();
    }

    public static function createIfNotExistsEverywhere($userId, $address, $lat, $lon)
    {
        $exists = self::where(['address' => $address, 'user_id' => $userId])->exists();
        $exists = FavoriteAddress::where(['address' => $address, 'user_id' => $userId])->exists() || $exists;
        $routes = FavoriteRoute::where('user_id', $userId)->get();
        foreach ($routes as $route) {
            $addresses = collect(json_decode($route->address, false)->address);
            foreach ($addresses as $routeAddress) {
                if ($address == $routeAddress) {
                    $exists = true;
                }
            }
        }
        if (!$exists) {
            AddressHistory::firstOrCreate([
                                              'user_id' => $userId,
                                              'address' => $address,
                                              'lat' => $lat,
                                              'lon' => $lon
                                          ]);
        }
    }

}
