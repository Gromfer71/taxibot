<?php

namespace App\Traits;

use App\Models\User;
use App\Services\OrderApiService;
use Barryvdh\TranslationManager\Models\LangPackage;

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
        $this->saveToStorage(['phone' => OrderApiService::replacePhoneCountyCode($phone)]);
    }

    /**
     * Сохранение смс кода в кеш
     *
     * @param $code
     */
    protected function saveSmsCode($code)
    {
        $this->saveToStorage(['sms_code' => $code]);
    }

    /**
     * Сообщает смс код пользователю по звонку
     */
    protected function callSmsCode()
    {
        $api = new OrderApiService();
        $smsCode = $api->getRandomSMSCode();
        $api->callSMSCode('7' . $this->getFromStorage('phone'), $smsCode);
        $this->saveSmsCode($smsCode);
    }

    /**
     * Проверяет, правильный ли смс код ввел пользователь
     *
     * @param $userInput
     * @return bool
     */
    protected function isSmsCodeCorrect($userInput): bool
    {
        return $userInput == $this->getFromStorage('sms_code');
    }

    /**
     * Выполняется после успешного ввода смс кода, регистрирует нового пользователя в зависимости от наличия телефона в системе
     */
    protected function registerUser()
    {
        if ($this->isPhoneAlreadyRegistered()) {
            $this->registerUserFromExist();
        } else {
            $this->createNewUser();
        }
    }

    /**
     * Проверяет, существует ли уже в системе пользователь с таким телефоном
     *
     * @return bool
     */
    private function isPhoneAlreadyRegistered(): bool
    {
        return (bool)User::wherePhone($this->getFromStorage('phone'))->count();
    }

    /**
     * Делаем "слияние" двух пользователей в одного, т.к. если два пользователя имеют один телефон, но на разных
     * платформах, то нужно объединить их в одного
     */
    private function registerUserFromExist()
    {
        $user = User::wherePhone($this->getFromStorage('phone'))->first();
        $this->_sayDebug($user->phone);
        $user->setPlatformId($this->getBot());
    }

    /**
     * Регистрация нового пользователя в системе
     *
     * @return void
     */
    private function createNewUser(): void
    {
        $user = User::create([
                                 'username' => $this->bot->getUser()->getUsername(),
                                 'firstname' => $this->bot->getUser()->getFirstName(),
                                 'lastname' => $this->bot->getUser()->getLastName(),
                                 'userinfo' => json_encode($this->bot->getUser()->getInfo()),
                                 'phone' => $this->getFromStorage('phone'),
                                 'lang_id' => LangPackage::getDefaultLangId(),
                             ]);
        $user->setPlatformId($this->bot);
        $user->save();
    }


}