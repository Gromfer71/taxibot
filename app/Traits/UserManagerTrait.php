<?php

namespace App\Traits;

use App\Models\User;

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


}