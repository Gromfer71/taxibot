<?php

namespace App\Traits;

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
            $this->say($this->__('messages.program error message'));
            $this->removeFromStorage('error');

        }
    }
}