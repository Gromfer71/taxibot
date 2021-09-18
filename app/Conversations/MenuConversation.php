<?php

namespace App\Conversations;

use App\Models\AddressHistory;
use App\Models\Log;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\ButtonsFormatterService;
use App\Services\Options;
use App\Services\OrderApiService;
use Barryvdh\TranslationManager\Models\LangPackage;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

/**
 * Главное меню бота
 */
class MenuConversation extends BaseConversation
{
    /**
     * Главное меню
     *
     * @param false $withoutMessage
     * @return \App\Conversations\MenuConversation|void
     */
    public function menu($withoutMessage = false)
    {
        $user = User::find($this->bot->getUser()->getId());

        if (!$user) {
            $this->bot->startConversation(new StartConversation());
            return;
        } elseif ($user->isBlocked) {
            $this->say($this->__('messages.you are blocked'));
            return;
        } elseif (!$user->phone) {
            $this->confirmPhone();
            return;
        } elseif (!$user->city) {
            $this->changeCity();
            return;
        }


        $this->bot->userStorage()->delete();
        $this->checkConfig();
        OrderHistory::cancelAllOrders($this->getUser()->id, $this->bot->getDriver()->getName());


        $question = Question::create(
            $withoutMessage ? '' : $this->__('messages.choose menu'),
            $this->bot->getUser()->getId()
        )
            ->addButtons([
                             Button::create($this->__('buttons.take taxi'))->value('take taxi')->additionalParameters(
                                 ['config' => ButtonsFormatterService::MAIN_MENU_FORMAT]
                             ),
                             Button::create($this->__('buttons.request call'))->value('request call'),
                             Button::create($this->__('buttons.change phone number'))->value('change phone number'),
                             Button::create($this->__('buttons.change city'))->value('change city'),
                             Button::create($this->__('buttons.price list'))->value('price list'),
                             Button::create($this->__('buttons.all about bonuses'))->value('all about bonuses'),
                             Button::create($this->__('buttons.address history menu'))->value('address history menu'),
                             Button::create($this->__('buttons.favorite addresses menu'))->value(
                                 'favorite addresses menu'
                             )
                         ]);

        return $this->ask($question, function (Answer $answer) use ($user) {
            Log::newLogAnswer($this->bot, $answer);

            if ($user->isBlocked) {
                $this->say($this->__('messages.you are blocked'));
                return;
            }

            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() == 'take taxi') {
                    $user = User::find($this->bot->getUser()->getId());
                    if (!$user) {
                        $this->bot->startConversation(new StartConversation());
                        return;
                    } elseif (!$user->phone) {
                        $this->confirmPhone();
                        return;
                    } elseif (!$user->city) {
                        $this->changeCity();
                        return;
                    }
                    $this->bot->startConversation(new TakingAddressConversation());
                } elseif ($answer->getValue() == 'change city') {
                    $this->changeCity();
                } elseif ($answer->getValue() == 'change phone number') {
                    $this->confirmPhone();
                } elseif ($answer->getValue() == 'request call') {
                    $user = $this->getUser();
                    $user->need_call = 1;
                    $user->save();
                    $this->say($this->__('messages.wait for dispatcher'), $this->bot->getUser()->getId());
                    $this->menu(true);
                } elseif ($answer->getValue() == 'price list') {
                    $this->say($this->__('messages.price list'));
                    $this->menu(true);
                } elseif ($answer->getValue() == 'all about bonuses') {
                    $this->bonuses();
                } elseif ($answer->getValue() == 'address history menu') {
                    // }  elseif($answer->getValue() == 'taxibot') {
                    $this->addressesMenu();
//                    $this->say($this->__('messages.address history menu'));
//                    AddressHistory::clearByUserId($this->bot->getUser()->getId());
//                    $this->menu(true);
                } elseif ($answer->getValue() == 'favorite addresses menu') {
                    $this->bot->startConversation(new FavoriteAddressesConversation());
                }
            } else {
                if ($answer->getText() == '/setabouttext') {
                    $this->say($this->__('messages.about myself'));
                }
                $this->menu();
            }
        });
    }

    public function addressesMenu()
    {
        $questionText = $this->__('messages.addresses menu');
        $questionText = $this->addAddressesToMessageOnlyFromHistory($questionText);
        $question = Question::create($questionText);
        $question->addButton(
            Button::create($this->__('buttons.back'))->value('back')->additionalParameters(['location' => 'addresses'])
        );
        $question->addButton(
            Button::create($this->__('buttons.clean addresses history'))->value('clean addresses history')
        );

        $user = User::find($this->bot->getUser()->getId());
        foreach ($user->addresses ?? [] as $key => $address) {
            $question->addButton(
                Button::create($address->address)->value($address->address)->additionalParameters(['number' => $key + 1]
                )
            );
        }


        return $this->ask($question, function (Answer $answer) {
            if ($answer->getValue() == 'back') {
                $this->bot->startConversation(new MenuConversation());
            } elseif ($answer->getValue() == 'clean addresses history') {
                $this->say($this->__('messages.clean addresses history'));
                AddressHistory::clearByUserId($this->getUser()->id);
                $this->bot->startConversation(new MenuConversation());
            } else {
                $this->addressMenu($answer->getText());
            }
        });
    }

    public function addressMenu($address)
    {
        $question = Question::create($this->__('messages.address menu') . ' ' . $address)
            ->addButtons([
                             Button::create($this->__('buttons.delete'))->value('delete'),
                             Button::create($this->__('buttons.back'))->value('back'),
                         ]);

        return $this->ask($question, function (Answer $answer) use ($address) {
            if ($answer->getValue() == 'back') {
                $this->addressesMenu();
            } else {
                $addr = User::find($this->bot->getUser()->getId())->addresses->where('address', $address)->first();
                if ($addr) {
                    $addr->delete();
                    $this->say($this->__('messages.address has been deleted'));
                } else {
                    $this->say($this->__('messages.problems with delete address') . $address);
                }
                $this->addressesMenu();
            }
        });
    }

    public function confirmPhone($first = false)
    {
        if ($first) {
            $message = $this->__('messages.enter phone first');
        } else {
            $message = $this->__('messages.enter phone');
        }
        $question = Question::create($message, $this->bot->getUser()->getId());
        $user = User::find($this->bot->getUser()->getId());
        if ($user && $user->phone) {
            $question = $question->addButton(Button::create($this->__('buttons.back'))->value('back'));
        }

        return $this->ask(
            $question,
            function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    $this->menu();
                } elseif (preg_match('^\+?[78][-\(]?\d{3}\)?-?\d{3}-?\d{2}-?\d{2}$^', $answer->getText())) {
                    $api = new OrderApiService();
                    $code = $api->getRandomSMSCode();
                    $this->bot->userStorage()->save(['sms_code' => $code, 'phone' => $answer->getText()]);
                    $api->sendSMSCode($answer->getText(), $code);
                    $this->confirmSMS();
                } else {
                    $this->say($this->__('messages.incorrect phone format'));
                    $this->confirmPhone();
                }
            }
        );
    }

    public function confirmSMS($withoutMessage = false)
    {
        $message = $this->__('messages.enter sms code');
        if ($withoutMessage) {
            $message = '';
        }
        $question = Question::create($message, $this->bot->getUser()->getId())
            ->addButton(Button::create($this->__('buttons.call'))->value('call'));

        return $this->ask(
            $question,
            function (Answer $answer) {
                if ($answer->getValue() == 'call') {
                    $api = new OrderApiService();
                    $code = $api->getRandomSMSCode();
                    $api->callSMSCode($this->bot->userStorage()->get('phone'), $code);
                    $this->bot->userStorage()->save(['sms_code' => $code]);
                    $this->confirmCall();
                }
                if ($answer->getText() == $this->bot->userStorage()->get('sms_code')) {
                    $phone = $this->getUser()->phone ?? null;
                    if ($phone) {
                        $this->getUser()->updatePhone(
                            OrderApiService::replacePhoneCountyCode($this->bot->userStorage()->get('phone'))
                        );
                    } else {
                        $this->changePhoneInRegistration();
                    }

                    $this->say(
                        $this->__('messages.phone changed', ['phone' => $this->bot->userStorage()->get('phone')])
                    );
                    $this->run();
                } else {
                    $this->say($this->__('messages.wrong sms code'));

                    $this->confirmSMS(true);
                }
            }
        );
    }

    public function confirmCall($withoutMessage = false)
    {
        $message = $this->__('messages.enter call code');
        if ($withoutMessage) {
            $message = '';
        }
        $question = Question::create($message, $this->bot->getUser()->getId())
            ->addButton(Button::create($this->__('buttons.call'))->value('call'));

        return $this->ask(
            $question,
            function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'call') {
                        $api = new OrderApiService();
                        $code = $api->getRandomSMSCode();
                        $api->callSMSCode($this->bot->userStorage()->get('phone'), $code);
                        $this->bot->userStorage()->save(['sms_code' => $code]);
                        $this->confirmCall();
                    }
                } elseif ($answer->getText() == $this->bot->userStorage()->get('sms_code')) {
                    $this->changePhoneInRegistration();
                    $this->say(
                        $this->__('messages.phone changed', ['phone' => $this->bot->userStorage()->get('phone')])
                    );
                    $this->run();
                } else {
                    $this->say($this->__('messages.incorrect phone code'));
                    $this->confirmCall(true);
                }
            }
        );
    }

    public function changePhone()
    {
    }

    public function changePhoneInRegistration()
    {
        $oldUser = User::where(
            'phone',
            OrderApiService::replacePhoneCountyCode($this->bot->userStorage()->get('phone'))
        )->first();

        if ($oldUser) {
            $this->getUser()->delete();
            if ($oldUser->isBlocked) {
                $blocked = true;
            }
            //$oldUser->delete();
            //$this->_sayDebug('Удалили пользователя');
            $oldUser->setPlatformId($this->bot);
            $oldUser->updatePhone(OrderApiService::replacePhoneCountyCode($this->bot->userStorage()->get('phone')));
        } else {
            $user = User::find($this->bot->getUser()->getId());
            if (!$user) {
                $user = User::create([
                                         'username' => $this->bot->getUser()->getUsername(),
                                         'firstname' => $this->bot->getUser()->getFirstName(),
                                         'lastname' => $this->bot->getUser()->getLastName(),
                                         'userinfo' => json_encode($this->bot->getUser()->getInfo()),
                                         'lang_id' => LangPackage::getDefaultLangId(),
                                     ]);
            }
            $user->setPlatformId($this->bot);
            $user->updatePhone(OrderApiService::replacePhoneCountyCode($this->bot->userStorage()->get('phone')));

            if ($blocked ?? false) {
                $user->block();
                $this->menu(true);
                return;
            }
            $user->save();
        }
    }

    public function changeCity()
    {
        if (User::find($this->bot->getUser()->getId())->isBlocked) {
            $this->say($this->__('messages.you are blocked'));
            return;
        }

        $user = User::find($this->bot->getUser()->getId());
        if ($user->city) {
            $question = Question::create($this->__('messages.choose city', ['city' => $user->city]));
        } else {
            $question = Question::create($this->__('messages.choose city without current city'));
        }

        $options = new Options($this->bot->channelStorage());
        $question = $question->addButton(
            Button::create($this->__('buttons.back'))->additionalParameters(
                ['config' => ButtonsFormatterService::CITY_MENU_FORMAT]
            )->value('back')
        );
        foreach ($options->getCities() as $city) {
            $question = $question->addButton(Button::create($city->name)->value($city->name));
        }

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
            if ($answer->isInteractiveMessageReply()) {
                $this->menu();
                return;
            }
            $user = User::find($this->bot->getUser()->getId());
            $user->city = $answer->getText();
            $user->save();
            $this->say($this->__('messages.city has been changed', ['city' => $answer->getText()]));
            $this->menu();
        });
    }

    public function bonuses($getBalance = false, $message = false)
    {
        $user = User::find($this->bot->getUser()->getId());
        if (!$message) {
            $message = $getBalance ? $this->__(
                'messages.get bonus balance',
                ['bonuses' => $user->getBonusBalance() ?? 0]
            ) : $this->__('messages.bonuses menu');
        }
        $question = Question::create($message)
            ->addButtons([
                             Button::create($this->__('buttons.bonus balance'))->additionalParameters(
                                 ['config' => ButtonsFormatterService::BONUS_MENU_FORMAT]
                             )->value('bonus balance'),
                             Button::create($this->__('buttons.work as driver'))->value('work as driver'),
                             Button::create($this->__('buttons.our site'))->value('our site'),
                             Button::create($this->__('buttons.our app'))->value('our app'),
                             Button::create($this->__('buttons.exit to menu'))->value('exit to menu'),
                         ]);
        return $this->ask(
            $question,
            function (Answer $answer) {
                Log::newLogAnswer($this->bot, $answer);
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'bonus balance') {
                        $this->bonuses(true);
                    } elseif ($answer->getValue() == 'work as driver') {
                        $this->bonuses(false, $this->__('messages.work as driver'));
                    } elseif ($answer->getValue() == 'our site') {
                        $this->bonuses(false, $this->__('messages.our site'));
                    } elseif ($answer->getValue() == 'our app') {
                        $this->bonuses(false, $this->__('messages.our app'));
                    } elseif ($answer->getValue() == 'exit to menu') {
                        $this->menu();
                    }
                } else {
                    $this->bonuses();
                }
            }
        );
    }

    public function run()
    {
        $user = User::find($this->bot->getUser()->getId());
        if (!$user) {
            $this->bot->startConversation(new StartConversation());
        } elseif (!$user->phone) {
            $this->confirmPhone(true);
        } elseif (!$user->city) {
            $this->changeCity();
        } else {
            $this->menu();
        }
    }
}