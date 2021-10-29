<?php

namespace App\Conversations;

use App\Models\AddressHistory;
use App\Services\Address;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Options;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class TakingAdditionalAddressConversation extends BaseAddressConversation
{
    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => function () {
                $this->_forgetLastAddress();
                $this->addAdditionalAddress();
            },
            ButtonsStructure::GO_AS_INDICATED => function () {
                $this->bot->userStorage()->save(['additional_address_is_incorrect_change_text_flag' => 1]);
                $this->bot->startConversation(new TaxiMenuConversation());
            },
            ButtonsStructure::EXIT_TO_MENU => 'App\Conversations\TaxiMenuConversation',

        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    public function run()
    {
        $this->addAdditionalAddress();
    }

    public function addAdditionalAddress()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            $this->addAddressesToMessage(Translator::trans('messages.add additional address')),
            [ButtonsStructure::BACK], ['location' => 'addresses']
        );

        $question = $this->_addAddressFavoriteButtons($question);
        $question = $this->_addAddressHistoryButtons($question);

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction(
                $answer->getValue(),
                [ButtonsStructure::BACK => 'App\Conversations\TaxiMenuConversation']
            );

            $address = $this->_getAddressFromHistoryByAnswer($answer);
            if ($address) {
                $this->saveAnotherAddressFromList($address);
            } else {
                $this->_saveAnotherAddress($answer);
                $addressesList = collect(
                    Address::getAddresses(
                        $answer->getText(),
                        (new Options())->getCities(),
                        $this->bot->userStorage()
                    )
                );
                if ($addressesList->isEmpty()) {
                    $this->streetNotFoundAdditionalAddress();
                } else {
                    $this->addAdditionalAddressAgain();
                }
            }
        });
    }

    public function addAdditionalAddressAgain()
    {
        $addressesList = collect(
            Address::getAddresses(
                collect($this->bot->userStorage()->get('address'))->last(),
                (new Options())->getCities(),
                $this->bot->userStorage()
            )
        )->take(Address::MAX_ADDRESSES_COUNT)->values();
        $question = ComplexQuestion::createWithSimpleButtons(
            $this->addAddressesFromApi(Translator::trans('messages.give address again'), $addressesList),
            [ButtonsStructure::BACK],
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
            $this->streetNotFoundAdditionalAddress();
            die();
        }

        return $this->ask($question, function (Answer $answer) use ($addressesList) {
            $this->handleAction($answer->getValue());
            $address = Address::findByAnswer($addressesList, $answer);
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
                $this->bot->startConversation(new TaxiMenuConversation());
            } else {
                $this->_saveAnotherAddress($answer, 0, 0, true);
                $this->addAdditionalAddressAgain();
            }
        });
    }

    public function streetNotFoundAdditionalAddress()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.address not found'),
            [
                ButtonsStructure::BACK,
                ButtonsStructure::GO_AS_INDICATED,
                ButtonsStructure::EXIT_TO_MENU
            ],
            ['config' => ButtonsFormatterService::AS_INDICATED_MENU_FORMAT]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            $this->_saveAnotherAddress($answer, 0, 0, true);
            $this->addAdditionalAddressAgain();
        });
    }


    public function forgetWriteHouseAdditionalAddress()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.forget write house'),
            [ButtonsStructure::EXIT_TO_MENU]
        );
        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            $this->_addToLastAnotherAddress($answer);
            $this->addAdditionalAddressAgain();
        });
    }

    private function saveAnotherAddressFromList($address)
    {
        if ($address['lat'] == 0 && Address::haveEndAddressFromStorageAndAllAdressesIsReal(
                $this->bot->userStorage()
            )) {
            $this->bot->userStorage()->save(['additional_address_is_incorrect_change_text_flag' => 1]);
        }
        $this->_saveAnotherAddress($address->address, $address['lat'], $address['lon']);
        $this->bot->startConversation(new TaxiMenuConversation());
    }
}