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
    public const BONUS_BALANCE = 'bonus balance';
    public const WORK_AS_DRIVER = 'work as driver';
    public const OUR_SITE = 'our site';
    public const OUR_APP = 'our app';
    public const LANG_MENU = 'lang menu';
    public const EXIT = 'exit';
    public const GO_AS_INDICATED = 'go as indicated';
    public const EXIT_TO_MENU = 'exit to menu';
    public const ADDRESS_WILL_SAY_TO_DRIVER = 'address will say to driver';
    public const NO_ENTRANCE = 'no entrance';
    public const CANCEL = 'cancel';
    public const CLEAR_ORDERS_HISTORY_MENU = 'clear orders history menu';
    public const CONFIRM = 'confirm';
    //routes
    public const ADD_ROUTE = 'add route';
    public const CREATE_ROUTE = 'create route';
    public const FAVORITE_ROUTES = 'favorite routes';
    public const SAVE = 'save';
    public const ADD_ADDRESS = 'add address';
    public const DELETE_ROUTE = 'delete route';
    public const SETTINGS = 'settings';
    public const FAVORITE_ROUTE_SETTINGS = 'favorite route settings';

    //taxi menu
    public const GO_FOR_BONUSES = 'go for bonuses';
    public const GO_FOR_CASH = 'go for cash';
    public const WRITE_COMMENT = 'write comment';
    public const WISHES = 'wishes';
    public const CHANGE_PRICE = 'change price';
    public const NEED_DISPATCHER = 'need dispatcher';
    public const ORDER_INFO = 'order info';
    public const CANCEL_ORDER = 'cancel order';
    public const ORDER_CONFIRM = 'order_confirm';
    public const CLIENT_GOES_OUT = 'client_goes_out';
    public const CLIENT_GOES_OUT_LATE = 'client_goes_out_late';
    public const NEED_DRIVER = 'need driver';
    public const FINISH_ORDER = 'finish order';
    public const ADD_TO_FAVORITE_ROUTES = 'add to favorite routes';
    public const ABORTED_ORDER = 'aborted order';
    public const CONTINUE = 'continue';
    public const CANCEL_CHANGE_PRICE = 'cancel change price';
    public const CANCEL_LAST_WISH = 'cancel last wish';
    public const NEED_MAP = 'need map';
    public const GET_DRIVER_LOCATION = 'get driver location';


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
            ButtonsStructure::FAVORITE_ROUTES,
            ButtonsStructure::PRICE_LIST,
            ButtonsStructure::ALL_ABOUT_BONUSES,
            ButtonsStructure::SETTINGS,
        ];
    }

    /**
     * Структура кнопок меню бонусов
     *
     * @return string[]
     */
    public static function getBonusesMenu(): array
    {
        return [
            ButtonsStructure::BONUS_BALANCE,
            ButtonsStructure::WORK_AS_DRIVER,
            ButtonsStructure::OUR_SITE,
            ButtonsStructure::OUR_APP,
            ButtonsStructure::BACK,
        ];
    }

    public static function getSettingsMenu(): array
    {
        return [
            ButtonsStructure::BACK,
            ButtonsStructure::CHANGE_PHONE,
            ButtonsStructure::CHANGE_CITY,
            ButtonsStructure::LANG_MENU,
            ButtonsStructure::ADDRESS_HISTORY_MENU,
            ButtonsStructure::CLEAR_ORDERS_HISTORY_MENU,
            ButtonsStructure::FAVORITE_ADDRESSES_MENU,
            ButtonsStructure::FAVORITE_ROUTE_SETTINGS,
        ];
    }
}