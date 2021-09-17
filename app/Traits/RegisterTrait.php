<?php

namespace App\Traits;

use App\Services\OrderApiService;

/**
 * Методы для работы с регистрацией
 */
trait RegisterTrait
{
    /**
     * @param $phone
     * @return false|int
     */
    protected function isPhoneCorrect($phone)
    {
        return preg_match('^\+?[78][-\(]?\d{3}\)?-?\d{3}-?\d{2}-?\d{2}$^', $phone);
    }

    /**
     * Отправка смс кода на телефон и сохранение кода в кеш
     */
    protected function sendSmsCode($phone)
    {
        $api = new OrderApiService();
        $smsCode = $api->getRandomSMSCode();
        $this->saveSmsCode($smsCode);
        $api->sendSMSCode($phone, $smsCode);
    }

    /**
     * Сохранение номера телефона пользователя в кеш
     *
     * @param $phone
     */
    protected function saveUserPhone($phone)
    {
        $this->getBot()->userStorage()->save(['phone' => $phone]);
    }

    /**
     * Сохранение смс кода в кеш
     *
     * @param $code
     */
    protected function saveSmsCode($code)
    {
        $this->getBot()->userStorage()->save(['sms_code' => $code]);
    }



}