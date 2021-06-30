<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteAddress extends Model
{
    protected $table = 'favorite_addresses';

    protected $guarded = [];
    public $timestamps = false;
}
