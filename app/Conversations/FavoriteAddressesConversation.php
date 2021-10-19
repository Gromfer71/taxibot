<?php


namespace App\Conversations;

use App\Models\FavoriteAddress;
use App\Models\Log;
use App\Models\User;
use App\Services\Address;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Options;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

class FavoriteAddressesConversation extends BaseAddressConversation
{
    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => 'App\Conversations\Settings\SettingsConversation',
            ButtonsStructure::ADD_ADDRESS => 'addAddress',
            ButtonsStructure::EXIT_TO_MENU => 'App\Conversations\MainMenu\MenuConversation',
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    public function run()
    {
        $question = ComplexQuestion::createWithSimpleButtons(Translator::trans('messages.favorite addresses menu'),
                                                             [ButtonsStructure::BACK, ButtonsStructure::ADD_ADDRESS]
        );

        foreach ($this->getUser()->favoriteAddresses as $address) {
            $question->addButton(
                Button::create($address->name . ' (' . $address->address . ')')->value($address->name)
            );
        }

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            if ($answer->getValue()) {
                $this->bot->userStorage()->save(['address_name' => $answer->getValue()]);
                $this->addressMenu();
            } else {
                $this->run();
            }
        });
    }

    public function addressMenu()
    {
        $question = Question::create($this->__('messages.favorite address menu'))->addButtons(
            [
                Button::create($this->__('buttons.back'))->value('back'),
                Button::create($this->__('buttons.delete'))->value('delete'),
            ]
        );

        return $this->ask($question, function (Answer $answer) {
            if ($answer->getValue() == 'back') {
                $this->run();
            } elseif ($answer->getValue() == 'delete') {
                $address = FavoriteAddress::where([
                                                      'user_id' => $this->getUser()->id,
                                                      'name' => $this->bot->userStorage()->get('address_name')
                                                  ])->first();
                if ($address) {
                    $address->delete();
                }
                $this->run();
            }
        });
    }

    public function addAddress()
    {
        $options = new Options($this->bot->userStorage());
        $crewGroupId = $options->getCrewGroupIdFromCity(User::find($this->bot->getUser()->getId())->city ?? null);
        $district = $options->getDistrictFromCity(User::find($this->bot->getUser()->getId())->city ?? null);
        $this->bot->userStorage()->save(['crew_group_id' => $crewGroupId]);
        $this->bot->userStorage()->save(['district' => $district]);
        $this->bot->userStorage()->save(['city' => User::find($this->bot->getUser()->getId())->city]);
        $questionText = $this->addAddressesToMessageOnlyFromHistory(
            $this->__('messages.give me your favorite address')
        );
        $question = Question::create($questionText, $this->bot->getUser()->getId())
            ->addButton(
                Button::create($this->__('buttons.exit'))->value('exit')->additionalParameters(
                    ['location' => 'addresses']
                )
            );
        $question = $this->_addAddressHistoryButtons($question, true);

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
            if ($answer->getValue() == 'exit') {
                $this->run();
                return;
            }
            $address = $this->_getAddressFromHistoryByAnswer($answer);

            if ($address) {
                if ($address['city'] == '') {
                    $crew_group_id = false;
                } else {
                    $crew_group_id = $this->_getCrewGroupIdByCity($address['city']);
                }
                if ($address['lat'] == 0) {
                    $this->bot->userStorage()->save(['first_address_from_history_incorrect' => 1]);
                }

                $this->_saveFirstAddress(
                    $address->address,
                    $crew_group_id,
                    $address['lat'],
                    $address['lon'],
                    $address['city']
                );
                if ($this->_hasEntrance($address->address)) {
                    $this->getAddressName();
                } else {
                    $this->getEntrance();
                }
            } else {
                $this->_saveFirstAddress($answer->getText());

                $addressesList = collect(
                    Address::getAddresses(
                        $this->bot->userStorage()->get('address'),
                        (new Options($this->bot->userStorage()))->getCities(),
                        $this->bot->userStorage()
                    )
                );
                if ($addressesList->isEmpty()) {
                    $this->streetNotFound();
                } else {
                    $this->getAddressAgain();
                }
            }
        });
    }

    public function getAddressAgain()
    {
        $this->_sayDebug('getAddressAgain');

        $addressesList = collect(
            Address::getAddresses(
                $this->bot->userStorage()->get('address'),
                (new Options($this->bot->userStorage()))->getCities(),
                $this->bot->userStorage()
            )
        )->take(25);
        $questionText = $this->addAddressesFromApi($this->__('messages.give favorite address again'), $addressesList);
        $question = Question::create($questionText, $this->bot->getUser()->getId());
        $this->_sayDebug('getAddressAgain2');
        $question->addButton(
            Button::create($this->__('buttons.exit'))->value('exit')->additionalParameters(['location' => 'addresses'])
        );
        if ($addressesList->isNotEmpty()) {
            $this->_sayDebug('addressesList->isNotEmpty');
            foreach ($addressesList as $key => $address) {
                $question->addButton(
                    Button::create(Address::toString($address))->value(
                        Address::toString($address)
                    )->additionalParameters(['number' => $key + 1])
                );
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
                    $this->run();
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
                    $this->_saveFirstAddress(
                        $answer->getText(),
                        $crew_group_id,
                        $address['coords']['lat'],
                        $address['coords']['lon'],
                        $address['city']
                    );
                    $this->getEntrance();
                } else {
                    $this->_saveFirstAddress($answer->getText());
                    $this->getAddressAgain();
                }
            }
        );
    }

    public function streetNotFound()
    {
        $question = Question::create($this->__('messages.not found favorite address'), $this->bot->getUser()->getId());
        $question->addButtons(
            [
                Button::create($this->__('buttons.back'))->additionalParameters(
                    ['config' => ButtonsFormatterService::AS_INDICATED_MENU_FORMAT]
                )->value('back'),
                Button::create($this->__('buttons.save as written'))->value('save as written'),
            ]
        );

        return $this->ask(
            $question,
            function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'back') {
                        $this->addAddress();
                    } elseif ($answer->getValue() == 'save as written') {
                        $this->getEntrance();
                    }
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
        $question = Question::create(
            $this->__('messages.forget write house in favorite address'),
            $this->bot->getUser()->getId()
        )
            ->addButtons([
                             Button::create($this->__('buttons.exit'))->value('exit'),
                         ]);

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() == 'exit') {
                    $this->run();
                    return;
                }
            }

            $this->_sayDebug('forgetWriteHouse - адрес откуда');
            $this->bot->userStorage()->save(
                ['address' => $this->bot->userStorage()->get('address') . $answer->getText()]
            );
            $this->getAddressAgain();
        });
    }

    public function getEntrance()
    {
        $question = Question::create(
            $this->__('messages.give entrance in favorite address'),
            $this->bot->getUser()->getId()
        )
            ->addButtons([
                             Button::create($this->__('buttons.no entrance'))->value('no entrance'),
                             Button::create($this->__('buttons.exit'))->value('exit'),
                         ]);

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
            if ($answer->getValue() == 'exit') {
                $this->run();
            } elseif ($answer->getValue() == 'no entrance') {
                $this->getAddressName();
            } else {
                $address = $this->bot->userStorage()->get('address') . ', *п ' . $answer->getText();
                $this->bot->userStorage()->save(['address' => $address]);
                $this->_sayDebug('getAddressName');
                $this->getAddressName();
            }
        });
    }

    public function getAddressName()
    {
        $question = Question::create($this->__('messages.get address name'))
            ->addButton(Button::create($this->__('buttons.exit'))->value('exit'));

        return $this->ask($question, function (Answer $answer) {
            if ($answer->getValue() == 'exit') {
                $this->run();
            } else {
                if (mb_strlen($answer->getText()) > 32) {
                    $this->say($this->__('messages.address name too long'));
                    $this->getAddressName();
                } else {
                    $this->bot->userStorage()->save(['address_name' => $answer->getText()]);
                    $question = Question::create(
                        $this->__(
                            'messages.favorite address',
                            ['name' => $answer->getText(), 'address' => $this->bot->userStorage()->get('address')]
                        )
                    )->addButtons(
                        [
                            Button::create($this->__('buttons.save'))->value('save'),
                            Button::create($this->__('buttons.cancel'))->value('cancel'),
                        ]
                    );

                    return $this->ask($question, function (Answer $answer) {
                        if ($answer->getValue() == 'save') {
                            $this->_sayDebug(json_encode($this->bot->userStorage()->get('address')));
                            $address = Address::subStrAddress($this->bot->userStorage()->get('address'));
                            FavoriteAddress::create(
                                [
                                    'user_id' => $this->getUser()->id,
                                    'address' => $address,
                                    'name' => $this->bot->userStorage()->get('address_name'),
                                    'lat' => $this->bot->userStorage()->get('lat'),
                                    'lon' => $this->bot->userStorage()->get('lon'),
                                    'city' => $this->bot->userStorage()->get('address_city'),

                                ]
                            );
                            $this->run();
                        } elseif ($answer->getValue() == 'cancel') {
                            $this->run();
                        } else {
                            $this->getAddressName();
                        }
                    });
                }
            }
        });
    }
}