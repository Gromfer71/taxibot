<?php

namespace App\Conversations\MainMenu;

use App\Conversations\BaseConversation;
use App\Conversations\Settings\SettingsConversation;
use App\Models\AddressHistory;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

class AddressesHistoryConversation extends BaseConversation
{
    public function getActions($replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => SettingsConversation::class,
            ButtonsStructure::CLEAN_ALL_ADDRESS_HISTORY => function () {
                $this->say(Translator::trans('messages.clean addresses history'));
                AddressHistory::clearByUserId($this->getUser()->id);
                $this->bot->startConversation(new MenuConversation());
            },
            ButtonsStructure::DELETE => function () {
                if ($address = $this->getUser()->getUserAddressByName($this->getFromStorage('address'))) {
                    $address->delete();
                    $this->say(Translator::trans('messages.address has been deleted'));
                    $this->run();
                } else {
                    $this->say(Translator::trans('messages.problems with delete address') . ' ' . $address);
                }
            }
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }

    public function run()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            $this->addAddressesToMessageOnlyFromHistory(Translator::trans('messages.addresses menu')),
            [ButtonsStructure::BACK, ButtonsStructure::CLEAN_ALL_ADDRESS_HISTORY],
            ButtonsFormatterService::getAdditionalForClearMenu($this->bot->getDriver())
        );
        $question = ComplexQuestion::setAddressButtons($question, $this->getUser()->addresses);
        foreach ($this->getUser()->addresses as $key => $address) {
            $this->saveToStorage(['addresses' => collect($this->getFromStorage('addresses'))->put($key + 1, $address->address)]);
        }

        return $this->ask($question, function (Answer $answer) {
            if ($this->handleAction($answer)) {
                return;
            }
            if (property_exists($this->bot->getDriver(), 'needToAddAddressesToMessage')) {
                $address = collect($this->getFromStorage('addresses'))->get($answer->getText());
                if (!$address) {
                    $address = $answer->getText();
                }
            } else {
                $address = $answer->getText();
            }


            $this->saveToStorage(['address' => $address]);
            $this->addressMenu();
        });
    }

    /**
     * Меню конкретного выбранного адреса
     *
     * @return \App\Conversations\MainMenu\AddressesHistoryConversation
     */
    public function addressMenu(): AddressesHistoryConversation
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.address menu', ['address' => $this->getFromStorage('address')]),
            [ButtonsStructure::BACK, ButtonsStructure::DELETE]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer, [ButtonsStructure::BACK => 'run']) ?: $this->run();
        });
    }
}