<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LangPackage extends Model
{
    protected $table = 'lang_packages';
    protected $guarded = [];

    public static function getByName($name)
    {
        return self::where('name', $name)->first();
    }

    public static function getPackagesName()
    {
        return self::all('name')->toArray();
    }
}
