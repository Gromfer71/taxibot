<?php

namespace App\Models;

use App\Services\Address;
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
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AddressHistory whereUserId($value)
 * @mixin \Eloquent
 */
class AddressHistory extends Model
{
    protected $table = 'address_history';
    protected $guarded = [];

    const ENTRANCE_SIGNATURE ='*п';

    public static function clearByUserId($userId){
        self::where('user_id',$userId)->delete();
    }

    public static function newAddress($userId, $address, $coords, $city)
    {
       $address = Address::subStrAddress($address);

        if(!FavoriteAddress::where(
            [
                'user_id'  => $userId,
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

}
