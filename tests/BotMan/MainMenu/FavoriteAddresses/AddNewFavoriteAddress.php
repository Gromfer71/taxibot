<?php

namespace Tests\BotMan\MainMenu\FavoriteAddresses;

use App\Models\User;
use Tests\BotMan\BaseTest;
use Tests\BotMan\MainMenu\MainMenuTest;

class AddNewFavoriteAddress extends MainMenuTest
{
    public function run()
    {
        $this->goToFavoriteAddressesMenu();
        $this->goToAddAddress();
        $this->writeAddress();
        $this->chooseAddressFromApi();
        $this->enterEntrance();
        $this->enterFavoriteAddressName();
        $this->save();
        $this->selectCreatedAddress();
        $this->delete();
        $this->back();
    }

    private function goToFavoriteAddressesMenu()
    {
        $this->sendMessage(trans('buttons.favorite addresses menu'));
        $this->assertEqualsWithLogging(trans('messages.favorite addresses menu'), $this->getBotResponse());
    }

    private function goToAddAddress()
    {
        $this->sendMessage(trans('buttons.add address'));
        $this->assertEqualsWithLogging(trans('messages.give me your favorite address'), $this->getBotResponse());
    }

    private function writeAddress()
    {
        $address = 'лен2';
        $this->sendMessage($address);
        $this->assertEqualsWithLogging(trans('messages.give favorite address again'), $this->getBotResponse());
    }

    private function chooseAddressFromApi()
    {
        $address = 'Ленина пр-т 2 (Якутск)';
        $this->sendMessage($address);
        $this->assertEqualsWithLogging(trans('messages.give entrance in favorite address'), $this->getBotResponse());
    }

    private function enterEntrance()
    {
        $this->sendMessage('2');
        $this->assertEqualsWithLogging(trans('messages.get address name'), $this->getBotResponse());
    }

    private function enterFavoriteAddressName()
    {
        $this->sendMessage('дом');
        $this->assertEqualsWithLogging('дом Ленина пр-т 2 (Якутск), *п 2', $this->getBotResponse());
    }

    private function save()
    {
        $this->sendMessage(trans('buttons.save'));
        $this->assertEqualsWithLogging(trans('messages.favorite addresses menu'), $this->getBotResponse());
    }

    private function selectCreatedAddress()
    {
        $this->sendMessage('дом (Ленская ул. 2 (Якутск), *п 2)');
        $this->assertEqualsWithLogging(trans('messages.favorite address menu'), $this->getBotResponse());
    }

    private function delete()
    {
        $this->sendMessage(trans('buttons.delete'));
        $this->assertEqualsWithLogging(trans('messages.favorite addresses menu'), $this->getBotResponse());

    }

    private function back()
    {
        $this->sendMessage(trans('buttons.back'));
        $this->checkUserInMainMenu();
    }
}