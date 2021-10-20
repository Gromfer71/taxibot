<?php


namespace App\Conversations;

use App\Models\FavoriteAddress;
use App\Models\Log;
use App\Services\Address;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use App\Traits\TakingAddressTrait;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

class FavoriteAddressesConversation extends BaseAddressConversation
{
    use TakingAddressTrait;

    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::EXIT => 'run',
            ButtonsStructure::BACK => 'App\Conversations\Settings\SettingsConversation',
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

            if ($answer->getValue() == ButtonsStructure::ADD_ADDRESS) {
                $this->getAddress(Translator::trans('messages.give me your favorite address'));
                return;
            }

            $this->bot->userStorage()->save(['address_name' => $answer->getText()]);
            $this->addressMenu();
        });
    }

    public function addressMenu()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.favorite address menu'),
            [ButtonsStructure::BACK, ButtonsStructure::DELETE]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue(), [ButtonsStructure::BACK => 'run']);
            FavoriteAddress::where([
                                       'user_id' => $this->getUser()->id,
                                       'name' => trim(
                                           stristr(
                                               $this->bot->userStorage()->get('address_name'),
                                               '(',
                                               true
                                           )
                                       )
                                   ])->first()->delete();
            $this->run();
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