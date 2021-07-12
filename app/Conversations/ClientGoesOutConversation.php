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

class ClientGoesOutConversation extends BaseAddressConversation
{
	public function run()
	{
		$this->inWay();
	}

	public function inWay($withoutMessage = false)
	{
        $this->_sayDebug('ClientGoesOutConversation - inWay');
		$order = OrderHistory::getActualOrder($this->bot->getUser()->getId());
		$message = 'Приятной поездки';
		if ($withoutMessage) $message = '';
		$question = Question::create($message, $this->bot->getUser()->getId())->addButtons([
			Button::create(trans('buttons.finish order'))->additionalParameters(['config' => ButtonsFormatterService::SPLITBYTWOEXCLUDEFIRST_MENU_FORMAT])->value('finish order'),
			Button::create(trans('buttons.need dispatcher'))->value('need dispatcher'),
            Button::create(trans('buttons.need driver'))->value('need driver'),
            Button::create(trans('buttons.need map'))->value('need map'),
		]);

		return $this->ask($question, function (Answer $answer) use ($order) {
			Log::newLogAnswer($this->bot, $answer);
			if ($answer->isInteractiveMessageReply()) {
				if ($answer->getValue() == 'need driver') {
					$api = new OrderApiService();
					$api->connectClientAndDriver($order);
					$this->say(trans('messages.connect with driver'), $this->bot->getUser()->getId());
					$this->inWay(true);
				} elseif ($answer->getValue() == 'finish order') {
					if ($order) $order->finishOrder();
					$this->end();
				} elseif ($answer->getValue() == 'need dispatcher') {
					$api = new OrderApiService();
					$this->say(trans('messages.wait for dispatcher'),$this->bot->getUser()->getId());
					$api->connectDispatcher(User::find($this->bot->getUser()->getId())->phone);
					$this->inWay(true);
				} elseif($answer->getValue() == 'cancel order') {
                    if ($order) $order->cancelOrder();
                    $this->bot->startConversation(new RunStartAfterButtonConversation());
                } elseif($answer->getValue() == 'client_goes_out_late') {
                    $api = new OrderApiService();
                    if ($order)  $api->changeOrderState($order, OrderApiService::USER_GOES_OUT);
                    $this->inWay();
                }   elseif($answer->getValue() == 'client_goes_out') {
                     $this->inWay(true);
               } elseif($answer->getValue() == 'need map') {
                    $api = new OrderApiService();
                    $driverLocation = $api->getCrewCoords($api->getOrderState(OrderHistory::getActualOrder($this->bot->getUser()->getId()))->data->crew_id ?? null);
                    if($driverLocation) {
                        $actualOrder = OrderHistory::getActualOrder($this->bot->getUser()->getId());
                        $auto = $actualOrder->getAutoInfo();
                        $this->say(trans('messages.need map message when driver at place', ['auto' => $auto]));
                        OrderApiService::sendDriverLocation($this->bot, $driverLocation->lat, $driverLocation->lon);
                    } else {
                        $this->say('К сожалению на данный момент мы не можем определить где водитель :( ');
                    }
                    $this->inWay(true);
                } else {
                    $this->_fallback($answer);
                }
			} else {
                $this->inWay(true);
            }
		});
	}


}