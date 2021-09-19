<?php

namespace App\Conversations\MainMenu;

use App\Conversations\BaseConversation;
use App\Models\Log;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\OrderApiService;
use App\Services\Translator;
use App\Traits\SetupCityTrait;
use Barryvdh\TranslationManager\Models\LangPackage;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

/**
 * Главное меню бота
 */
class MenuConversation extends BaseConversation
{
    use SetupCityTrait;

    public function getActions($replaceActions = [])
    {
        $actions = [
            ButtonsStructure::REQUEST_CALL => function () {
                $user = $this->getUser();
                $user->need_call = 1;
                $user->save();
                $this->say($this->__('messages.wait for dispatcher'), $this->bot->getUser()->getId());
                $this->menu(true);
            },
            ButtonsStructure::CHANGE_PHONE => 'confirmPhone',
            ButtonsStructure::TAKE_TAXI => 'App\Conversations\TakingAddressConversation',
            ButtonsStructure::CHANGE_CITY => 'changeCity',
            ButtonsStructure::PRICE_LIST => function () {
                $this->menu(Translator::trans('messages.price list'));
            },
            ButtonsStructure::ALL_ABOUT_BONUSES => 'bonuses',
            ButtonsStructure::ADDRESS_HISTORY_MENU => 'App\Conversations\MainMenu\AddressesHistoryConversation',
            ButtonsStructure::FAVORITE_ADDRESSES_MENU => 'App\Conversations\FavoriteAddressesConversation',
            ButtonsStructure::BACK => 'menu',
        ];

        return parent::getActions(array_replace_recursive($replaceActions, $actions));
    }

    /**
     * Главное меню
     *
     * @param null $message
     * @return MenuConversation
     */
    public function menu($message = null): MenuConversation
    {
        $this->bot->userStorage()->delete();
        OrderHistory::cancelAllOrders($this->getUser()->id, $this->bot->getDriver()->getName());

        $question = ComplexQuestion::createWithSimpleButtons(
            $message ?: Translator::trans('messages.choose menu'),
            ButtonsStructure::getMainMenu(),
            ['config' => ButtonsFormatterService::MAIN_MENU_FORMAT]
        );

        return $this->ask($question, $this->getDefaultCallback());
    }


    /**
     * @return \App\Conversations\MainMenu\MenuConversation
     */
    public function changeCity(): MenuConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.choose city', ['city' => $this->getUser()->city]),
            [ButtonsStructure::BACK],
            ['config' => ButtonsFormatterService::CITY_MENU_FORMAT]
        );

        $question = ComplexQuestion::setButtons(
            $question,
            $this->getCitiesArray()
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            $this->getUser()->updateCity($answer->getText());
            $this->menu(Translator::trans('messages.city has been changed', ['city' => $answer->getText()]));
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
                } elseif (preg_match('^\+?[78][-(]?\d{3}\)?-?\d{3}-?\d{2}-?\d{2}$^', $answer->getText())) {
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

    public function run()
    {
        $this->menu();
    }

    public function changePhone()
    {
    }
}