<?php

namespace App\Conversations\MainMenu;

use App\Conversations\BaseConversation;
use App\Models\AddressHistory;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;

/**
 * Меню истории адресов
 */
class AddressesHistoryConversation extends BaseConversation
{

    /**
     * Действия
     *
     * @param array $replaceActions
     * @return array
     */
    public function getActions(array $replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::BACK => 'App\Conversations\Settings\SettingsConversation',
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

    /**
     * @inheritDoc
     */
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
            $this->handleAction($answer);
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
            [ButtonsStructure::DELETE, ButtonsStructure::BACK]
        );

        return $this->ask($question, function (Answer $answer) {
            $this->handleAction($answer, [ButtonsStructure::BACK => 'run']);
            $this->run();
        });
    }
}