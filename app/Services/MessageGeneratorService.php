<?php


namespace App\Services;


use BotMan\BotMan\Storages\Storage;
use Illuminate\Support\Collection;

class MessageGeneratorService
{
    public static function getPaymentByBonusesMessage($userStorage)
    {
        $price = $userStorage->get('price');
        $bonusbalance = $userStorage->get('bonusbalance');
        $costBonus = $bonusbalance > $price ? $price : $bonusbalance;
        $costCash = $price - $costBonus;
        $message = ' ' . Translator::trans('messages.payment with bonuses') . ' - ' . $costBonus;
        if ($costCash > 0) {
            $message = $message . ', ' . Translator::trans('messages.payment with cash') . ' - ' . $costCash;
        }
        return $message;
    }

    public static function getFullOrderInfoFromStorage(Storage $userStorage)
    {
        if (!$userStorage->get('address')) {
            return '';
        }
        $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($userStorage);
        $countAddresses = count($userStorage->get('address'));
        if ($haveEndAddress && $countAddresses == 2 && !$userStorage->get('comment') && !$userStorage->get('wishes') && !$userStorage->get('changed_price')) {
            $route = MessageGeneratorService::implodeAddress(collect($userStorage->get('address')));
            return Translator::trans('messages.addres_naznachen_za_bonusi_punkt_21', ['route' => $route, 'price' => $userStorage->get('price')]);
        }
        return self::getFullOrderInfoFromStorageFallback($userStorage);
    }

    public static function getFullOrderInfoFromStorage2(Storage $userStorage)
    {
        if (!$userStorage->get('address')) {
            return '';
        }
        $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($userStorage);
        $countAddresses = count($userStorage->get('address'));
        if ($userStorage->get('usebonus') && $haveEndAddress && $countAddresses == 2 && !$userStorage->get('comment') && !$userStorage->get('wishes') && !$userStorage->get('changed_price')) {
            $bonusbalance = $userStorage->get('bonusbalance');
            $payment = self::getPaymentByBonusesMessage($userStorage);
            return Translator::trans('messages.addres_naznachen_za_bonusi_punkt_29', ['bonusbalance' => $bonusbalance, 'payment' => $payment]);
        }

        if (!$haveEndAddress && !$userStorage->get('comment') && !$userStorage->get('wishes') && !$userStorage->get('changed_price')) {
            return Translator::trans('messages.skazhu_voditelu_punkt_20');
        }
        return self::getFullOrderInfoFromStorage2Fallback($userStorage);
    }


    public static function getFullOrderInfoFromStorageFallback(Storage $userStorage)
    {
        if (!$userStorage->get('address')) {
            return '';
        }


        if ($userStorage->get('additional_address_is_incorrect_change_text_flag') && $userStorage->get('additional_address_is_incorrect_change_text_flag') == 1) {
            $data = ['route' => MessageGeneratorService::implodeAddress(collect($userStorage->get('address'))), 'price' => $userStorage->get('price')];
            $userStorage->save(['additional_address_is_incorrect_change_text_flag' => 0]);
            $userStorage->save(['second_address_will_say_to_driver_change_text_flag' => 0]);
            $userStorage->save(['second_address_from_history_incorrect_change_text_flag' => 0]);
            $userStorage->save(['first_address_from_history_incorrect' => 0]);
            return Translator::trans('messages.menu without third address', $data);
        }

        if ($userStorage->get('second_address_will_say_to_driver_change_text_flag') && $userStorage->get('second_address_will_say_to_driver_change_text_flag') == 1) {
            $data = ['address' => collect($userStorage->get('address'))->first(), 'price' => $userStorage->get('price')];
            $userStorage->save(['second_address_will_say_to_driver_change_text_flag' => 0]);
            if (collect($userStorage->get('address'))->last() == '') {
                return Translator::trans('messages.menu without end address', $data);
            } else {
                $data['route'] = MessageGeneratorService::implodeAddress(collect($userStorage->get('address')));
                return Translator::trans('messages.menu without end address with route', $data);
            }
        }

        if ($userStorage->get('second_address_from_history_incorrect_change_text_flag') && $userStorage->get('second_address_from_history_incorrect_change_text_flag') == 1) {
            $data = ['route' => MessageGeneratorService::implodeAddress(collect($userStorage->get('address'))), 'price' => $userStorage->get('price')];
            $userStorage->save(['second_address_from_history_incorrect_change_text_flag' => 0]);
            return Translator::trans('messages.menu without end address with route', $data);
        }


        if ($userStorage->get('first_address_from_history_incorrect') && $userStorage->get('first_address_from_history_incorrect') == 1 && !(

                $userStorage->get('second_address_from_history_incorrect') && $userStorage->get('second_address_from_history_incorrect') == 1


            )) {
            $data = ['address' => MessageGeneratorService::implodeAddress(collect($userStorage->get('address'))), 'price' => $userStorage->get('price')];
            $userStorage->save(['first_address_from_history_incorrect' => 0]);
            return Translator::trans('messages.menu with first address from history incorrect', $data);
        }


        if ($userStorage->get('second_address_will_say_to_driver_flag') && $userStorage->get('second_address_will_say_to_driver_flag') == 1) {
            $message = Translator::trans('messages.your address') . ' ' . collect($userStorage->get('address'))->first() . '.';
        } else {
            $message = Translator::trans('messages.your route') . ' ' . MessageGeneratorService::implodeAddress(collect($userStorage->get('address'))) . '.';
        }


        if ($userStorage->get('comment')) {
            $message = $message . ' ' . Translator::trans('messages.comment') . ' - ' . $userStorage->get('comment') . '. ';
        }

        if ($userStorage->get('wishes')) {
            $message = $message . ' ' . Translator::trans('messages.wishes') . ' - ' . MessageGeneratorService::implodeWishes(collect($userStorage->get('wishes'))) . '. ';
        }

        if ($userStorage->get('changed_price')) {
            $changedPriceValue = $userStorage->get('changed_price')['value'];
            $message = $message . ' ' . Translator::trans('messages.price change') . ' ' . ($changedPriceValue > 0 ? '+' : '') . $changedPriceValue . ' ' . Translator::trans(
                    'messages.currency short'
                );
        }
        if ($userStorage->get('changed_price_in_order')) {
            $changedPriceValue = $userStorage->get('changed_price_in_order')['value'];
            $message = $message . ' ' . Translator::trans('messages.price change') . ' ' . ($changedPriceValue > 0 ? '+' : '') . $changedPriceValue . '. ';
        }
        if ($userStorage->get('usebonus')) {
            $price = $userStorage->get('price');
            $message = $message . ' ' . Translator::trans('messages.estimated order cost') . ' - ' . $price . ' ' . Translator::trans('messages.currency') . '. ';
            $bonusbalance = $userStorage->get('bonusbalance');
            $costBonus = $bonusbalance > $price ? $price : $bonusbalance;
            $costCash = $price - $costBonus;
            $message = $message . ' ' . Translator::trans('messages.payment with bonuses') . ' - ' . $costBonus;
            if ($costCash > 0) {
                $message = $message . ', ' . Translator::trans('messages.payment with cash') . ' - ' . $costCash;
            }
        } else {
            $price = $userStorage->get('price');
            $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($userStorage);
            if ($haveEndAddress) {
                $message = $message . ' ' . Translator::trans('messages.cost') . ' - ' . $price . ' ' . Translator::trans('messages.currency') . '. ';
            } else {
                $message = $message . ' ' . Translator::trans('messages.estimated order cost') . ' - ' . $price . ' ' . Translator::trans('messages.currency short');
            }
        }

        $message .= ' ' . Translator::trans('messages.make your choice');
        return $message;
    }


    public static function getFullOrderInfoFromStorage2Fallback(Storage $userStorage)
    {
        if (!$userStorage->get('address')) {
            return '';
        }
        $message = '';

        if ($userStorage->get('usebonus')) {
            $message = Translator::trans('messages.bonus balance and searching auto', ['bonuses' => $userStorage->get('bonusbalance')]);
        } elseif (count($userStorage->get('address')) > 2 || $userStorage->get('comment') || $userStorage->get('wishes') || $userStorage->get('changed_price')) {
            $message = Translator::trans('messages.komment_i_pozhelanie_skazhu_voditelu_punkt_43');
        }


        if ($userStorage->get('second_address_will_say_to_driver_flag') && $userStorage->get('second_address_will_say_to_driver_flag') == 1) {
            $message .= Translator::trans('messages.your address is still') . ': ' . collect($userStorage->get('address'))->first() . '.';
        } else {
            $message .= ' ' . Translator::trans('messages.your route is still') . ': ' . MessageGeneratorService::implodeAddress(collect($userStorage->get('address'))) . '.';
        }


        if ($userStorage->get('comment')) {
            $message = $message . ' ' . Translator::trans('messages.comment') . ' - ' . $userStorage->get('comment') . '. ';
        }

        if ($userStorage->get('wishes')) {
            $message = $message . ' ' . Translator::trans('messages.wishes') . ' - ' . MessageGeneratorService::implodeWishes(collect($userStorage->get('wishes'))) . '. ';
        }

        if ($userStorage->get('changed_price')) {
            $changedPriceValue = $userStorage->get('changed_price')['value'];
            $message = $message . ' ' . Translator::trans('messages.price change') . ' ' . ($changedPriceValue > 0 ? '+' : '') . $changedPriceValue . '. ';
        }
        if ($userStorage->get('changed_price_in_order')) {
            $changedPriceValue = $userStorage->get('changed_price_in_order')['value'];
            $message = $message . ' ' . Translator::trans('messages.price change') . ' ' . ($changedPriceValue > 0 ? '+' : '') . $changedPriceValue . '. ';
        }

        if ($userStorage->get('usebonus')) {
            $payment = self::getPaymentByBonusesMessage($userStorage);
            $message = $message . $payment;
        } else {
            $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($userStorage);
            if ($haveEndAddress) {
                $message = $message . ' ' . Translator::trans('messages.cost') . ' - ' . $userStorage->get('price') . ' ' . Translator::trans('messages.currency') . '. ';
            } else {
                $message = $message . ' ' . Translator::trans('messages.estimated order cost') . ' - ' . $userStorage->get('price') . ' ' . Translator::trans('messages.currency short') . ' ';
            }

            $message = $message . ' ' . Translator::trans('messages.searching auto first');
        }


        return $message;
    }

    /**
     * @param $wishes
     * @return Collection
     */
    public static function wishesTrans($wishes)
    {
        return $wishes->map(function ($item) {
            return Translator::trans('buttons.' . $item);
        });
    }

    public static function escape($str)
    {
        //$str = str_replace('\\', '', $str);
        $str = str_replace('"', "'", $str);

        return addslashes($str);
    }

    /**
     * @param Collection $addresses
     * @return string
     */
    public static function implodeAddress($addresses)
    {
        return $addresses->implode('👉') . '👉';
    }

    /**
     * @param Collection $wishes
     * @return string
     */
    public static function implodeWishes($wishes)
    {
        $wishes = $wishes->transform(function ($item) {
            return Translator::trans('buttons.wish #' . $item);
        });

        return '❗️' . $wishes->implode('❗️');
    }
}