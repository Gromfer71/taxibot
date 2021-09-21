<?php

namespace App\Services;

/**
 * Языковой менеджер для работы с языковыми пакетами с бд и файлами
 */
class Translator
{
    public static $lang = 'ru';

    /**
     * Перевод строки
     *
     * @param $key
     * @param array $replace
     * @return array|\Illuminate\Contracts\Translation\Translator|\Illuminate\Foundation\Application|string|null
     */
    public static function trans($key, array $replace = [])
    {
        if (trans($key, $replace, self::$lang) == $key) {
            if (trans($key, $replace, 'ru') == $key) {
                return 'Упс, данное сообщение не переведено ни на один язык!';
            }

            return trans($key, $replace, 'ru');
        }

        return trans($key, $replace, self::$lang);
    }
}