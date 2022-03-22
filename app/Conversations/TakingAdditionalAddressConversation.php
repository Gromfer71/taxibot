<?php

namespace App\Conversations;

use App\Conversations\FavoriteRoutes\AddedRouteMenuConversation;
use App\Conversations\MainMenu\MenuConversation;
use App\Models\AddressHistory;
use App\Services\Address;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Options;
use App\Services\OrderApiService;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class TakingAdditionalAddressConversation extends BaseAddressConversation
{
    public function getActions($replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => function () {
                $this->_forgetLastAddress();
                $this->addAdditionalAddress();
            },
            ButtonsStructure::GO_AS_INDICATED => function () {
                $this->bot->userStorage()->save(['additional_address_is_incorrect_change_text_flag' => 1]);
                $this->exit();
            },
            ButtonsStructure::EXIT_TO_MENU => MenuConversation::class,

        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    public function run()
    {
        $this->addAdditionalAddress();
    }

    public function exit()
    {
        if ($this->isAdditionalAddressForFavoriteRoute()) {
            $this->bot->startConversation(new AddedRouteMenuConversation());
        } else {
            $this->bot->startConversation(new TaxiMenuConversation());
        }
    }

    public function isAdditionalAddressForFavoriteRoute()
    {
        return (bool)$this->bot->userStorage()->get('additional_address_for_favorite_route');
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
            if ($this->handleAction(
                $answer,
                [
                    ButtonsStructure::BACK => $this->isAdditionalAddressForFavoriteRoute() ? 'exit' : TaxiMenuConversation::class
                ]
            )) {
                return;
            }

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
            return;
        }

        return $this->ask($question, function (Answer $answer) use ($addressesList) {
            if ($this->handleAction($answer)) {
                return;
            }
            $address = Address::findByAnswer($addressesList, $answer);
            if ($address) {
                if ($address['kind'] == 'street') {
                    $this->_saveAnotherAddress($address['street'], 0, 0, true);
                    $this->forgetWriteHouseAdditionalAddress();
                    return;
                }

                if ($this->needToSaveAddressToHistory()) {
                    AddressHistory::newAddress(
                        $this->getUser()->id,
                        $answer->getText(),
                        $address['coords'],
                        $address['city']
                    );
                }
                OrderApiService::sendDriverLocation($this->getBot(),  $address['coords']['lat'], $address['coords']['lon']);
                $this->_saveAnotherAddress($answer, $address['coords']['lat'], $address['coords']['lon'], true);
                $this->exit();
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
            if ($this->handleAction($answer)) {
                return;
            }
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
            if ($this->handleAction($answer)) {
                return;
            }
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
        OrderApiService::sendDriverLocation($this->getBot(),  $address['lat'], $address['lon']);
        $this->_saveAnotherAddress($address->address, $address['lat'], $address['lon']);
        $this->exit();
    }
}