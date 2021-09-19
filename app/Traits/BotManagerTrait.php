<?php

namespace App\Traits;

use App\Services\Translator;

/**
 * Методы для проверок, управления ботом
 */
trait BotManagerTrait
{
    /**
     *  Проверка на ошибку в программе
     */
    protected function checkProgramForBroken()
    {
        if ($this->bot->userStorage()->get('error')) {
            $this->_sayDebug(Translator::trans('messages.program error message'));
            $this->removeFromStorage('error');
        }
    }
}