<?php

namespace App\Services;


use BotMan\Drivers\VK\Extensions\VKKeyboardButton;
use Illuminate\Support\Collection;

class ButtonsFormatterService
{
    public const MAIN_MENU_FORMAT = 1;
    public const BONUS_MENU_FORMAT = 2;
    public const CITY_MENU_FORMAT = 3;
    public const TAXI_MENU_FORMAT = 4;
    public const CHANGE_PRICE_MENU_FORMAT = 5;
    public const AS_INDICATED_MENU_FORMAT = 6;
    public const WISH_MENU_FORMAT = 7;
    public const CURRENT_ORDER_MENU_FORMAT = 8;
    public const TWO_LINES_DIALOG_MENU_FORMAT = 9;
    public const ONE_TWO_DIALOG_MENU_FORMAT = 10;
    public const COMMENT_MENU_FORMAT = 11;
    public const AFTER_COMMENT_MENU_FORMAT = 12;
    public const SPLITBYTWO_MENU_FORMAT = 13;
    public const SPLITBYTWOEXCLUDEFIRST_MENU_FORMAT = 14;
    public const WISH_MENU_FORMAT_WITH_BONUSES = 15;
    public const SPLIT_BY_THREE_EXCLUDE_FIRST = 16;
    public const SPLIT_BY_THREE_EXCLUDE_TWO_LINES = 17;

    public static function format(Collection $buttons, $format = null)
    {
        $format = $buttons->first() instanceof VKKeyboardButton ? $format : ($buttons->first()['config'] ?? null);
        //Если есть конфиг - то форматируем и возвращаем результат
        if ($format) {
            return self::formatByConfig($buttons, $format);
        }
        //Если конфига нет, то в каждой строке по кнопке
        $buttons = $buttons->split($buttons->count());
        return $buttons;
    }

    private static function splitByTwo(Collection $buttons)
    {
        $linesCount = ceil($buttons->count() / 2);
        return $buttons->split($linesCount);
    }

    private static function splitByThree(Collection $buttons)
    {
        $linesCount = ceil($buttons->count() / 3);
        return $buttons->split($linesCount);
    }

    private static function splitByTwoExcludeFirst(Collection $buttons)
    {
        $result = collect([[$buttons->shift()]]);
        return $result->concat(self::splitByTwo($buttons));
    }

    private static function splitByThreeExcludeFirst(Collection $buttons)
    {
        $result = collect([[$buttons->shift()]]);
        return $result->concat(self::splitByThree($buttons));
    }

    private static function splitByThreeExcludeTwoLines(Collection $buttons)
    {
        $result = collect([[$buttons->shift()], [$buttons->shift()]]);
        return $result->concat(self::splitByThree($buttons));
    }


    private static function formatByConfig(Collection $buttons, $config)
    {
        if ($config == self::MAIN_MENU_FORMAT) {
            return self::splitByTwo($buttons);
        }
        if ($config == self::BONUS_MENU_FORMAT) {
            return self::splitByTwo($buttons);
        }
        if ($config == self::CITY_MENU_FORMAT) {
            return self::splitByTwoExcludeFirst($buttons);
        }
        if ($config == self::TAXI_MENU_FORMAT) {
            return self::splitByTwoExcludeFirst($buttons);
        }
        if ($config == self::CHANGE_PRICE_MENU_FORMAT) {
            return self::splitByTwo($buttons);
        }
        if ($config == self::AS_INDICATED_MENU_FORMAT) {
            return self::splitByTwo($buttons);
        }
        if ($config == self::WISH_MENU_FORMAT) {
            $result = collect([[$buttons->shift(), $buttons->shift()], [$buttons->shift()]]);
            if ($buttons->count() == 0) {
                return $result;
            }
            return $result->concat(self::splitByTwoExcludeFirst($buttons));
        }
        if ($config == self::WISH_MENU_FORMAT_WITH_BONUSES) {
            $result = collect([[$buttons->shift(), $buttons->shift()], [$buttons->shift(), $buttons->shift()]]);
            if ($buttons->count() == 0) {
                return $result;
            }
            return $result->concat(self::splitByTwoExcludeFirst($buttons));
        }
        if ($config == self::CURRENT_ORDER_MENU_FORMAT) {
            return self::splitByTwo($buttons);
        }
        if ($config == self::TWO_LINES_DIALOG_MENU_FORMAT) {
            return self::splitByTwo($buttons);
        }
        if ($config == self::ONE_TWO_DIALOG_MENU_FORMAT) {
            return self::splitByTwoExcludeFirst($buttons);
        }
        if ($config == self::COMMENT_MENU_FORMAT) {
            return self::splitByTwoExcludeFirst($buttons);
        }
        if ($config == self::SPLITBYTWO_MENU_FORMAT) {
            return self::splitByTwo($buttons);
        }
        if ($config == self::AFTER_COMMENT_MENU_FORMAT) {
            return self::splitByTwo($buttons);
        }
        if ($config == self::SPLITBYTWOEXCLUDEFIRST_MENU_FORMAT) {
            return self::splitByTwoExcludeFirst($buttons);
        }
        if ($config == self::SPLIT_BY_THREE_EXCLUDE_FIRST) {
            return self::splitByThreeExcludeFirst($buttons);
        }

        if ($config == self::SPLIT_BY_THREE_EXCLUDE_TWO_LINES) {
            return self::splitByThreeExcludeTwoLines($buttons);
        }

        return $buttons->split($buttons->count());
    }
}