<?php


namespace App\Conversations;


use App\Models\Log;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\ButtonsFormatterService;
use App\Services\OrderApiService;
use BotMan\BotMan\Messages\Conversations\Conversation;
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
        if ($withoutMessage) $message = '';
        $question = Question::create($message , $this->bot->getUser()->getId())->addButtons(
            [
                Button::create(trans('buttons.need dispatcher'))->additionalParameters(['config' => ButtonsFormatterService::TWO_LINES_DIALOG_MENU_FORMAT]),
                Button::create(trans('buttons.need driver')),
                Button::create(trans('buttons.cancel order')),
                Button::create(trans('buttons.need map')),
            ]
        );
        $order = OrderHistory::getActualOrder($this->bot->getUser()->getId());

        return $this->ask(
            $question,
            function (Answer $answer) use ($order) {
                Log::newLogAnswer($this->bot, $answer);
                    if ($answer->getValue() == 'need driver') {
                        $api = new OrderApiService();
                        if ($order) $api->connectClientAndDriver($order);
                        $this->say(trans('messages.connect with driver'), $this->bot->getUser()->getId());
                        $this->confirmOrder(true);
                    } elseif($answer->getValue() == 'order_confirm') {
                        $this->confirmOrder(false);
                    } elseif ($answer->getValue() == 'need dispatcher') {
                        $api = new OrderApiService();
						$this->say(trans('messages.wait for dispatcher'), $this->bot->getUser()->getId());
                        $api->connectDispatcher(User::find($this->bot->getUser()->getId())->phone);
                        $this->confirmOrder(true);
                    } elseif($answer->getValue() == 'client_goes_out') {
                        $api = new OrderApiService();
                        if ($order) $api->changeOrderState($order, OrderApiService::USER_GOES_OUT);
                        $this->bot->startConversation(new \App\Conversations\ClientGoesOutConversation());
                    } elseif($answer->getValue() == 'client_goes_out_late') {
                        $api = new OrderApiService();
                        if ($order) $api->changeOrderState($order, OrderApiService::USER_GOES_OUT);
                        $this->bot->startConversation(new \App\Conversations\ClientGoesOutConversation());
                    }elseif($answer->getValue() == 'cancel order') {
                        if ($order) $order->cancelOrder();
                        $this->bot->startConversation(new RunStartAfterButtonConversation());
                    } elseif ($answer->getValue() == 'finish order') {
					   if ($order) $order->finishOrder();
					   $this->end();
				   } elseif($answer->getValue() == 'need map') {
                        $api = new OrderApiService();
                        $driverLocation = $api->getCrewCoords($api->getOrderState(OrderHistory::getActualOrder($this->bot->getUser()->getId()))->data->crew_id ?? null);
                        if($driverLocation) {
                            OrderApiService::sendDriverLocation($this->bot->getUser()->getId(), $driverLocation->lat, $driverLocation->lon);
                        } else {
                            $this->say('К сожалению на данный момент мы не можем определить где водитель :( ');
                        }
                        $this->confirmOrder(true);
                    }  else {
                        if (!$answer->isInteractiveMessageReply()){
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
        $question = Question::create(trans('messages.cancel order'), $this->bot->getUser()->getId())->addButtons(
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