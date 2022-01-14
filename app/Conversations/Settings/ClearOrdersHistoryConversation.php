<?php

namespace App\Conversations\Settings;

use App\Conversations\BaseConversation;
use App\Conversations\MainMenu\MenuConversation;
use App\Models\OrderHistory;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class ClearOrdersHistoryConversation extends BaseConversation
{
    public function getActions($replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => SettingsConversation::class,
            ButtonsStructure::CLEAN_ALL_ADDRESS_HISTORY => function () {
                $this->say(Translator::trans('messages.delete all orders'));
                $this->getUser()->orders()->delete();
                $this->bot->startConversation(new MenuConversation());
            },
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            $this->addOrdersRoutesToMessage(Translator::trans('messages.delete orders history menu')),
            [ButtonsStructure::BACK, ButtonsStructure::CLEAN_ALL_ADDRESS_HISTORY],
            ButtonsFormatterService::getAdditionalForClearMenu($this->bot->getDriver())
        );

        $question = ComplexQuestion::addOrderHistoryButtons($question, $this->getUser()->orders);
        $orders = $this->getUser()->orders;
        foreach ($orders as $order) {
            $order->address = implode(' – ', collect(json_decode($order->address)->address)->toArray());
        }
        $this->bot->userStorage()->save(['routes' => $orders->pluck('id', 'address')->toArray()]);

        return $this->ask($question, function (Answer $answer) {
            if ($this->handleAction($answer)) {
                return;
            }
            $route = collect($this->getFromStorage('address_in_number'));
            $route = $route->get($answer->getText());
            if (!$route) {
                $route = $answer->getText();
            }
            $this->saveToStorage(['route' => $route]);
            $this->orderMenu();
        });
    }

    public function orderMenu()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.delete order', ['route' => $this->getFromStorage('route')]),
            [ButtonsStructure::BACK, ButtonsStructure::DELETE]
        );

        return $this->ask($question, function (Answer $answer) {
            if ($this->handleAction($answer, [ButtonsStructure::BACK => 'run'])) {
                return;
            }
            if ($answer->getValue() == ButtonsStructure::DELETE) {
                if ($order = OrderHistory::where(
                    [
                        'user_id' => $this->getUser()->id,
                        'id' => array_get($this->bot->userStorage()->get('routes'), $this->getFromStorage('route'))
                    ]
                )->first()) {
                    $order->delete();
                    $this->say(Translator::trans('messages.order has been deleted'));
                } else {
                    $this->say(Translator::trans('messages.problems with delete order'));
                }
                $this->run();
            } else {
                $this->orderMenu();
            }
        });
    }
}