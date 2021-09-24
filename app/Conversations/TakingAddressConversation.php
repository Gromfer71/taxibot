<?php

namespace App\Conversations;

use App\Conversations\MainMenu\MenuConversation;
use App\Models\AddressHistory;
use App\Models\Log;
use App\Services\Address;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;
use App\Traits\TakingAddressTrait;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Throwable;

/**
 *  Диалог для получения маршрута
 */
class TakingAddressConversation extends BaseAddressConversation
{
    use TakingAddressTrait;

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
                $this->bot->startConversation(new TaxiMenuConversation());
            },
            ButtonsStructure::ADDRESS_WILL_SAY_TO_DRIVER => function () {
                $this->_saveSecondAddressByText('');
                $this->bot->userStorage()->save(['second_address_will_say_to_driver_change_text_flag' => 1]);
                $this->bot->userStorage()->save(['second_address_will_say_to_driver_flag' => 1]);
                $this->bot->startConversation(new TaxiMenuConversation());
            }
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    /**
     * @return void
     */
    public function run()
    {
        $this->getAddress();
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
                $this->handleFirstAddress($address);
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

    public function streetNotFound()
    {
        $question = Question::create(
            $this->__('messages.not found address dorabotka bota'),
            $this->bot->getUser()->getId()
        );
        $question->addButtons(
            [
                Button::create($this->__('buttons.back'))->additionalParameters(
                    ['config' => ButtonsFormatterService::AS_INDICATED_MENU_FORMAT]
                )->value('back'),
                Button::create($this->__('buttons.go as indicated'))->value('go as indicated'),
                Button::create($this->__('buttons.exit to menu'))->value('exit to menu'),
            ]
        );

        return $this->ask(
            $question,
            function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'back') {
                        $this->getAddress();
                    } elseif ($answer->getValue() == 'exit to menu') {
                        $this->bot->startConversation(new MenuConversation());
                    } elseif ($answer->getValue() == 'go as indicated') {
                        $this->getEntrance();
                    }
                } else {
                    $this->_saveFirstAddress($answer->getText());
                    $this->getAddressAgain();
                }
            }
        );
    }

    public function getEntrance()
    {
        $question = Question::create($this->__('messages.give entrance'), $this->bot->getUser()->getId())
            ->addButtons([
                Button::create($this->__('buttons.no entrance'))->value('no entrance'),
                Button::create($this->__('buttons.exit'))->value('exit'),
            ]);

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() == 'exit') {
                    $this->bot->startConversation(new MenuConversation());
                } elseif ($answer->getValue() == 'no entrance') {
                    AddressHistory::newAddress(
                        $this->getUser()->id,
                        $this->bot->userStorage()->get('address'),
                        [
                            'lat' => $this->bot->userStorage()->get('lat'),
                            'lon' => $this->bot->userStorage()->get('lon')
                        ],
                        $this->bot->userStorage()->get('address_city')
                    );
                    $this->getAddressTo();
                }
            } else {
                $address = $this->bot->userStorage()->get('address') . ', *п ' . $answer->getText();
                $this->bot->userStorage()->save(['address' => $address]);
                AddressHistory::newAddress(
                    $this->getUser()->id,
                    $address,
                    [
                        'lat' => $this->bot->userStorage()->get('lat'),
                        'lon' => $this->bot->userStorage()->get('lon')
                    ],
                    $this->bot->userStorage()->get('address_city')
                );
                $this->getAddressTo();
            }
        });
    }

    public function getAddressTo()
    {
        $question = ComplexQuestion::createWithSimpleButtons($this->addAddressesToMessage($this->getAddressToMessage()),
            [ButtonsStructure::ADDRESS_WILL_SAY_TO_DRIVER, ButtonsStructure::EXIT],
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
                } else {
                    $this->_saveFirstAddress($answer->getText());
                    $this->getAddressAgain();
                }
            }
        );
    }

    public function streetNotFoundAddressTo()
    {

        $question = ComplexQuestion::createWithSimpleButtons(Translator::trans('messages.not found address dorabotka bota'),
            [ButtonsStructure::BACK, ButtonsStructure::GO_AS_INDICATED, ButtonsStructure::EXIT_TO_MENU],
            ['config' => ButtonsFormatterService::AS_INDICATED_MENU_FORMAT]);

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            $this->_saveSecondAddress($answer->getText());
            $this->getAddressToAgain();
        });
    }

    public function getAddressToAgain()
    {

        $addressesList = $this->getAddressesList(1);
        $question = ComplexQuestion::createWithSimpleButtons($this->addAddressesFromApi(Translator::trans('messages.give address again'), $addressesList),
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
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->getValue() == 'exit' && $answer->isInteractiveMessageReply()) {
                    $this->bot->startConversation(new MenuConversation());
                    return;
                }
                $address = Address::findByAnswer($addressesList, $answer);
                $this->_sayDebug('getAddressToAgain - address -' . json_encode($address, JSON_UNESCAPED_UNICODE));
                if ($address) {
                    if ($address['kind'] == 'street') {
                        $this->bot->userStorage()->save(
                            [
                                'address' => collect($this->bot->userStorage()->get('address'))->put(
                                    1,
                                    $address['street']
                                )->toArray()
                            ]
                        );
                        $this->forgetWriteHouse();
                        return;
                    }

                    AddressHistory::newAddress(
                        $this->getUser()->id,
                        Address::toString($address),
                        $address['coords'],
                        $address['city']
                    );
                    $this->_saveSecondAddress(
                        Address::toString($address),
                        $address['coords']['lat'],
                        $address['coords']['lon']
                    );
                    $this->bot->startConversation(new TaxiMenuConversation());
                } else {
                    $this->_saveSecondAddress($answer->getText());
                    $this->getAddressToAgain();
                }
            }
        );
    }

    public function forgetWriteHouse()
    {
        $this->_sayDebug('forgetWriteHouse');
        $question = Question::create($this->__('messages.forget write house'), $this->bot->getUser()->getId())
            ->addButtons([
                Button::create($this->__('buttons.exit'))->value('exit'),
            ]);

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() == 'exit') {
                    $this->bot->startConversation(new MenuConversation());
                    return;
                }
            }

            if (count((array)$this->bot->userStorage()->get('address')) > 1) {
                $this->_sayDebug('forgetWriteHouse - адрес куда');
                $addresses = collect($this->bot->userStorage()->get('address'));
                $lastAddress = $addresses->pop();
                $lastAddressWithEntrance = $lastAddress . $answer->getText();
                $addresses = $addresses->push($lastAddressWithEntrance);
                $this->bot->userStorage()->save(['address' => $addresses]);
                $this->_sayDebug('forgetWriteHouse - адреса ' . $addresses->toJson(JSON_UNESCAPED_UNICODE));
                $this->getAddressToAgain();
            } else {
                $this->_sayDebug('forgetWriteHouse - адрес откуда');
                $this->bot->userStorage()->save(
                    ['address' => $this->bot->userStorage()->get('address') . $answer->getText()]
                );
                $this->getAddressAgain();
            }
        });
    }


}