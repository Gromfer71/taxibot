<?php

namespace App\Conversations;

use App\Models\AddressHistory;
use App\Services\Address;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;
use App\Traits\TakingAddressTrait;
use BotMan\BotMan\Messages\Incoming\Answer;
use Throwable;

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
        $this->getAddress();
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
                AddressHistory::newAddress(
                    $this->getUser()->id,
                    collect($this->bot->userStorage()->get('address'))->last(),
                    ['lat' => 0, 'lon' => 0],
                    $this->bot->userStorage()->get('address_city')
                );
                $this->bot->startConversation(new $this->conversationAfterTakeAddress());
            },
            ButtonsStructure::ADDRESS_WILL_SAY_TO_DRIVER => function () {
                $this->_saveSecondAddressByText('');
                $this->saveToStorage(['second_address_will_say_to_driver_change_text_flag' => 1]);
                $this->saveToStorage(['second_address_will_say_to_driver_flag' => 1]);
                $this->bot->startConversation(new $this->conversationAfterTakeAddress());
            },
            ButtonsStructure::NO_ENTRANCE => function () {
                $this->createAddressHistory($this->getFromStorage('address'));
                $this->getAddressTo();
            }
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    /**
     * Ввод начального адреса пользователя
     *
     * @return TakingAddressConversation
     */
    public function getAddress(): TakingAddressConversation
    {
        $this->saveCityInformation();

        $question = ComplexQuestion::createWithSimpleButtons(
            $this->addAddressesToMessage(Translator::trans('messages.give me your address')),
            [ButtonsStructure::EXIT],
            ['location' => 'addresses']
        );
        // Добавляем в кнопки избранные адреса и адреса из истории
        $question = $this->_addAddressFavoriteButtons($question);
        $question = $this->_addAddressHistoryButtons($question);

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            // Определяем ввод пользователя - это выбранный адрес из списка или введенный вручную
            $address = $this->_getAddressFromHistoryByAnswer($answer);
            if ($address) {
                // если выбранный, то сохраняем его и идем дальше
                $this->saveFirstAddress($address);
                if ($this->_hasEntrance($address->address)) {
                    $this->getAddressTo();
                } else {
                    $this->getEntrance();
                }
            } else {
                // если введенный, то сохраняем его и выводим список похожих адресов
                $this->_saveFirstAddress($answer->getText());
                $addressesList = $this->getAddressesList();
                if ($addressesList->isEmpty()) {
                    $this->streetNotFound();
                } else {
                    $this->getAddressAgain();
                }
            }
        });
    }

    public function getAddressTo()
    {
        $question = ComplexQuestion::createWithSimpleButtons($this->addAddressesToMessage($this->getAddressToMessage()),
                                                             [
                                                                 ButtonsStructure::ADDRESS_WILL_SAY_TO_DRIVER,
                                                                 ButtonsStructure::EXIT
                                                             ],
                                                             ['location' => 'addresses']
        );
        $question = $this->_addAddressFavoriteButtons($question);
        $question = $this->_addAddressHistoryButtons($question);

        return $this->ask(
            $question,
            function (Answer $answer) {
                $this->handleAction($answer->getValue());
                $this->handleSecondAddress($answer);
            }
        );
    }

    /**
     * Меню выбора первого адреса маршрута, после ввода адреса пользователем. Пользователь выбирает из предложенного списка.
     * В зависимости от выбранного адреса бот отправляет в сценарий, если выбрана только улица без номера дома, либо если всё
     * хорошо, то на ввод подъезда. Либо пользователь просто вводит первый адрес снова, тогда он попадает на этот же диалог.
     *
     * @return TakingAddressConversation
     * @throws Throwable
     */
    public function getAddressAgain(): TakingAddressConversation
    {
        $addressesList = $this->getAddressesList();
        $question = ComplexQuestion::createWithSimpleButtons(
            $this->addAddressesFromApi(Translator::trans('messages.give address again'), $addressesList),
            [ButtonsStructure::EXIT],
            ['location' => 'addresses']
        );

        $question = ComplexQuestion::setAddressButtons(
            $question,
            $addressesList->map(function ($address) {
                return Address::toString($address);
            })
        );

        return $this->ask(
            $question,
            function (Answer $answer) use ($addressesList) {
                $this->handleAction($answer->getValue());
                $address = Address::findByAnswer($addressesList, $answer);
                if ($address) {
                    $this->handleFirstChosenAddress($address);
                    $this->getEntrance();
                } else {
                    $this->_saveFirstAddress($answer->getText());
                    $this->getAddressAgain();
                }
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
        }

        return $this->ask(
            $question,
            function (Answer $answer) use ($addressesList) {
                $this->handleAction($answer->getValue());
                $this->handleSecondAddressAgain($addressesList, $answer);
            }
        );
    }

    public function getEntrance()
    {
        $question = ComplexQuestion::createWithSimpleButtons(Translator::trans('messages.give entrance'),
                                                             [ButtonsStructure::NO_ENTRANCE, ButtonsStructure::EXIT]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            $this->addEntranceToAddress($answer->getText());
            $this->createAddressHistory($this->getFromStorage('address'));
            $this->getAddressTo();
        });
    }

    public function streetNotFound()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.not found address dorabotka bota'),
            [ButtonsStructure::BACK, ButtonsStructure::GO_AS_INDICATED, ButtonsStructure::EXIT_TO_MENU],
            ['config' => ButtonsFormatterService::AS_INDICATED_MENU_FORMAT]
        );

        return $this->ask(
            $question,
            function (Answer $answer) {
                $this->handleAction(
                    $answer->getValue(),
                    [ButtonsStructure::BACK => 'getAddress', ButtonsStructure::GO_AS_INDICATED => 'getEntrance']
                );
                $this->_saveFirstAddress($answer->getText());
                $this->getAddressAgain();
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
            $this->handleAction($answer->getValue());
            $this->_saveSecondAddress($answer->getText());
            $this->getAddressToAgain();
        });
    }

    public function forgetWriteHouse()
    {
        $question = ComplexQuestion::createWithSimpleButtons(Translator::trans('messages.forget write house'),
                                                             [ButtonsStructure::EXIT]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());

            if (count((array)$this->bot->userStorage()->get('address')) > 1) {
                $this->handleForgetWriteHouse($answer->getText());
                $this->getAddressToAgain();
            } else {
                $this->bot->userStorage()->save(
                    ['address' => $this->bot->userStorage()->get('address') . $answer->getText()]
                );
                $this->getAddressAgain();
            }
        });
    }


}