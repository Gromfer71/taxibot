<?php


namespace App\Conversations;


use App\Models\AddressHistory;
use App\Models\FavoriteAddress;
use App\Models\Log;
use App\Models\User;
use App\Services\Address;
use App\Services\ButtonsFormatterService;
use App\Services\Options;
use BotMan\BotMan\Exceptions\Base\BotManException;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Facades\App;

class TakingAddressConversation extends BaseAddressConversation
{

    public function getAddress()
    {
        $options = new Options($this->bot->userStorage());
        $crewGroupId = $options->getCrewGroupIdFromCity(User::find($this->bot->getUser()->getId())->city ?? null);
        $district = $options->getDistrictFromCity(User::find($this->bot->getUser()->getId())->city ?? null);
        $this->bot->userStorage()->save(['crew_group_id' => $crewGroupId]);
        $this->bot->userStorage()->save(['district' => $district]);
        $this->bot->userStorage()->save(['city' => User::find($this->bot->getUser()->getId())->city]);
        $questionText = trans('messages.give me your address');
        if(property_exists($this->bot->getDriver(), 'needToAddAddressesToMessage')) {
            $this->_sayDebug('property exists');
            foreach ($this->getUser()->favoriteAddresses as $key => $address) {
                $questionText .= $key .'⭐️ ' . $address->address . '\n';
            }

            if(!isset($key)) $key = 0;

            foreach ($this->getUser()->favoriteAddresses as $historyAddressKey => $address) {
                $questionText .= $historyAddressKey + $key . $address->address . '\n';
            }

        }


        $question = Question::create($questionText, $this->bot->getUser()->getId())
            ->addButton(Button::create(trans('buttons.exit'))->value('exit')->additionalParameters(['location' => 'addresses']));


        $question = $this->_addAddressFavoriteButtons($question);
        $question = $this->_addAddressHistoryButtons($question);


        return $this->ask($question, function (Answer $answer) {

            Log::newLogAnswer($this->bot, $answer);
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() == 'exit') {
                    $this->bot->startConversation(new MenuConversation());
                    return;
                }
            }

                $address = $this->_getAddressFromHistoryByAnswer($answer);
                if ($address) {
                    if ($address['city'] == '') {
                        $crew_group_id = false;
                    } else {
                        $crew_group_id = $this->_getCrewGroupIdByCity($address['city']);
                    }
                    if ($address['lat'] == 0) $this->bot->userStorage()->save(['first_address_from_history_incorrect' => 1]);

                    $this->_saveFirstAddress($address->address, $crew_group_id, $address['lat'], $address['lon'], $address['city']);
                    if ($this->_hasEntrance($address->address)) {
                        $this->getAddressTo();
                    } else {
                        $this->getEntrance();
                    }
                } else {
                $this->_saveFirstAddress($answer->getText());
                $addressesList = collect(Address::getAddresses($this->bot->userStorage()->get('address'), (new Options($this->bot->userStorage()))->getCities(), $this->bot->userStorage()));
                if ($addressesList->isEmpty()) {
                    $this->streetNotFound();
                } else {
                    $this->getAddressAgain();
                }
            }
        }
        );
    }

    public
    function streetNotFound()
    {
        $question = Question::create(trans('messages.not found address dorabotka bota'), $this->bot->getUser()->getId());
        $question->addButtons(
            [
                Button::create(trans('buttons.back'))->additionalParameters(['config' => ButtonsFormatterService::AS_INDICATED_MENU_FORMAT])->value('back'),
                Button::create(trans('buttons.go as indicated'))->value('go as indicated'),
                Button::create(trans('buttons.exit to menu'))->value('exit to menu'),
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

    public function getAddressAgain()
    {
        $this->_sayDebug('getAddressAgain');
        $question = Question::create(trans('messages.give address again'), $this->bot->getUser()->getId());
        $addressesList = collect(Address::getAddresses($this->bot->userStorage()->get('address'), (new Options($this->bot->userStorage()))->getCities(), $this->bot->userStorage()));
        $this->_sayDebug('getAddressAgain2');
        $question->addButton(Button::create(trans('buttons.exit'))->value('exit'));
        if ($addressesList->isNotEmpty()) {
            $this->_sayDebug('addressesList->isNotEmpty');
            $addressesList = $addressesList->take(25);
            foreach ($addressesList as $address) {
                $question->addButton(Button::create(Address::toString($address))->value(Address::toString($address)));
            }
        } else {
            $this->_sayDebug('addressesList->isEmpty');
            $this->streetNotFound();
            return;
        }

        $this->_sayDebug('getAddressAgain3');
        return $this->ask(
            $question,
            function (Answer $answer) use ($addressesList) {
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->getValue() == 'exit' && $answer->isInteractiveMessageReply()) {
                    $this->bot->startConversation(new MenuConversation());
                    return;
                }

                $address = Address::findByAnswer($addressesList, $answer);

                if ($address) {
                    if ($address['kind'] == 'street') {
                        $this->bot->userStorage()->save(['address' => $address['street']]);
                        $this->forgetWriteHouse();
                        return;
                    }
                    $crew_group_id = $this->_getCrewGroupIdByCity($address['city']);
                    $this->_saveFirstAddress(Address::toString($address), $crew_group_id, $address['coords']['lat'], $address['coords']['lon'], $address['city']);
                    $this->getEntrance();
                } else {
                    $this->_saveFirstAddress($answer->getText());
                    $this->getAddressAgain();
                }
            }
        );
    }

    public function forgetWriteHouse()
    {
        $this->_sayDebug('forgetWriteHouse');
        $question = Question::create(trans('messages.forget write house'), $this->bot->getUser()->getId())
            ->addButtons([
                Button::create(trans('buttons.exit'))->value('exit'),
            ]);;

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

    public function getEntrance()
    {
        $question = Question::create(trans('messages.give entrance'), $this->bot->getUser()->getId())
            ->addButtons([
                Button::create(trans('buttons.no entrance'))->value('no entrance'),
                Button::create(trans('buttons.exit'))->value('exit'),
            ]);

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() == 'exit') {
                    $this->bot->startConversation(new MenuConversation());
                } elseif ($answer->getValue() == 'no entrance') {
                    AddressHistory::newAddress($this->getUser()->id, $this->bot->userStorage()->get('address'), ['lat' => $this->bot->userStorage()->get('lat'), 'lon' => $this->bot->userStorage()->get('lon')], $this->bot->userStorage()->get('address_city'));
                    $this->getAddressTo();
                }
            } else {
                $address = $this->bot->userStorage()->get('address') . ', *п ' . $answer->getText();
                $this->bot->userStorage()->save(['address' => $address]);
                AddressHistory::newAddress($this->getUser()->id, $address, ['lat' => $this->bot->userStorage()->get('lat'), 'lon' => $this->bot->userStorage()->get('lon')], $this->bot->userStorage()->get('address_city'));
                $this->getAddressTo();
            }
        });
    }

    public
    function streetNotFoundAddressTo()
    {
        $this->_sayDebug('streetNotFoundAddressTo');
        $question = Question::create(trans('messages.not found address dorabotka bota'), $this->bot->getUser()->getId());
        $question->addButtons(
            [
                Button::create(trans('buttons.back'))->additionalParameters(['config' => ButtonsFormatterService::AS_INDICATED_MENU_FORMAT])->value('back'),
                Button::create(trans('buttons.go as indicated'))->value('go as indicated'),
                Button::create(trans('buttons.exit to menu'))->value('exit to menu'),
            ]
        );

        return $this->ask(
            $question,
            function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'back') {
                        $this->getAddressTo();
                        return;
                    } elseif ($answer->getValue() == 'exit to menu') {
                        $this->bot->startConversation(new MenuConversation());
                    } elseif ($answer->getValue() == 'go as indicated') {
                        AddressHistory::newAddress($this->getUser()->id, collect($this->bot->userStorage()->get('address'))->last(), ['lat' => 0, 'lon' => 0], $this->bot->userStorage()->get('address_city'));
                        $this->bot->startConversation(new TaxiMenuConversation());
                        return;
                    }
                } else {
                    $this->_saveSecondAddress($answer->getText());
                    $this->getAddressToAgain();
                }
            }
        );
    }

    public
    function getAddressTo()
    {
        $this->_sayDebug('getAddressTo');
        if (Address::haveFirstAddressFromStorageAndFirstAdressesIsReal($this->bot->userStorage())) {
            $message = trans('messages.user address') . collect($this->bot->userStorage()->get('address'))->first() . ' ' . trans('messages.give me end address');
        } else {
            $message = trans('messages.ask for second address if first address incorrect', ['address' => collect($this->bot->userStorage()->get('address'))->first()]);
        }

        $question = Question::create($message, $this->bot->getUser()->getId())
            ->addButtons(
                [
                    Button::create(trans('buttons.address will say to driver'))->value('address will say to driver'),
                    Button::create(trans('buttons.exit'))->value('exit'),
                ]
            );
        $question = $this->_addAddressFavoriteButtons($question);
        $question = $this->_addAddressHistoryButtons($question);

        return $this->ask(
            $question,
            function (Answer $answer) {
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'address will say to driver') {
                        $this->_sayDebug('address will say to driver');
                        $this->_saveSecondAddressByText('');
                        $this->bot->userStorage()->save(['second_address_will_say_to_driver_change_text_flag' => 1]);
                        $this->bot->userStorage()->save(['second_address_will_say_to_driver_flag' => 1]);
                        $this->bot->startConversation(new TaxiMenuConversation());
                        return;
                    } elseif ($answer->getValue() == 'exit') {
                        $this->bot->startConversation(new MenuConversation());
                        return;
                    }
                }


                    $address = $this->_getAddressFromHistoryByAnswer($answer);
                    if ($address) {
                        $this->_saveSecondAddress($address->address, $address['lat'], $address['lon']);
                        if ($address['lat'] == 0) $this->bot->userStorage()->save(['second_address_from_history_incorrect' => 1]);
                        if ($address['lat'] == 0) $this->bot->userStorage()->save(['second_address_from_history_incorrect_change_text_flag' => 1]);
                        $this->bot->startConversation(new TaxiMenuConversation());
                        return;
                    } else {
                    $this->_saveSecondAddress($answer->getText());
                    $addressesList = collect(Address::getAddresses($answer->getText(), (new Options($this->bot->userStorage()))->getCities(), $this->bot->userStorage()));
                    if ($addressesList->isEmpty()) {
                        $this->streetNotFoundAddressTo();
                        return;
                    } else {
                        $this->getAddressToAgain();
                        return;
                    }
                }
            }
        );
    }

    public
    function getAddressToAgain()
    {
        $this->_sayDebug('getAddressToAgain');
        $question = Question::create(trans('messages.give address again'), $this->bot->getUser()->getId());
        $addressesList = collect(Address::getAddresses(collect($this->bot->userStorage()->get('address'))->get(1), (new Options($this->bot->userStorage()))->getCities(), $this->bot->userStorage()));

        $question->addButton(Button::create(trans('buttons.exit'))->value('exit'));
        if ($addressesList->isNotEmpty()) {
            $addressesList = $addressesList->take(25);
            foreach ($addressesList as $address) {
                $question->addButton(Button::create(Address::toString($address))->value(Address::toString($address)));
            }
        } else {
            $this->streetNotFoundAddressTo();
            return;
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
                                'address' => collect($this->bot->userStorage()->get('address'))->put(1,
                                    $address['street']
                                )->toArray()
                            ]);
                        $this->forgetWriteHouse();
                        return;
                    }

                    AddressHistory::newAddress($this->getUser()->id, Address::toString($address), $address['coords'], $address['city']);
                    $this->_saveSecondAddress(Address::toString($address), $address['coords']['lat'], $address['coords']['lon']);
                    $this->bot->startConversation(new TaxiMenuConversation());
                } else {
                    $this->_saveSecondAddress($answer->getText());
                    $this->getAddressToAgain();
                }
            });
    }

    public
    function run()
    {
        $this->getAddress();
    }
}