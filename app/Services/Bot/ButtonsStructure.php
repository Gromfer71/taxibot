<?php

namespace App\Services\Bot;

use BotMan\BotMan\Messages\Outgoing\Actions\Button;

/**
 * Описание и упрощённый доступ к кнопкам бота
 */
class ButtonsStructure extends Button
{
    public const REQUEST_CALL = 'request call';
    public const CHANGE_PHONE = 'change phone number';
    public const TAKE_TAXI = 'take taxi';
    public const CHANGE_CITY = 'change city';
    public const PRICE_LIST = 'price list';
    public const ALL_ABOUT_BONUSES = 'all about bonuses';
    public const ADDRESS_HISTORY_MENU = 'address history menu';
    public const FAVORITE_ADDRESSES_MENU = 'favorite addresses menu';
    public const BACK = 'back';
    public const CLEAN_ALL_ADDRESS_HISTORY = 'clean addresses history';
    public const DELETE = 'delete';

    /**
     * Структура кнопок главного меню
     *
     * @return string[]
     */
    public static function getMainMenu(): array
    {
        return [
            ButtonsStructure::TAKE_TAXI,
            ButtonsStructure::REQUEST_CALL,
            ButtonsStructure::CHANGE_PHONE,
            ButtonsStructure::CHANGE_CITY,
            ButtonsStructure::PRICE_LIST,
            ButtonsStructure::ALL_ABOUT_BONUSES,
            ButtonsStructure::ADDRESS_HISTORY_MENU,
            ButtonsStructure::FAVORITE_ADDRESSES_MENU
        ];
    }
}