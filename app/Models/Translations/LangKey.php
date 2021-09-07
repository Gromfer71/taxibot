<?php

namespace App\Models\Translations;

use App\Models\Config;
use Illuminate\Database\Eloquent\Model;

class LangKey extends Model
{
    protected $table = 'lang_keys';
    public $timestamps = false;
    
    public static function setUpKeys()
    {
        $messages = Config::MESSAGE_LABELS;
        foreach ($messages as $message) {
            
        }
    }
}
