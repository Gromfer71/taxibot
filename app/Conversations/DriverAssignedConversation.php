<?php


namespace App\Conversations;


use App\Conversations\FavoriteRoutes\AddedRouteMenuConversation;
use App\Conversations\MainMenu\MenuConversation;
use App\Models\Log;
use App\Models\OrderHistory;
use App\Services\ButtonsFormatterService;
use App\Services\OrderApiService;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

class DriverAssignedConversation extends BaseConversation
{


    public function run()
    {
        $this->confirmOrder();
    }

    public function confirmOrder($withoutMessage = false)
    {
        $this->_sayDebug('DriverAssignedConversation - confirmOrder');
        $message = 'Ваш автомобиль уже в пути';
        if ($withoutMessage) {
            $message = '';
        }
        $question = Question::create($message, $this->bot->getUser()->getId())->addButtons(
            [
                Button::create($this->__('buttons.need dispatcher'))->additionalParameters(
                    ['config' => ButtonsFormatterService::TWO_LINES_DIALOG_MENU_FORMAT]
                ),
                Button::create($this->__('buttons.need driver'))->value('need driver'),
                Button::create($this->__('buttons.cancel order'))->value('cancel order'),
                Button::create($this->__('buttons.need map'))->value('need map'),
            ]
        );
        $order = OrderHistory::getActualOrder($this->bot->getUser()->getId(), $this->bot->getDriver()->getName());

        return $this->ask(
            $question,
            function (Answer $answer) use ($order) {
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->getValue() == 'need driver') {
                    $api = new OrderApiService();
                    if ($order) {
                        $api->connectClientAndDriver($order);
                    }
                    $this->say($this->__('messages.connect with driver'), $this->bot->getUser()->getId());
                    $this->confirmOrder(true);
                } elseif ($answer->getValue() == 'order_confirm') {
                    $order = OrderHistory::getActualOrder(
                        $this->bot->getUser()->getId(),
                        $this->bot->getDriver()->getName()
                    );
                    if ($order) {
                        $order->confirmOrder();
                    }
                    $this->bot->startConversation(new DriverAssignedConversation());
                } elseif ($answer->getValue() == 'need dispatcher') {
                    $this->say($this->__('messages.wait for dispatcher'), $this->bot->getUser()->getId());
                    $this->getUser()->setUserNeedDispatcher();
                    $this->confirmOrder(true);
                } elseif ($answer->getValue() == 'client_goes_out') {
                    $api = new OrderApiService();
                    if ($order) {
                        $api->changeOrderState($order, OrderApiService::USER_GOES_OUT);
                    }
                    $this->bot->startConversation(new ClientGoesOutConversation());
                } elseif ($answer->getValue() == 'client_goes_out_late') {
                    $api = new OrderApiService();
                    if ($order) {
                        $api->changeOrderState($order, OrderApiService::USER_GOES_OUT);
                    }
                    $this->bot->startConversation(new ClientGoesOutConversation());
                } elseif ($answer->getValue() == 'cancel order') {
                    if ($order) {
                        $order->cancelOrder();
                    }
                    $this->bot->startConversation(new RunStartAfterButtonConversation());
                } elseif ($answer->getValue() == 'finish order') {
                    if ($order) {
                        $order->finishOrder();
                    }
                    $this->end();
                } elseif ($answer->getValue() == 'need map') {
                    $api = new OrderApiService();
                    $driverLocation = $api->getCrewCoords(
                        $api->getOrderState(
                            OrderHistory::getActualOrder(
                                $this->bot->getUser()->getId(),
                                $this->bot->getDriver()->getName()
                            )
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
                        $this->say(
                            $this->__('messages.need map message while driver goes', ['time' => $time, 'auto' => $auto])
                        );
                        OrderApiService::sendDriverLocation($this->bot, $driverLocation->lat, $driverLocation->lon);
                    } else {
                        $this->say($this->__('messages.error driver location'));
                    }
                    $this->confirmOrder(true);
                } elseif ($answer->getValue() == 'add to favorite routes') {
                    $this->bot->startConversation(new AddedRouteMenuConversation());
                } else {
                    if (!$answer->isInteractiveMessageReply()) {
                        $this->confirmOrder(true);
                        return;
                    }
                    $this->_fallback($answer);
                }
            }
        );
    }

    public function cancelOrder()
    {
        $question = Question::create($this->__('messages.cancel order'), $this->bot->getUser()->getId())->addButtons(
            [
                Button::create('Продолжить')->value('Продолжить'),
            ]
        );

        return $this->ask(
            $question,
            function (Answer $answer) {
                Log::newLogAnswer($this->bot, $answer);
                $this->bot->startConversation(new MenuConversation());
            }
        );
    }
}