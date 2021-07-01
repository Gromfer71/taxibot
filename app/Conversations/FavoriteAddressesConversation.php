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
        $this->_sayDebug('ran f addresses');
        $this->bot->userStorage()->delete();
        $question = Question::create(trans('messages.favorite addresses menu'));

        $question->addButtons([
            Button::create(trans('buttons.back'))->value('back'),
            Button::create(trans('buttons.add address'))->value('add address')
        ]);

        foreach ($this->getUser()->favoriteAddresses as $address) {
            $question->addButton(Button::create($address->name . ' ('. $address->address . ')')->value($address->name));
        }

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
           $this->switchConversation($answer, 'back', new MenuConversation());

                if($answer->getValue() == 'add address') {
                    $this->addAddress();
                } else {
                    $this->_sayDebug($answer->getValue());
                    $this->bot->userStorage()->save(['address_name' => $answer->getValue()]);
                    $this->addressMenu();
                }


        });
    }

    public function addressMenu()
    {
        $question = Question::create(trans('messages.address menu'))->addButtons(
            [
                Button::create(trans('buttons.back'))->value('back'),
                Button::create(trans('buttons.delete'))->value('delete'),
            ]
        );

        return $this->ask($question, function (Answer $answer) {
            if($answer->getValue() == 'back') {
                $this->run();
            } elseif($answer->getValue() == 'delete') {
                $this->_sayDebug($this->bot->userStorage()->get('address_name'));
                $address = FavoriteAddress::where([
                    'user_id' => $this->getUser()->id,
                    'name' => $this->bot->userStorage()->get('address_name')
                ])->first();
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

        $question = Question::create(trans('messages.give me your address'), $this->bot->getUser()->getId())
            ->addButton(Button::create(trans('buttons.exit'))->value('exit'));

        return $this->ask($question, function (Answer $answer)  {
            Log::newLogAnswer($this->bot, $answer);
            if ($answer->getValue() == 'exit') {
                $this->run();
            }

            $this->_saveFirstAddress($answer);

            $addressesList = collect(Address::getAddresses($this->bot->userStorage()->get('address'), (new Options($this->bot->userStorage()))->getCities(), $this->bot->userStorage()));
            if ($addressesList->isEmpty()) {
                $this->streetNotFound();
            } else {
                $this->getAddressAgain();
            }
        });
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
                    $this->_saveFirstAddress($answer,$crew_group_id,$address['coords']['lat'],$address['coords']['lon'], $address['city']);
                    $this->getEntrance();
                } else {
                    $this->_saveFirstAddress($answer);
                    $this->getAddressAgain();
                }
            }
        );
    }

    public function streetNotFound()
    {
        $question = Question::create(trans('messages.not found address dorabotka bota'), $this->bot->getUser()->getId());
        $question->addButtons(
            [
                Button::create(trans('buttons.back'))->additionalParameters(['config' => ButtonsFormatterService::AS_INDICATED_MENU_FORMAT]),
                Button::create(trans('buttons.go as indicated')),
                Button::create(trans('buttons.exit to menu')),
            ]
        );

        return $this->ask(
            $question,
            function (Answer $answer) {
                if ($answer->isInteractiveMessageReply()) {
                    if ($answer->getValue() == 'back') {
                        $this->addAddress();
                    } elseif ($answer->getValue() == 'exit to menu') {
                        $this->run();
                    } elseif ($answer->getValue() == 'go as indicated') {
                        $this->getEntrance();
                    }
                } else {
                    $this->_saveFirstAddress($answer);
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
        $question = Question::create(trans('messages.give entrance'), $this->bot->getUser()->getId())
            ->addButtons([
                Button::create(trans('buttons.no entrance'))->value('no entrance'),
                Button::create(trans('buttons.exit'))->value('exit'),
            ]);

        return $this->ask($question, function (Answer $answer) {
            Log::newLogAnswer($this->bot, $answer);
            if ($answer->isInteractiveMessageReply()) {
                if ($answer->getValue() == 'exit') {
                    $this->run();
                } elseif ($answer->getValue() == 'no entrance') {

                    $this->getAddressName();
                }
            } else {
                $address = $this->bot->userStorage()->get('address') . ', *п ' . $answer->getText();
                $this->bot->userStorage()->save(['address' => $address]);

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
        });
    }
}