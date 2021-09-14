<?php


namespace App\Services;

use Barryvdh\TranslationManager\Models\LangPackage;

class Translator
{
    static $lang;

    public static function setUp($user)
    {
        if(is_null(Translator::$lang)) {
            if(! $user->lang_id) {
                $user->setDefaultLang();
            }

            $package = LangPackage::find($user->lang_id);
            if(!$package) {
                $user->setDefaultLang();
            }

            Translator::$lang = $package->code;
        }
    }


    public static function trans($key, $replace = [])
    {
        return trans($key, $replace, self::$lang);
    }

}