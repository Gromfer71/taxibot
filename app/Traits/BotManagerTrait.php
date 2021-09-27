<?php

namespace App\Traits;

use App\Conversations\StartConversation;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\Translator;

/**
 * Методы для проверок, управления ботом
 */
trait BotManagerTrait
{
    /**
     * Создание заказа за наличку
     */
    public function _go_for_cash()
    {
        if (!OrderHistory::newOrder($this->bot)) {
            $this->say(Translator::trans('messages.create order error'));
            $this->bot->startConversation(new StartConversation());
        } else {
            $this->currentOrderMenu(true);
        }
    }

    /**
     *  Создание заказа за бонусы
     */
    public function _go_for_bonuses()
    {
        if ($this->getUser()->getBonusBalance() > 0) {
            $this->bot->userStorage()->save(['usebonus' => true]);
            $this->bot->userStorage()->save(
                ['bonusbalance' => User::find($this->bot->getUser()->getId())->getBonusBalance()]
            );
        } else {
            $this->say(Translator::trans('messages.zero bonus balance'));
        }
        $this->currentOrderMenu(true);
        if (!OrderHistory::newOrder($this->bot, true)) {
            $this->say(Translator::trans('messages.create order error'));
            $this->bot->startConversation(new StartConversation());
        }
    }

    /**
     *  Проверка на ошибку в программе
     */
    protected function checkProgramForBroken()
    {
        if ($this->bot->userStorage()->get('error')) {
            $this->say(Translator::trans('messages.program error message'));
            $this->removeFromStorage('error');
        }
    }
}