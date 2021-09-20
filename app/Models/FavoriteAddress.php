<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FavoriteAddress
 *
 * @property int $id
 * @property int $user_id
 * @property string $address
 * @property string $name
 * @property string $lat
 * @property string $lon
 * @property string|null $city
 * @property string|null $created_at
 * @property string|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress query()
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FavoriteAddress whereUserId($value)
 * @mixin \Eloquent
 */
class FavoriteAddress extends Model
{
    protected $table = 'favorite_addresses';

    protected $guarded = [];
    public $timestamps = false;



}
