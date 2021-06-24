<?php


namespace App\Conversations;


use App\Models\AddressHistory;
use App\Models\Log;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\Options;
use App\Services\OrderApiService;
use App\Services\BonusesApi;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use App\Services\ButtonsFormatterService;

class MenuConversation extends BaseConversation
{
	public function menu($withoutMessage = false)
	{
        $user = User::find($this->bot->getUser()->getId());
        if(! $user) {
            $this->bot->startConversation(new StartConversation());
            return;
        } elseif (! $user->phone) {
            $this->confirmPhone();
            return;
        } elseif (! $user->city) {
            $this->changeCity();
            return;
        }

        if (!$user->server_id) {
            $user->registerServerId();
        }

		$this->bot->userStorage()->delete();
        $this->checkConfig();
		OrderHistory::cancelAllOrders($this->bot->getUser()->getId());


		$question = Question::create($withoutMessage ? '' : trans('messages.choose menu'), $this->bot->getUser()->getId())
			->addButtons([
				Button::create(trans('buttons.take taxi'))->value('take taxi')->additionalParameters(['config' => ButtonsFormatterService::MAIN_MENU_FORMAT]),
                Button::create(trans('buttons.request call'))->value('request call'),
                Button::create(trans('buttons.change phone number'))->value('change phone number'),
                Button::create(trans('buttons.change city'))->value('change city'),
				Button::create(trans('buttons.price list'))->value('price list'),
                Button::create(trans('buttons.all about bonuses'))->value('all about bonuses'),
                //Button::create(trans('buttons.address history menu'))->value('address history menu'),
                Button::create(trans('buttons.clean addresses history'))->value('clean addresses history'),
			]);

		return $this->ask($question, function (Answer $answer) use ($user) {
			Log::newLogAnswer($this->bot, $answer);

            if ($user->isBlocked) {
                $this->say(trans('messages.you are blocked'));
                return;
            }

			if ($answer->isInteractiveMessageReply()) {
				if ($answer->getValue() == 'take taxi') {
                    $user = User::find($this->bot->getUser()->getId());
                    if(! $user) {
                        $this->bot->startConversation(new StartConversation());
                        return;
                    } elseif (! $user->phone) {
                        $this->confirmPhone();
                        return;
                    } elseif (! $user->city) {
                        $this->changeCity();
                        return;
                    }
					$this->bot->startConversation(new TakingAddressConversation());
				} elseif ($answer->getValue() == 'change city') {
					$this->changeCity();
				} elseif ($answer->getValue() == 'change phone number') {
					$this->confirmPhone();
				} elseif($answer->getValue() == 'request call') {
				    $this->_sayDebug('request call из главного меню');
                    $api = new OrderApiService();
                    $user = User::find($this->bot->getUser()->getId());
                    $crew = 25;
                    $this->_sayDebug('город - '.$user->city);
                    if ($user->city) {
                        $options = new Options($this->bot->userStorage());
                        $crew = $options->getCrewGroupIdFromCity($user->city);
                    }
                    if ($user->city == 'Чульман'){
                        $crew = 54;
                    }
                    $this->_sayDebug('crew - '.$crew);
					$this->say(trans('messages.wait for dispatcher'), $this->bot->getUser()->getId());
                    $api->connectDispatcherWithCrewId(User::find($this->bot->getUser()->getId())->phone,$crew);
                    $this->menu(true);
                }  elseif($answer->getValue() == 'price list') {
				    $this->say(trans('messages.price list'));
				    $this->menu(true);
                }  elseif ($answer->getValue() == 'all about bonuses') {
                   $this->bonuses();
              // }  elseif($answer->getValue() == 'address history menu') {
               }  elseif($answer->getValue() == 'clean addresses history') {
	//			    $this->addressesMenu();
                    $this->say(trans('messages.clean addresses history'));
                    AddressHistory::clearByUserId($this->bot->getUser()->getId());
                    $this->menu(true);
                }
			} else {
			       if($answer->getText() == '/setabouttext') {
                     $this->say(trans('messages.about myself'));
                   }
                $this->menu();
			}
		});
	}

//	public function addressesMenu()
//    {
//        $question = Question::create(trans('messages.addresses menu'), $this->bot->getUser()->getId());
//        $user = User::find($this->bot->getUser()->getId());
//        foreach ($user->addresses as $address) {
//            $question->addButton(Button::create($address->address)->value($address->address));
//        }
//        $question->addButton(Button::create(trans('buttons.clean addresses history'))->value('clean addresses history'));
//        $question->addButton(Button::create(trans('buttons.back'))->value('back'));
//
//
//        return $this->ask($question, function (Answer $answer) {
//            if($answer->getValue() == 'back') {
//                $this->menu(true);
//            } elseif ($answer->getValue() == 'clean addresses history') {
//                    $this->say(trans('messages.clean addresses history'));
//                    AddressHistory::clearByUserId($this->bot->getUser()->getId());
//                    $this->menu(true);
//            } else {
//                $this->addressMenu($answer->getValue());
//            }
//        });
//    }
//
//    public function addressMenu($address)
//    {
//        $question = Question::create(trans('messages.address menu') . ' ' . $address)
//            ->addButtons([
//                Button::create('Удалить')->value('delete'),
//                Button::create('Назад')->value('back'),
//            ]);
//
//        return $this->ask($question, function (Answer $answer) use ($address) {
//            if($answer->getValue() == 'back') {
//                $this->addressesMenu();
//            } else {
//                User::find($this->bot->getUser()->getId())->addresses->where('address', $address)->first()->delete();
//                $this->addressesMenu();
//            }
//        });
//    }

	public function confirmPhone($first = false)
    {
        $this->bot->userStorage()->delete('sms_code');
        if ($first) {
            $message = trans('messages.enter phone first');
        } else {
            $message = trans('messages.enter phone');
        }
        $question = Question::create($message, $this->bot->getUser()->getId());

        if (User::find($this->bot->getUser()->getId())->phone) {
            $question = $question->addButton(Button::create(trans('buttons.back'))->value('back'));
        }

        return $this->ask(
            $question,
            function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    $this->menu();
                } elseif (preg_match('^\+?[78][-\(]?\d{3}\)?-?\d{3}-?\d{2}-?\d{2}$^', $answer->getText())) {

                    if(User::where('phone', $answer->getText())->first()->isBlocked ?? true) {
                        $user = User::find($this->bot->getUser()->getId());
                        $user->block();
                        $this->say(trans('messages.you are blocked'));
                        //$this->menu();
                        return;
                    }
                    $api = new OrderApiService();
                    $code = $api->getRandomSMSCode();
                    $this->bot->userStorage()->save(['sms_code' => $code, 'phone' => $answer->getText()]);
                    $api->sendSMSCode($answer->getText(), $code);
                    $this->confirmSMS();
                } else {
					$this->say(trans('messages.incorrect phone format'));
					$this->confirmPhone();
				}
			}
		);
	}

	public function confirmSMS($withoutMessage = false)
	{
	    $message = trans('messages.enter sms code');
	    if ($withoutMessage) $message = '';
		$question = Question::create($message, $this->bot->getUser()->getId())
			->addButton(Button::create(trans('buttons.call')));

		return $this->ask(
			$question,
			function (Answer $answer) {
			    if($answer->isInteractiveMessageReply()) {
			        if($answer->getValue() == 'call') {
			            $api = new OrderApiService();
			            $code = $api->getRandomSMSCode();
			            $api->callSMSCode($this->bot->userStorage()->get('phone'), $code);
                        $this->bot->userStorage()->save(['sms_code' => $code]);
			            $this->confirmCall();
                    }
                } elseif($answer->getText() == $this->bot->userStorage()->get('sms_code')) {
                    User::find($this->bot->getUser()->getId())->updatePhone(OrderApiService::replacePhoneCountyCode($this->bot->userStorage()->get('phone')));
                    $this->say(trans('messages.phone changed',['phone' => $this->bot->userStorage()->get('phone')]));
                    $this->run();
                } else {
					$this->say(trans('messages.wrong sms code'));

					$this->confirmSMS(true);
				}
			}
		);
	}

    public function confirmCall($withoutMessage = false)
    {
        $message = trans('messages.enter call code');
        if ($withoutMessage) $message = '';
        $question = Question::create($message, $this->bot->getUser()->getId())
            ->addButton(Button::create(trans('buttons.call')));

        return $this->ask(
            $question,
            function (Answer $answer) {
                if($answer->isInteractiveMessageReply()) {
                    if($answer->getValue() == 'call') {
                        $api = new OrderApiService();
                        $code = $api->getRandomSMSCode();
                        $api->callSMSCode($this->bot->userStorage()->get('phone'), $code);
                        $this->bot->userStorage()->save(['sms_code' => $code]);
                        $this->confirmCall();
                    }
                } elseif($answer->getText() == $this->bot->userStorage()->get('sms_code')) {
                    User::find($this->bot->getUser()->getId())->updatePhone(OrderApiService::replacePhoneCountyCode($this->bot->userStorage()->get('phone')));
                    $this->say(trans('messages.phone changed',['phone' => $this->bot->userStorage()->get('phone')]));
                    $this->run();
                } else {
                    $this->say(trans('messages.incorrect phone code'));
                    $this->confirmCall(true);
                }
            }
        );
    }

	public function changeCity()
	{
		$user = User::find($this->bot->getUser()->getId());
		if ($user->city) {
			$question = Question::create(trans('messages.choose city', ['city' => $user->city]));
		} else {
			$question = Question::create(trans('messages.choose city without current city'));
		}

		$options = new Options($this->bot->channelStorage());
        $question = $question->addButton(Button::create(trans('buttons.back'))->additionalParameters(['config' => ButtonsFormatterService::CITY_MENU_FORMAT]));
		foreach ($options->getCities() as $city) {
			$question = $question->addButton(Button::create($city->name)->value($city->name));
		}

		return $this->ask($question, function (Answer $answer) {
			    Log::newLogAnswer($this->bot, $answer);
                if($answer->isInteractiveMessageReply()) {
                    $this->menu();
                    return;
                }
				$user = User::find($this->bot->getUser()->getId());
				$user->city = $answer->getText();
				$user->save();
				$this->say(trans('messages.city has been changed', ['city' => $answer->getText()]));
				$this->menu();

		});

	}
    public function bonuses($getBalance = false,$message = false)
    {
        $user = User::find($this->bot->getUser()->getId());
        if (!$message){
            $message = $getBalance ? trans('messages.get bonus balance', ['bonuses' => $user->getBonusBalance() ?? 0]) : trans('messages.bonuses menu');
        }
        $question = Question::create($message)
            ->addButtons([
                Button::create(trans('buttons.bonus balance'))->additionalParameters(['config' => ButtonsFormatterService::BONUS_MENU_FORMAT]),
                Button::create(trans('buttons.work as driver')),
                Button::create(trans('buttons.our site')),
                Button::create(trans('buttons.our app')),
                Button::create(trans('buttons.exit to menu')),
            ]);
        return $this->ask(
            $question,
            function (Answer $answer) {
                Log::newLogAnswer($this->bot, $answer);
                if($answer->isInteractiveMessageReply()) {
                    if($answer->getValue() == 'bonus balance') {
                        $this->bonuses(true);
                    } elseif($answer->getValue() == 'work as driver') {
                        $this->bonuses(false,trans('messages.work as driver'));
                    } elseif($answer->getValue() == 'our site') {
                        $this->bonuses(false,trans('messages.our site'));
                    } elseif($answer->getValue() == 'our app') {
                        $this->bonuses(false,trans('messages.our app'));
                    } elseif($answer->getValue() == 'exit to menu') {
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
		if(! $user) {
			$this->bot->startConversation(new StartConversation());
		} elseif (! $user->phone) {
			$this->confirmPhone(true);
		} elseif (! $user->city) {
			$this->changeCity();
		} else {
			$this->menu();
		}

	}
}