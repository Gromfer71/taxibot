<?php


namespace App\Conversations;

use App\Models\FavoriteAddress;
use App\Models\Log;
use App\Models\User;
use App\Services\Address;
use App\Services\ButtonsFormatterService;
use App\Services\Options;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Nexmo\Conversations\Conversation;

class FavoriteAddressesConversation extends BaseAddressConversation
{

    public function run()
    {
        $this->_sayDebug('запустили меню избранных адресов');
        $this->bot->userStorage()->delete();
        $question = Question::create(trans('messages.favorite addresses menu'));

        $question->addButtons([
            Button::create(trans('buttons.back'))->value('back'),
            Button::create(trans('buttons.add address'))->value('add address')
        ]);

        foreach ($this->getUser()->favoriteAddresses as $address) {
            $question->addButton(Button::create($address->name . ' ('. $address->address . ')'));
        }

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
                    if($answer->getValue() == 'add address') {
                        $this->addAddress();
                    } elseif($answer->getValue() == 'back') {
                        $this->bot->startConversation(new MenuConversation());
                    } else {
                        $this->_sayDebug('Выбранный пункт меню - ' . $answer->getText());
                        $this->bot->userStorage()->save(['address_name' => $answer->getText()]);
                        $this->addressMenu();
                    }
        });
    }

    public function addressMenu()
    {
        $question = Question::create(trans('messages.favorite address menu'))->addButtons(
            [
                Button::create(trans('buttons.back'))->value('back'),
                Button::create(trans('buttons.delete'))->value('delete'),
            ]
        );

        return $this->ask($question, function (Answer $answer) {
            if($answer->getValue() == 'back') {
                $this->run();
            } elseif($answer->getValue() == 'delete') {
                $this->_sayDebug('Адрес целиком до обрезания до псевдонима - ' . $this->bot->userStorage()->get('address_name'));
                $address = FavoriteAddress::where([
                    'user_id' => $this->getUser()->id,
                    'name' => trim(stristr($this->bot->userStorage()->get('address_name'), '(', true))
                ])->first();
                $this->_sayDebug('Псевдоним адреса после обрезки для бд - ' . trim(stristr($this->bot->userStorage()->get('address_name'), '(', true)));
                if($address) {
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

        $question = Question::create(trans('messages.give me your favorite address'), $this->bot->getUser()->getId())

            ->addButton(Button::create(trans('buttons.exit'))->value('exit'));

        return $this->ask($question, function (Answer $answer)  {
            Log::newLogAnswer($this->bot, $answer);
            if ($answer->getValue() == 'exit') {
                $this->run();

            } else {
                $this->_saveFirstAddress($answer->getText());

                $addressesList = collect(Address::getAddresses($this->bot->userStorage()->get('address'), (new Options($this->bot->userStorage()))->getCities(), $this->bot->userStorage()));
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
        $question = Question::create(trans('messages.give favorite address again'), $this->bot->getUser()->getId());
        $addressesList = collect(Address::getAddresses($this->bot->userStorage()->get('address'), (new Options($this->bot->userStorage()))->getCities(), $this->bot->userStorage()));
        $this->_sayDebug('getAddressAgain2');
        $question->addButton(Button::create(trans('buttons.exit'))->value('exit'));
        if ($addressesList->isNotEmpty()) {
            $this->_sayDebug('addressesList->isNotEmpty');
            $addressesList = $addressesList->take(25);
            foreach ($addressesList as $address) {
                $question->addButton(Button::create(Address::toString($address)));
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
                    $this->_saveFirstAddress($address['address'], $crew_group_id,$address['coords']['lat'],$address['coords']['lon'], $address['city']);
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
        $question = Question::create(trans('messages.not found favorite address'), $this->bot->getUser()->getId());
        $question->addButtons(
            [
                Button::create(trans('buttons.back'))->additionalParameters(['config' => ButtonsFormatterService::AS_INDICATED_MENU_FORMAT]),
                Button::create(trans('buttons.save as written')),
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
        $question = Question::create(trans('messages.forget write house in favorite address'), $this->bot->getUser()->getId())
            ->addButtons([
                Button::create(trans('buttons.exit'))->value('exit'),
            ]);;

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
        $question = Question::create(trans('messages.give entrance in favorite address'), $this->bot->getUser()->getId())
            ->addButtons([
                Button::create(trans('buttons.no entrance'))->value('no entrance'),
                Button::create(trans('buttons.exit'))->value('exit'),
            ]);

        return $this->ask($question, function (Answer $answer) {
            $this->_sayDebug('начало');
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
        $question = Question::create(trans('messages.get address name'))
            ->addButton(Button::create(trans('buttons.exit'))->value('exit'));

        return $this->ask($question, function (Answer $answer) {
           if($answer->getValue() == 'exit') {
               $this->run();
           } else {
               if(strlen($answer->getText()) > 32) {
                   $this->say(trans('messages.address name too long'));
                   $this->getAddressName();
               } else {
                   $this->_sayDebug(json_encode($this->bot->userStorage()->get('address')));
                   FavoriteAddress::create(
                       [
                           'user_id' => $this->getUser()->id,
                           'address' => $this->bot->userStorage()->get('address'),
                           'name' => $answer->getText(),
                           'lat' => $this->bot->userStorage()->get('lat'),
                           'lon' => $this->bot->userStorage()->get('lon'),
                           'city' => $this->bot->userStorage()->get('address_city'),

                       ]
                   );

                   $this->run();
               }
           }
        });
    }
}