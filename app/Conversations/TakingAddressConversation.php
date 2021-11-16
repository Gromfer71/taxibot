<?php

namespace App\Conversations;

use App\Conversations\FavoriteRoutes\TakingAddressForFavoriteRouteConversation;
use App\Models\AddressHistory;
use App\Services\Address;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;
use App\Traits\TakingAddressTrait;
use BotMan\BotMan\Messages\Incoming\Answer;

/**
 *  Диалог для получения маршрута
 */
class TakingAddressConversation extends BaseAddressConversation
{
    use TakingAddressTrait;

    public $conversationAfterTakeAddress = 'App\Conversations\TaxiMenuConversation';

    /**
     * @return void
     */
    public function run()
    {
        $this->getAddress(Translator::trans('messages.give me your address'), true);
    }

    /**
     * Массив действий под определенную кнопку. Если значение это анонимная функция, то выполнится она, если имя метода,
     * то выполнится он в контексте текущего класса, если название класса (с полным путем), то запустится его Conversation.
     *
     * @param array $replaceActions
     * @return array
     */
    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::EXIT => 'App\Conversations\MainMenu\MenuConversation',
            ButtonsStructure::EXIT_TO_MENU => 'App\Conversations\MainMenu\MenuConversation',
            ButtonsStructure::GO_AS_INDICATED => function () {
                if (self::NEED_TO_SAVE_ADDRESS_HISTORY) {
                    AddressHistory::newAddress(
                        $this->getUser()->id,
                        collect($this->bot->userStorage()->get('address'))->last(),
                        ['lat' => 0, 'lon' => 0],
                        $this->bot->userStorage()->get('address_city')
                    );
                }
                $this->bot->startConversation(new $this->conversationAfterTakeAddress());
            },
            ButtonsStructure::ADDRESS_WILL_SAY_TO_DRIVER => function () {
                $this->_saveSecondAddressByText('');
                $this->saveToStorage(['second_address_will_say_to_driver_change_text_flag' => 1]);
                $this->saveToStorage(['second_address_will_say_to_driver_flag' => 1]);
                $this->bot->startConversation(new $this->conversationAfterTakeAddress());
            },
            ButtonsStructure::NO_ENTRANCE => function () {
                if (self::NEED_TO_SAVE_ADDRESS_HISTORY) {
                    $this->createAddressHistory($this->getFromStorage('address'));
                }

                $this->getAddressTo();
            }
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    public function getAddressTo()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            $this->addAddressesToMessage($this->getAddressToMessage())
        );
        if ($this->conversationAfterTakeAddress != TakingAddressForFavoriteRouteConversation::ADDED_ROUTE_CONVERSATION) {
            $question = ComplexQuestion::setButtons(
                $question,
                [ButtonsStructure::ADDRESS_WILL_SAY_TO_DRIVER],
                ['location' => 'addresses']
            );
        }
        $question = ComplexQuestion::setButtons(
            $question,
            [ButtonsStructure::EXIT],
            ['location' => 'addresses']
        );
        $question = $this->_addAddressFavoriteButtons($question);
        $question = $this->_addAddressHistoryButtons($question);

        return $this->ask(
            $question,
            function (Answer $answer) {
                $this->handleAction($answer);
                $this->handleSecondAddress($answer);
            }
        );
    }

    public function getAddressToAgain()
    {
        $addressesList = $this->getAddressesList(1);
        $question = ComplexQuestion::createWithSimpleButtons(
            $this->addAddressesFromApi(Translator::trans('messages.give address again'), $addressesList),
            [ButtonsStructure::EXIT],
            ['location' => 'addresses']
        );


        if ($addressesList->isNotEmpty()) {
            $question = ComplexQuestion::setAddressButtons(
                $question,
                $addressesList->map(function ($address) {
                    return Address::toString($address);
                })
            );
        } else {
            $this->streetNotFoundAddressTo();
            die();
        }

        return $this->ask(
            $question,
            function (Answer $answer) use ($addressesList) {
                $this->handleAction($answer);
                $this->handleSecondAddressAgain($addressesList, $answer);
            }
        );
    }

    public function streetNotFoundAddressTo()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.not found address dorabotka bota'),
            [ButtonsStructure::BACK, ButtonsStructure::GO_AS_INDICATED, ButtonsStructure::EXIT_TO_MENU],
            ['config' => ButtonsFormatterService::AS_INDICATED_MENU_FORMAT]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer, [ButtonsStructure::BACK => 'getAddressTo']);
            $this->_saveSecondAddress($answer->getText());
            $this->getAddressToAgain();
        });
    }

    public function redirectAfterGetEntrance()
    {
        $this->getAddressTo();
    }


}