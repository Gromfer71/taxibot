<?php

namespace App\Traits;

use App\Services\Address;
use App\Services\Options;

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

    public function getAddressesList()
    {
        return collect(
            Address::getAddresses(
                $this->bot->userStorage()->get('address'),
                (new Options())->getCities(),
                $this->bot->userStorage()
            )
        )->take(Address::MAX_ADDRESSES_COUNT);
    }

}