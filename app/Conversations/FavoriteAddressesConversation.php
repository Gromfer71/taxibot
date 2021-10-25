<?php


namespace App\Conversations;

use App\Models\FavoriteAddress;
use App\Services\Address;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use App\Traits\TakingAddressTrait;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;

class FavoriteAddressesConversation extends BaseAddressConversation
{
    use TakingAddressTrait;

    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::EXIT => 'run',
            ButtonsStructure::BACK => 'App\Conversations\Settings\SettingsConversation',
            ButtonsStructure::EXIT_TO_MENU => 'App\Conversations\MainMenu\MenuConversation',
            ButtonsStructure::CANCEL => 'run',
            ButtonsStructure::SAVE => function () {
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
            }
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

    public function getAddressName()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.get address name'),
            [ButtonsStructure::EXIT]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            $this->checkAddressNameForLength($answer->getText());
            $this->bot->userStorage()->save(['address_name' => $answer->getText()]);
            $this->confirmAddress();
        });
    }


    public function confirmAddress()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.favorite address'),
            [ButtonsStructure::SAVE, ButtonsStructure::CANCEL]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            $this->getAddressName();
        });
    }

    public function redirectAfterGetEntrance()
    {
        $this->getAddressName();
    }

    public function checkAddressNameForLength($addressName)
    {
        if (mb_strlen($addressName) > 32) {
            $this->say(Translator::trans('messages.address name too long'));
            $this->getAddressName();
            die();
        }
    }
}