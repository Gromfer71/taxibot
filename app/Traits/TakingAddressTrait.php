<?php

namespace App\Traits;

use App\Conversations\FavoriteRoutes\TakingAddressForFavoriteRouteConversation;
use App\Models\AddressHistory;
use App\Services\Address;
use App\Services\Options;
use App\Services\OrderApiService;
use App\Services\Translator;
use BotMan\BotMan\Messages\Incoming\Answer;
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
    public function saveFirstAddress($address)
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
            $address['address'],
            $crew_group_id,
            $address['lat'],
            $address['lon'],
            $address['city']
        );
        //OrderApiService::sendDriverLocation($this->getBot(), $address['lat'], $address['lon']);
    }

    public function handleFirstAddress(Answer $answer, $withFavoriteAddresses = false)
    {
        $address = $this->_getAddressFromHistoryByAnswer($answer);

        if ($address) {
            // если выбранный, то сохраняем его и идем дальше
            $this->saveFirstAddress($address);
            if ($this->_hasEntrance($address->address)) {
                $withFavoriteAddresses ? $this->getAddressTo() : $this->getAddressName();
            } else {
                $this->getEntrance();
            }
        } else {
            $this->_saveFirstAddress($answer->getText());
            $addressesList = $this->getAddressesList();
            if ($addressesList->isEmpty()) {
                $this->streetNotFound();
            } else {
                $this->getAddressAgain();
            }
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
            //OrderApiService::sendDriverLocation($this->getBot(), $address['lat'], $address['lon']);
            $this->bot->startConversation(new $this->conversationAfterTakeAddress());
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

    public function saveSecondAddressIfStreet($address)
    {
        $this->bot->userStorage()->save(
            [
                'address' => collect($this->bot->userStorage()->get('address'))->put(
                    1,
                    $address['street']
                )->toArray(),
            ]
        );
    }

    public function handleSecondAddressAgain($addressesList, $answer)
    {
        $address = Address::findByAnswer($addressesList, $answer);

        if ($address) {
            if ($this->isAddressIsStreet($address)) {
                $this->saveSecondAddressIfStreet($address);
                $this->forgetWriteHouse();
            } else {
                if ($this->needToSaveAddressToHistory()) {
                    AddressHistory::newAddress(
                        $this->getUser()->id,
                        Address::toString($address),
                        $address['coords'],
                        $address['city']
                    );
                }

                $this->_saveSecondAddress(
                    Address::toString($address),
                    $address['coords']['lat'],
                    $address['coords']['lon']
                );
                //OrderApiService::sendDriverLocation($this->getBot(), $address['coords']['lat'], $address['coords']['lon']);
                $this->bot->startConversation(new $this->conversationAfterTakeAddress());
            }
        } else {
            $this->_saveSecondAddress($answer->getText());
            $this->getAddressToAgain();
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
            die();
        }
        //OrderApiService::sendDriverLocation($this->getBot(), $address['coords']['lat'], $address['coords']['lon']);
        $crew_group_id = $this->_getCrewGroupIdByCity($address['city']);
        $this->_saveFirstAddress(
            Address::toString($address),
            $crew_group_id,
            $address['coords']['lat'],
            $address['coords']['lon'],
            $address['city']
        );
    }

    public function getAddressToMessage()
    {
        if (Address::haveFirstAddressFromStorageAndFirstAddressesIsReal($this->bot->userStorage())) {
            $message = Translator::trans('messages.user address') . collect(
                    $this->bot->userStorage()->get('address')
                )->first() . ' ' . Translator::trans(
                    $this->conversationAfterTakeAddress == TakingAddressForFavoriteRouteConversation::ADDED_ROUTE_CONVERSATION ? 'messages.give me end address without say to driver button' : 'messages.give me end address'
                );
        } else {
            $message = Translator::trans(
                'messages.ask for second address if first address incorrect',
                ['address' => collect($this->bot->userStorage()->get('address'))->first()]
            );
        }

        return $message;
    }

    public function handleForgetWriteHouse($text)
    {
        $addresses = collect($this->bot->userStorage()->get('address'));
        $lastAddress = $addresses->pop();
        $lastAddressWithEntrance = $lastAddress . $text;
        $addresses = $addresses->push($lastAddressWithEntrance);
        $this->bot->userStorage()->save(['address' => $addresses]);
    }

    /**
     * Добавляет указанный пользователем подъезд к адресу и сохраняет его в кеш
     *
     * @param $entrance
     */
    public function addEntranceToAddress($entrance)
    {
        $address = $this->bot->userStorage()->get('address') . ', *п ' . $entrance;
        $this->bot->userStorage()->save(['address' => $address]);
    }

    /**
     * Сохраняем адрес в историю
     *
     * @param $addressName
     */
    public function createAddressHistory($addressName)
    {
        if ($this->needToSaveAddressToHistory()) {
            AddressHistory::newAddress(
                $this->getUser()->id,
                $addressName,
                [
                    'lat' => $this->bot->userStorage()->get('lat'),
                    'lon' => $this->bot->userStorage()->get('lon'),
                ],
                $this->bot->userStorage()->get('address_city')
            );
        }
    }

    public function isAddressIsStreet($address)
    {
        return $address['kind'] == 'street';
    }

}