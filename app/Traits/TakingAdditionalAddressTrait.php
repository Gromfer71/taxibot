<?php

namespace App\Traits;

use App\Conversations\MainMenu\MenuConversation;
use App\Models\AddressHistory;
use App\Models\Log;
use App\Services\Address;
use App\Services\ButtonsFormatterService;
use App\Services\Options;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

trait TakingAdditionalAddressTrait
{
    public function addAdditionalAddress()
    {
        $this->_sayDebug('addAdditionalAddress');
        $message = $this->__('messages.add additional address');
        $message = $this->addAddressesToMessage($message);
        $question = Question::create($message, $this->bot->getUser()->getId());
        $question = $question->addButton(
            Button::create($this->__('buttons.back'))->value('back')->additionalParameters(['location' => 'addresses'])
        );
        $question = $this->_addAddressFavoriteButtons($question);
        $question = $this->_addAddressHistoryButtons($question);
        return $this->ask(
            $question,
            function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'back') {
                        $this->run();
                        return;
                    }
                }

                $address = $this->_getAddressFromHistoryByAnswer($answer);

                if ($address) {
                    if ($address['lat'] == 0 && Address::haveEndAddressFromStorageAndAllAdressesIsReal(
                            $this->bot->userStorage()
                        )) {
                        $this->bot->userStorage()->save(['additional_address_is_incorrect_change_text_flag' => 1]);
                    }
                    $this->_saveAnotherAddress($address->address, $address['lat'], $address['lon']);
                    $this->run();
                } else {
                    Log::newLogAnswer($this->bot, $answer);
                    $this->_saveAnotherAddress($answer);
                    $addressesList = collect(
                        Address::getAddresses(
                            $answer->getText(),
                            (new Options($this->bot->userStorage()))->getCities(),
                            $this->bot->userStorage()
                        )
                    );
                    if ($addressesList->isEmpty()) {
                        $this->streetNotFoundAdditionalAddress();
                        return;
                    } else {
                        $this->addAdditionalAddressAgain();
                        return;
                    }
                }
            }
        );
    }

    public function addAdditionalAddressAgain()
    {
        $this->_sayDebug('addAdditionalAddressAgain');


        $addressesList = collect(
            Address::getAddresses(
                collect($this->bot->userStorage()->get('address'))->last(),
                (new Options($this->bot->userStorage()))->getCities(),
                $this->bot->userStorage()
            )
        )->take(25)->values();
        $questionText = $this->addAddressesFromApi($this->__('messages.give address again'), $addressesList);
        $question = Question::create($questionText, $this->bot->getUser()->getId());
        $question->addButton(
            Button::create($this->__('buttons.back'))->value('back')->additionalParameters(['location' => 'addresses'])
        );
        if ($addressesList->isNotEmpty()) {
            foreach ($addressesList as $key => $address) {
                $question->addButton(
                    Button::create(Address::toString($address))->value(
                        Address::toString($address)
                    )->additionalParameters(['number' => $key + 1])
                );
            }
        } else {
            $this->streetNotFoundAdditionalAddress();
            return;
        }
        return $this->ask(
            $question,
            function (Answer $answer) use ($addressesList) {
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->getValue() == 'back') {
                    $this->_forgetLastAddress();
                    $this->run();
                    return;
                }
                $address = Address::findByAnswer($addressesList, $answer);
                $this->_sayDebug(
                    'addAdditionalAddressAgain - address -' . json_encode($address, JSON_UNESCAPED_UNICODE)
                );

                if ($address) {
                    if ($address['kind'] == 'street') {
                        $this->_saveAnotherAddress($address['street'], 0, 0, true);
                        $this->forgetWriteHouseAdditionalAddress();
                        return;
                    }

                    AddressHistory::newAddress(
                        $this->getUser()->id,
                        $answer->getText(),
                        $address['coords'],
                        $address['city']
                    );

                    $this->_saveAnotherAddress($answer, $address['coords']['lat'], $address['coords']['lon'], true);
                    $this->run();
                } else {
                    $this->_saveAnotherAddress($answer, 0, 0, true);
                    $this->addAdditionalAddressAgain();
                }
            }
        );
    }

    public function streetNotFoundAdditionalAddress()
    {
        $this->_sayDebug('streetNotFoundAdditionalAddress');
        $question = Question::create($this->__('messages.address not found'), $this->bot->getUser()->getId());
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
                        $this->_forgetLastAddress();
                        $this->addAdditionalAddress();
                        return;
                    } elseif ($answer->getValue() == 'exit to menu') {
                        $this->bot->startConversation(new MenuConversation());
                    } elseif ($answer->getValue() == 'go as indicated') {
                        $this->bot->userStorage()->save(['additional_address_is_incorrect_change_text_flag' => 1]);
                        $this->run();
                        return;
                    }
                } else {
                    $this->_saveAnotherAddress($answer, 0, 0, true);
                    $this->addAdditionalAddressAgain();
                }
            }
        );
    }

    public function forgetWriteHouseAdditionalAddress()
    {
        $this->_sayDebug('forgetWriteHouseAdditionalAddress');
        $question = Question::create($this->__('messages.forget write house'), $this->bot->getUser()->getId())
            ->addButtons([
                             Button::create($this->__('buttons.exit'))->value('exit'),
                         ]);

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() == 'exit') {
                    $this->_forgetLastAddress();
                    $this->bot->startConversation(new MenuConversation());
                    return;
                }
            }


            $this->_sayDebug('forgetWriteHouseAdditionalAddress - дополнительный адрес');

            $this->_addToLastAnotherAddress($answer);

            $this->addAdditionalAddressAgain();
        });
    }
}