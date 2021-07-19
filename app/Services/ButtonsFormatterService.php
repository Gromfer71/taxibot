<?php

namespace App\Services;


use BotMan\BotMan\Storages\Storage;
use Illuminate\Support\Collection;

class ButtonsFormatterService
{
    const MAIN_MENU_FORMAT = 1;
    const BONUS_MENU_FORMAT = 2;
    const CITY_MENU_FORMAT = 3;
    const TAXI_MENU_FORMAT = 4;
    const CHANGE_PRICE_MENU_FORMAT = 5;
    const AS_INDICATED_MENU_FORMAT = 6;
    const WISH_MENU_FORMAT = 7;
    const CURRENT_ORDER_MENU_FORMAT = 8;
    const TWO_LINES_DIALOG_MENU_FORMAT = 9;
    const ONE_TWO_DIALOG_MENU_FORMAT = 10;
    const COMMENT_MENU_FORMAT = 11;
    const AFTER_COMMENT_MENU_FORMAT = 12;
    const SPLITBYTWO_MENU_FORMAT = 13;
    const SPLITBYTWOEXCLUDEFIRST_MENU_FORMAT = 14;
    const WISH_MENU_FORMAT_WITH_BONUSES =15;

    private static function splitByTwo(Collection $buttons){
        $linesCount = ceil($buttons->count()/2);
        return $buttons->split($linesCount);
    }

    private static function splitByTwoExcludeFirst(Collection $buttons){
        $result = collect([[$buttons->shift()]]);
        return  $result->concat(self::splitByTwo($buttons));
    }

    private static function formatByConfig(Collection $buttons, $config)
    {
        if ($config == self::MAIN_MENU_FORMAT){
            return self::splitByTwo($buttons);
        }
        if ($config == self::BONUS_MENU_FORMAT){
            return self::splitByTwo($buttons);
        }
        if ($config == self::CITY_MENU_FORMAT){
            return self::splitByTwoExcludeFirst($buttons);
        }
        if ($config == self::TAXI_MENU_FORMAT){
            return self::splitByTwoExcludeFirst($buttons);
        }
        if ($config == self::CHANGE_PRICE_MENU_FORMAT){
            return self::splitByTwo($buttons);
        }
        if ($config == self::AS_INDICATED_MENU_FORMAT){
            return self::splitByTwo($buttons);
        }
        if ($config == self::WISH_MENU_FORMAT){
            $result = collect([[$buttons->shift(),$buttons->shift()],[$buttons->shift()]]);
            if ($buttons->count() == 0) return $result;
            return  $result->concat(self::splitByTwoExcludeFirst($buttons));
        }
        if ($config == self::WISH_MENU_FORMAT_WITH_BONUSES){
            $result = collect([[$buttons->shift(),$buttons->shift()],[$buttons->shift(),$buttons->shift()]]);
            if ($buttons->count() == 0) return $result;
            return  $result->concat(self::splitByTwoExcludeFirst($buttons));
        }
        if ($config == self::CURRENT_ORDER_MENU_FORMAT){
            return self::splitByTwo($buttons);
        }
        if ($config == self::TWO_LINES_DIALOG_MENU_FORMAT){
            return self::splitByTwo($buttons);
        }
        if ($config == self::ONE_TWO_DIALOG_MENU_FORMAT){
            return self::splitByTwoExcludeFirst($buttons);
        }
        if ($config == self::COMMENT_MENU_FORMAT){
            return self::splitByTwoExcludeFirst($buttons);
        }
        if ($config == self::SPLITBYTWO_MENU_FORMAT){
            return self::splitByTwo($buttons);
        }
        if ($config == self::AFTER_COMMENT_MENU_FORMAT){
            return self::splitByTwo($buttons);
        }
        if ($config == self::SPLITBYTWOEXCLUDEFIRST_MENU_FORMAT){
            return self::splitByTwoExcludeFirst($buttons);
        }

        return $buttons->split($buttons->count());
    }

    public static function format(Collection $buttons, $format = null)
    {
        if(!$format) {
            $firstButton = $buttons->first();
            if (isset($firstButton['config'])) {
                return self::formatByConfig($buttons, $firstButton['config']);
            } else {
                //$buttons = $buttons->split($buttons->count());
                return $buttons;
            }
        } else {
            return self::formatByConfig($buttons, $format);
        }


        $buttons = $buttons->split($buttons->count());
        return $buttons;
    }
}