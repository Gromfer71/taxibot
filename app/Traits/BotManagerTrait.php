<?php

namespace App\Traits;

use App\Conversations\StartConversation;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\OrderApiService;
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

    protected function checkProgramForBroken()
    {
        if ($this->bot->userStorage()->get('error')) {
            $this->say(Translator::trans('messages.program error message'));
            $this->removeFromStorage('error');
        }
    }

    protected function sendDriverMap()
    {
        $api = new OrderApiService();
        $driverLocation = $api->getCrewCoords(
            $api->getOrderState(
                OrderHistory::getActualOrder(
                    $this->getUser()->id,
                    $this->bot->getDriver()->getName()
                )->id
            )->data->crew_id ?? null
        );
        if ($driverLocation) {
            $actualOrder = OrderHistory::getActualOrder(
                $this->bot->getUser()->getId(),
                $this->bot->getDriver()->getName()
            );
            $actualOrder->updateOrderState();
            $auto = $actualOrder->getAutoInfo();
            $time = $api->driverTimeCount($actualOrder->id)->data->DRIVER_TIMECOUNT;
            $state = $actualOrder->getCurrentOrderState()->state_id;
            if ($state == OrderHistory::DRIVER_ASSIGNED || $state == OrderApiService::ORDER_CONFIRMED_BY_USER) {
                $this->say(
                    Translator::trans('messages.need map message while driver goes', ['time' => $time, 'auto' => $auto])
                );
            }
            if ($state == OrderApiService::USER_GOES_OUT) {
                $this->say(
                    Translator::trans(
                        'messages.auto waits for client',
                        ['auto' => $actualOrder->getAutoInfo()]
                    )
                );
            }
            OrderApiService::sendDriverLocation($this->bot, $driverLocation->lat, $driverLocation->lon);
        } else {
            $this->say(Translator::trans('messages.error driver location'));
        }
        $this->confirmOrder(true);
    }
}