<?php
namespace Tests\BotMan\MainMenu;

use Tests\BotMan\BaseTest;
use Tests\BotMan\MainMenu\FavoriteAddresses\AddNewFavoriteAddress;

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