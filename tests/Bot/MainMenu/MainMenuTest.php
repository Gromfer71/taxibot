<?php

declare(strict_types=1);

namespace Tests\Bot\MainMenu;

use Tests\Bot\BaseTest;
use Tests\Bot\MainMenu\FavoriteAddresses\AddNewFavoriteAddress;


/**
 * Тестирование главного меню, основных функций
 */
class MainMenuTest extends BaseTest
{
    public const MAIN_MENU_MESSAGE = 'Моё почтение! Выберите из вариантов ниже, что я могу для Вас сделать';

    /**
     * Начало запуска
     */
    public function run()
    {
        $this->restart();
        $this->checkUserInMainMenu();

        $favoriteAddressesMenuTest = new AddNewFavoriteAddress($this->proto);
        $favoriteAddressesMenuTest->run();
        $this->mergeErrors($favoriteAddressesMenuTest->getTestResults());
    }

    /**
     * Проверяем, находится ли пользователь в главном меню
     */
    public function checkUserInMainMenu()
    {
        $this->assertEquals(self::MAIN_MENU_MESSAGE, $this->createResponse());
    }
}