<?php

namespace App\Services\Bot;

use BotMan\BotMan\Messages\Outgoing\Actions\Button;

class ButtonsStructure extends Button
{
    public const REQUEST_CALL = 'buttons.request call';
    public const CHANGE_PHONE = 'buttons.change phone number';
    public const TAKE_TAXI = 'buttons.take taxi';
    public const CHANGE_CITY = 'buttons.change city';
    public const PRICE_LIST = 'buttons.price list';
    public const ALL_ABOUT_BONUSES = 'buttons.all about bonuses';
    public const ADDRESS_HISTORY_MENU = 'buttons.address history menu';
    public const FAVORITE_ADDRESSES_MENU = 'buttons.favorite addresses menu';

    public static function getMainMenu()
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