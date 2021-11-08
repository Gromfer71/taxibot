<?php

namespace App\Conversations\Settings;

use App\Conversations\BaseConversation;
use App\Models\OrderHistory;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class ClearOrdersHistoryConversation extends BaseConversation
{
    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => 'App\Conversations\Settings\SettingsConversation',
            ButtonsStructure::CLEAN_ALL_ADDRESS_HISTORY => function () {
                $this->say(Translator::trans('messages.delete all orders'));
                $this->getUser()->orders()->delete();
                $this->bot->startConversation(new SettingsConversation());
            },
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        if (property_exists($this->bot->getDriver(), 'needToAddAddressesToMessage')) {
            $additional = [
                'location' => 'addresses',
                'config' => ButtonsFormatterService::SPLIT_BY_THREE_EXCLUDE_TWO_LINES
            ];
        } else {
            $additional = [];
        }
        $question = ComplexQuestion::createWithSimpleButtons(
            $this->addOrdersRoutesToMessage(Translator::trans('messages.delete orders history menu')),
            [ButtonsStructure::BACK, ButtonsStructure::CLEAN_ALL_ADDRESS_HISTORY],
            $additional
        );

        $question = ComplexQuestion::addOrderHistoryButtons($question, $this->getUser()->orders);
        $orders = $this->getUser()->orders;
        foreach ($orders as $order) {
            $order->address = implode(' – ', collect(json_decode($order->address)->address)->toArray());
        }
        $this->_sayDebug(json_encode($this->bot->userStorage()->get('routes'), JSON_UNESCAPED_UNICODE));

        $this->bot->userStorage()->save(['routes' => $orders->pluck('id', 'address')->toArray()]);


        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer);
            $this->orderMenu();
        });
    }

    public function orderMenu()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.delete order'),
            [ButtonsStructure::DELETE, ButtonsStructure::BACK]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer, [ButtonsStructure::BACK => 'run']);
            if ($answer->getValue() == ButtonsStructure::DELETE) {
                $this->_sayDebug(
                    $this->bot->userStorage()->get($answer->getText()) . ' - ' . json_encode(
                        $this->bot->userStorage()->all()
                    )
                );
                if ($order = OrderHistory::where(
                    [
                        'user_id' => $this->getUser()->id,
                        'id' => array_get($this->bot->userStorage()->get('routes'), $answer->getText())
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