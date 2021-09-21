<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

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
        Log::info('язык -  ' . self::$lang);
        if (trans($key, $replace, self::$lang) == $key) {
            if (trans($key, $replace, 'ru') == $key) {
                throw new Exception('Для выражения ' . $key . ' нет ни одного перевода! Срочно исправить!');
            }

            return trans($key, $replace, 'ru');
        }

        return trans($key, $replace, self::$lang);
    }
}