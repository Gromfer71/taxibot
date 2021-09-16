<?php

namespace App\Traits;

use App\Models\User;
use Barryvdh\TranslationManager\Models\LangPackage;

/**
 *
 */
trait UserManagerTrait
{
    /**
     * Проверка пользователя на наличие в системе
     *
     * @return bool
     */
    protected function isUserRegistered(): bool
    {
        return (bool)User::find($this->bot->getUser()->getId());
    }

    /**
     * Регистрация нового пользователя в системе
     *
     * @return void
     */
    protected function registerUser(): void
    {
        $user = User::create([
                                 'username' => $this->bot->getUser()->getUsername(),
                                 'firstname' => $this->bot->getUser()->getFirstName(),
                                 'lastname' => $this->bot->getUser()->getLastName(),
                                 'userinfo' => json_encode($this->bot->getUser()->getInfo()),
                                 'lang_id' => LangPackage::getDefaultLangId(),
                             ]);
        $user->setPlatformId($this->bot);
        $user->save();
    }
}