<?php

namespace Tests\Bot\MainMenu;

use Tests\Bot\BaseTest;
use Tests\Bot\MainMenu\FavoriteAddresses\AddNewFavoriteAddress;


class MainMenuTest extends BaseTest
{
    const MAIN_MENU_MESSAGE = 'Моё почтение! Выберите из вариантов ниже, что я могу для Вас сделать';

    public function run()
    {
        $this->restart();
        $this->checkUserInMainMenu();

        $favoriteAddressesMenuTest = new AddNewFavoriteAddress($this->proto);
        $favoriteAddressesMenuTest->run();
        $this->mergeErrors($favoriteAddressesMenuTest->getErrors());

    }


    public function checkUserInMainMenu()
    {
        $this->assertEqualsWithLogging(self::MAIN_MENU_MESSAGE, $this->setAndGetBotResponse());
    }
}