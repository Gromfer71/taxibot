<?php


namespace App\Services;


use App\Services\Address;
use BotMan\BotMan\Storages\Storage;
use Illuminate\Support\Collection;

class MessageGeneratorService
{
    public static function getPaymentByBonusesMessage($userStorage){
        $price = $userStorage->get('price');
        $bonusbalance = $userStorage->get('bonusbalance');
        $costBonus = $bonusbalance > $price ? $price : $bonusbalance;
        $costCash = $price - $costBonus;
        $message =' Оплата бонусами - '.$costBonus;
        if ($costCash > 0 ) $message = $message .', оплата наличкой - '.$costCash;
        return  $message;
    }
    public static function getFullOrderInfoFromStorage(Storage $userStorage){
        if(!$userStorage->get('address')) {
            return '';
        }
        $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($userStorage);
        $countAddresses = count($userStorage->get('address'));
        if ($haveEndAddress && $countAddresses == 2 && !$userStorage->get('comment') && !$userStorage->get('wishes') && !$userStorage->get('changed_price')) {
            $route =  collect($userStorage->get('address'))->implode(' - ') ;
            return trans('messages.addres_naznachen_za_bonusi_punkt_21',['route' =>$route,'price' => $userStorage->get('price') ]);
        }
        return self::getFullOrderInfoFromStorageFallback($userStorage);
    }

    public static function getFullOrderInfoFromStorage2(Storage $userStorage){
        if(!$userStorage->get('address')) {
            return '';
        }
        $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($userStorage);
        $countAddresses = count($userStorage->get('address'));
        if ($userStorage->get('usebonus') && $haveEndAddress && $countAddresses == 2 && !$userStorage->get('comment') && !$userStorage->get('wishes') && !$userStorage->get('changed_price')) {
            $bonusbalance = $userStorage->get('bonusbalance');
            $payment = self::getPaymentByBonusesMessage($userStorage);
            return trans('messages.addres_naznachen_za_bonusi_punkt_29',['bonusbalance' =>$bonusbalance,'payment' => $payment]);
        }

        if (!$haveEndAddress  && !$userStorage->get('comment') && !$userStorage->get('wishes') && !$userStorage->get('changed_price')) {
            return trans('messages.skazhu_voditelu_punkt_20');
        }
        return self::getFullOrderInfoFromStorage2Fallback($userStorage);
    }



    public static function getFullOrderInfoFromStorageFallback(Storage $userStorage)
	{
		if(!$userStorage->get('address')) {
			return '';
		}



        if($userStorage->get('additional_address_is_incorrect_change_text_flag') && $userStorage->get('additional_address_is_incorrect_change_text_flag')==1) {
            $data = ['route' => collect($userStorage->get('address'))->implode(' - '),'price' =>  $userStorage->get('price')];
            $userStorage->save(['additional_address_is_incorrect_change_text_flag' => 0]);
            $userStorage->save(['second_address_will_say_to_driver_change_text_flag' => 0]);
            $userStorage->save(['second_address_from_history_incorrect_change_text_flag' => 0]);
            $userStorage->save(['first_address_from_history_incorrect' => 0]);
            return trans('messages.menu without third address',$data);
        }

        if($userStorage->get('second_address_will_say_to_driver_change_text_flag') && $userStorage->get('second_address_will_say_to_driver_change_text_flag')==1) {
            $data = ['address' => collect($userStorage->get('address'))->first(),'price' =>  $userStorage->get('price')];
            $userStorage->save(['second_address_will_say_to_driver_change_text_flag' => 0]);
            if (collect($userStorage->get('address'))->last() == ''){
                return trans('messages.menu without end address',$data);
            } else {
                $data['route'] = collect($userStorage->get('address'))->implode(' - ');
                return trans('messages.menu without end address with route',$data);
            }


        }

        if (   $userStorage->get('second_address_from_history_incorrect_change_text_flag') && $userStorage->get('second_address_from_history_incorrect_change_text_flag')==1){
            $data = ['route' => collect($userStorage->get('address'))->implode(' - '),'price' =>  $userStorage->get('price')];
            $userStorage->save(['second_address_from_history_incorrect_change_text_flag' => 0]);
            return trans('messages.menu without end address with route',$data);
        }


        if($userStorage->get('first_address_from_history_incorrect') && $userStorage->get('first_address_from_history_incorrect')==1 && !(

                $userStorage->get('second_address_from_history_incorrect') && $userStorage->get('second_address_from_history_incorrect')==1


            )) {
            $data = ['address' => collect($userStorage->get('address'))->implode(' - '),'price' =>  $userStorage->get('price')];
            $userStorage->save(['first_address_from_history_incorrect' => 0]);
            return trans('messages.menu with first address from history incorrect',$data);
        }





        if($userStorage->get('second_address_will_say_to_driver_flag') && $userStorage->get('second_address_will_say_to_driver_flag')==1) {
            $message = 'Ваш адрес: ' . collect($userStorage->get('address'))->first() . '.';
        } else {
            $message = 'Ваш маршрут: ' . collect($userStorage->get('address'))->implode(' - ') . '.';
        }








		if($userStorage->get('comment')) {
			$message = $message . ' Комментарий - ' . $userStorage->get('comment') . '. ';
		}

		if($userStorage->get('wishes')) {
			$message = $message . ' Пожелания - ' . collect($userStorage->get('wishes'))->implode(', ') . '. ';
		}

		if($userStorage->get('changed_price')) {
		    $changedPriceValue = $userStorage->get('changed_price')['value'];
			$message = $message . ' Изменение цены ' . ($changedPriceValue > 0 ? '+' : '') . $changedPriceValue .  'р. ';
		}
        if($userStorage->get('changed_price_in_order')) {
            $changedPriceValue = $userStorage->get('changed_price_in_order')['value'];
            $message = $message . ' Изменение цены ' . ($changedPriceValue > 0 ? '+' : '') . $changedPriceValue .  '. ';
        }
        if ($userStorage->get('usebonus')){
            $price = $userStorage->get('price');
            $message = $message . ' Предварительная стоимость поездки - ' . $price . ' рублей. ';
            $bonusbalance = $userStorage->get('bonusbalance');
            $costBonus = $bonusbalance > $price ? $price : $bonusbalance;
            $costCash = $price - $costBonus;
            $message = $message .' Оплата бонусами - '.$costBonus;
            if ($costCash > 0 ) $message = $message .', оплата наличкой - '.$costCash;
        } else {
                $price = $userStorage->get('price');
                $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($userStorage);
                if ($haveEndAddress){
                    $message = $message . ' Стоимость поездки - ' . $price . ' рублей. ';
                } else {
                    $message = $message . ' Предварительная стоимость - ' . $price . ' руб. ';
                }

        }

        $message.=' Выберите варианты ниже.';
		return $message;
	}


    public static function getFullOrderInfoFromStorage2Fallback(Storage $userStorage)
    {
        if(!$userStorage->get('address')) {
            return '';
        }
        $message = '';

        if ($userStorage->get('usebonus')){
            $message = 'Вау! У Вас есть '.$userStorage->get('bonusbalance'). ' бонусов(а)! Ждём-с, я ищу Вам машину. ';
        } else {
            //Если адресов больше 2х, либо есть доп пожелания, либо коммент - то текст с кнопочной гимнастикой, иначе обычный
            if (count($userStorage->get('address')) > 2 || $userStorage->get('comment') || $userStorage->get('wishes') || $userStorage->get('changed_price')){
                $message = trans('messages.komment_i_pozhelanie_skazhu_voditelu_punkt_43');
            }
        }

        if($userStorage->get('second_address_will_say_to_driver_flag') && $userStorage->get('second_address_will_say_to_driver_flag')==1) {
            $message .= 'Ваш адрес по-прежнему: ' . collect($userStorage->get('address'))->first() . '.';
        } else {
            $message .= 'Ваш маршрут по-прежнему: ' . collect($userStorage->get('address'))->implode(' - ') . '.';
        }





        if($userStorage->get('comment')) {
            $message = $message . ' Комментарий - ' . $userStorage->get('comment') . '. ';
        }

        if($userStorage->get('wishes')) {
            $message = $message . ' Пожелания - ' . collect($userStorage->get('wishes'))->implode(', ') . '. ';
        }

        if($userStorage->get('changed_price')) {
            $changedPriceValue = $userStorage->get('changed_price')['value'];
            $message = $message . ' Изменение цены ' . ($changedPriceValue > 0 ? '+' : '') . $changedPriceValue .  '. ';
        }
        if($userStorage->get('changed_price_in_order')) {
            $changedPriceValue = $userStorage->get('changed_price_in_order')['value'];
            $message = $message . ' Изменение цены ' . ($changedPriceValue > 0 ? '+' : '') . $changedPriceValue .  '. ';
        }

        if ($userStorage->get('usebonus')){
            $payment = self::getPaymentByBonusesMessage($userStorage);
            $message = $message .$payment;
        } else {
            $haveEndAddress = Address::haveEndAddressFromStorageAndAllAdressesIsReal($userStorage);
            if ($haveEndAddress){
                $message = $message . ' Стоимость поездки - ' . $userStorage->get('price') . ' рублей. ';
            } else {
                $message = $message . ' Предварительная стоимость - ' . $userStorage->get('price') . ' руб. ';
            }

            $message = $message . ' И наконец-то я с радостью ищу Вам машину!';
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
			return trans('buttons.'.$item);
		});
	}

	public static function escape($str)
    {
        //$str = str_replace('\\', '', $str);
        $str = str_replace('"', "'", $str);

        return addslashes($str);
    }
}