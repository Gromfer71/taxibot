<?php

namespace App\Conversations\MainMenu;

use App\Conversations\BaseConversation;
use App\Models\AddressHistory;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

/**
 *
 */
class AddressesHistoryConversation extends BaseConversation
{

    /**
     * @param array $replaceActions
     * @return array
     */
    public function getActions($replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => 'App\Conversations\MainMenu\MenuConversation',
            ButtonsStructure::CLEAN_ALL_ADDRESS_HISTORY => function () {
                $this->say(Translator::trans('messages.clean addresses history'));
                AddressHistory::clearByUserId($this->getUser()->id);
                $this->bot->startConversation(new MenuConversation());
            },
            ButtonsStructure::DELETE => function () {
                if ($address = $this->getUser()->getUserAddressByName($this->getFromStorage('address'))) {
                    $address->delete();
                    $this->say(Translator::trans('messages.address has been deleted'));
                } else {
                    $this->say(Translator::trans('messages.problems with delete address') . ' ' . $address);
                }
            }
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            $this->addAddressesToMessageOnlyFromHistory(Translator::trans('messages.addresses menu')),
            [ButtonsStructure::BACK, ButtonsStructure::CLEAN_ALL_ADDRESS_HISTORY],
            ['location' => 'addresses']
        );
        $question = ComplexQuestion::setAddressButtons($question, $this->getUser()->addresses);

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue());
            $this->saveToStorage(['address' => $answer->getText()]);
            $this->addressMenu();
        });
    }

    /**
     * @return \App\Conversations\MainMenu\AddressesHistoryConversation
     */
    public function addressMenu(): AddressesHistoryConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.address menu'),
            [ButtonsStructure::DELETE, ButtonsStructure::BACK]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer->getValue(), [ButtonsStructure::BACK => 'run']);
            $this->run();
        });
    }
}