<?php


namespace App\Services;


use App\Models\User;

class BonusesApi
{

    public const USER_NOT_FOUND = 100;
    public const SUCCESS = 0;

    public static function analyzePhone($phone)
    {
        return json_decode(file_get_contents('https://sk-taxi.ru/tmapi/analyze_phone.php?phone=8' . $phone . '&search_in_drivers_mobile=False&search_in_clients=True&search_in_phones=True', false));
    }

    public static function getClientInfo($id)
    {
        return json_decode(file_get_contents('https://sk-taxi.ru/tmapi/get_client_info.php?client_id=' . $id, false));
    }
}