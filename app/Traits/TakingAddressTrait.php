<?php

namespace App\Traits;

use App\Conversations\TaxiMenuConversation;
use App\Services\Address;
use App\Services\Options;
use App\Services\Translator;
use Throwable;
use Tightenco\Collect\Support\Collection;

/**
 * Трейт для работы с адресами
 */
trait TakingAddressTrait
{
    /**
     * Сохраняем информацию о городе в кеш
     */
    public function saveCityInformation()
    {
        $this->saveToStorage(['crew_group_id' => $this->options->getDistrictFromCity($this->getUser()->city)]);
        $this->saveToStorage(['district' => $this->options->getDistrictFromCity($this->getUser()->city)]);
        $this->saveToStorage(['city' => $this->getUser()->city]);
    }

    /**
     * @param  $address
     */
    public function handleFirstAddress($address)
    {
        if ($address['city'] == '') {
            $crew_group_id = false;
        } else {
            $crew_group_id = $this->_getCrewGroupIdByCity($address['city']);
        }
        if ($address['lat'] == 0) {
            $this->bot->userStorage()->save(['first_address_from_history_incorrect' => 1]);
        }

        $this->_saveFirstAddress(
            $address->address,
            $crew_group_id,
            $address['lat'],
            $address['lon'],
            $address['city']
        );
        if ($this->_hasEntrance($address->address)) {
            $this->getAddressTo();
        } else {
            $this->getEntrance();
        }
    }

    public function handleSecondAddress($answer)
    {
        $address = $this->_getAddressFromHistoryByAnswer($answer);
        if ($address) {
            $this->_saveSecondAddress($address->address, $address['lat'], $address['lon']);
            if ($address['lat'] == 0) {
                $this->bot->userStorage()->save(['second_address_from_history_incorrect' => 1]);
            }
            if ($address['lat'] == 0) {
                $this->bot->userStorage()->save(['second_address_from_history_incorrect_change_text_flag' => 1]
                );
            }
            $this->bot->startConversation(new TaxiMenuConversation());
            return;
        } else {
            $this->_saveSecondAddress($answer->getText());
            $addressesList = collect(
                Address::getAddresses(
                    $answer->getText(),
                    (new Options())->getCities(),
                    $this->bot->userStorage()
                )
            );
            if ($addressesList->isEmpty()) {
                $this->streetNotFoundAddressTo();
            } else {
                $this->getAddressToAgain();
            }
        }
    }

    /**
     * Возвращает коллекцию адресов из api, похожих на те, что ввел пользователь
     *
     * @return \Illuminate\Support\Collection|Collection
     * @throws Throwable
     */
    public function getAddressesList($fromAddress = 0)
    {
        return collect(
            Address::getAddresses(
                collect($this->bot->userStorage()->get('address'))->get($fromAddress),
                (new Options())->getCities(),
                $this->bot->userStorage()
            )
        )->take(Address::MAX_ADDRESSES_COUNT);
    }

    /**
     * Обработка выбранного из списка первого адреса
     */
    public function handleFirstChosenAddress(array $address)
    {
        if ($address['kind'] == 'street') {
            $this->bot->userStorage()->save(['address' => $address['street']]);
            $this->forgetWriteHouse();
            return;
        }
        $crew_group_id = $this->_getCrewGroupIdByCity($address['city']);
        $this->_saveFirstAddress(
            Address::toString($address),
            $crew_group_id,
            $address['coords']['lat'],
            $address['coords']['lon'],
            $address['city']
        );
        $this->getEntrance();
    }

    public function getAddressToMessage()
    {
        if (Address::haveFirstAddressFromStorageAndFirstAdressesIsReal($this->bot->userStorage())) {
            $message = Translator::trans('messages.user address') . collect($this->bot->userStorage()->get('address'))->first() . ' ' . Translator::trans('messages.give me end address');
        } else {
            $message = Translator::trans(
                'messages.ask for second address if first address incorrect',
                ['address' => collect($this->bot->userStorage()->get('address'))->first()]
            );
        }

        return $message;
    }

}