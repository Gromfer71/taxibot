<?php

namespace Tests\BotMan\MainMenu\FavoriteAddresses;

use App\Models\User;
use Tests\BotMan\BaseTest;

class AddNewFavoriteAddress extends BaseTest
{
    public function run()
    {
        $this->restart();
        $this->goToFavoriteAddressesMenu();
        $this->goToAddAddress();
        $this->selectAddressFromHistory();

    }

    private function goToFavoriteAddressesMenu()
    {
        $this->sendMessage(trans('buttons.favorite addresses menu'));
        $response = $this->getBotResponse();
        $this->assertEqualsWithLogging(trans('messages.favorite addresses menu'), $response);
    }

    private function goToAddAddress()
    {
        $this->sendMessage(trans('buttons.add address'));
        $response = $this->getBotResponse();
        $this->assertEqualsWithLogging(trans('messages.give me your favorite address'), $response);
    }

    private function selectAddressFromHistory()
    {
        $address = 'Ленина пр-т 2 (Якутск), *п 2';
        $this->sendMessage($address);
        $response = $this->getBotResponse();
        $this->assertEqualsWithLogging(trans('messages.get address name'), $response);
    }
}