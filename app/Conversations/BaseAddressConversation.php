<?php

namespace App\Conversations;


use App\Models\AddressHistory;
use App\Models\FavoriteAddress;
use App\Models\Log;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\Address;
use App\Services\Options;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class BaseAddressConversation extends BaseConversation
{

    public function _hasEntrance($address)
    {
        return Str::contains($address, AddressHistory::ENTRANCE_SIGNATURE);
    }

    public function _getCrewGroupIdByCity($city)
    {
        $options = new Options($this->bot->userStorage());
        return $options->getCrewGroupIdFromCity($city);
    }

    public function _getAddressFromHistoryByAnswer(Answer $answer)
    {
        $address = AddressHistory::getAddressFromAnswer($answer);

        if (!$address) {
            $address = FavoriteAddress::where(['name' => explode('⭐️', $answer->getText())[1] ?? null, 'user_id' => $this->getUser()->id])->get()->first();
            if (!$address) {
                $address = FavoriteAddress::where(['address' => $answer->getText(), 'user_id' => $this->getUser()->id])->get()->first();
            }
        }

        if ($address) $address->touch();

        return $address;
    }

    public function _addAddressHistoryButtons($question, $numberWithoutFavorite = false)
    {
        $addressHistory = $this->getUser()->addresses;

        if ($addressHistory->isNotEmpty()) {

            if ($numberWithoutFavorite) {
                $favoritesAddressesCount = 0;
            } else {
                $favoritesAddressesCount = $this->getUser()->favoriteAddresses->count();

            }

            foreach ($addressHistory as $key => $address) {
                $question = $question->addButton(Button::create($address->address)->value($address->address)->additionalParameters(['number' => $favoritesAddressesCount + $key + 1]));
            }
        }
        return $question;
    }

    public function _addAddressFavoriteButtons($question)
    {
        $favoriteAddresses = $this->getUser()->favoriteAddresses;

        if ($favoriteAddresses->isNotEmpty()) {
            foreach ($favoriteAddresses as $key => $address) {
                $question = $question->addButton(Button::create('⭐️' . $address->name)->value($address->address)->additionalParameters(['number' => $key + 1]));
            }
        }
        return $question;
    }

    public function _addToLastAnotherAddress($answer)
    {

        $data = collect($this->bot->userStorage()->get('address'));
        $lastAnotherAddress = $data->pop();
        $data = $data->push($lastAnotherAddress . $answer->getText());


        $this->_sayDebug('Сохраняем дополнительный адрес - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->bot->userStorage()->save(['address' => $data]);
    }

    public function _forgetLastAddress()
    {
        $data = [
            'address' => collect($this->bot->userStorage()->get('address')),
            'lat' => collect($this->bot->userStorage()->get('lat')),
            'lon' => collect($this->bot->userStorage()->get('lon'))
        ];
        foreach ($data as $item) {
            $item->pop();
        }
        $this->_sayDebug('Забываем введенный адрес - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->bot->userStorage()->save($data);
    }

    public function _saveAnotherAddress($answer, $lat = 0, $lon = 0, $withForgetLast = false)
    {
        if (!is_string($answer)) {
            $answer = $answer->getText();
        }
        if ($withForgetLast) {
            $data = [
                'address' => collect($this->bot->userStorage()->get('address')),
                'lat' => collect($this->bot->userStorage()->get('lat')),
                'lon' => collect($this->bot->userStorage()->get('lon'))
            ];
            foreach ($data as $item) {
                $item->pop();
            }
        } else {
            $data = [
                'address' => collect($this->bot->userStorage()->get('address')),
                'lat' => collect($this->bot->userStorage()->get('lat')),
                'lon' => collect($this->bot->userStorage()->get('lon'))
            ];

        }
        $data['address'] = $data['address']->push($answer);
        $data['lat'] = $data['lat']->push($lat);
        $data['lon'] = $data['lon']->push($lon);

        $this->_sayDebug('Сохраняем дополнительный адрес - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->bot->userStorage()->save($data);
    }

    public function _saveFirstAddress($address, $crew_group_id = false, $lat = 0, $lon = 0, $city = '')
    {
        if (!$crew_group_id) {
            $user = User::find($this->bot->getUser()->getId());
            $crew_group_id = $this->_getCrewGroupIdByCity($user->city ?? null);
        }
        $data = [
            'address' => $address,
            'crew_group_id' => $crew_group_id,
            'lat' => $lat,
            'lon' => $lon,
            'address_city' => $city,
        ];
        $this->_sayDebug('Сохраняем первый адрес - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->bot->userStorage()->save($data);
    }

    public function _saveSecondAddress($address, $lat = 0, $lon = 0)
    {
        $this->_saveSecondAddressByText($address, $lat, $lon);
    }

    public function _saveSecondAddressByText($text, $lat = 0, $lon = 0)
    {
        $data = [
            'address' => collect($this->bot->userStorage()->get('address'))->put(1,
                $text
            )->toArray(),
            'lat' => collect($this->bot->userStorage()->get('lat'))->put(
                1,
                $lat
            ),
            'lon' => collect($this->bot->userStorage()->get('lon'))->put(
                1,
                $lon
            ),
        ];
        $this->_sayDebug('Сохраняем второй адрес - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->bot->userStorage()->save($data);
    }

    public function addAddressesToMessage($questionText)
    {
        if (property_exists($this->bot->getDriver(), 'needToAddAddressesToMessage')) {
            $questionText .= "\n";
            $this->_sayDebug('property exists');
            foreach ($this->getUser()->favoriteAddresses as $key => $address) {
                $questionText .= $key + 1 . ' ⭐️ ' . $address->name . ' ' . $address->address . "\n";
            }

            $key = $this->getUser()->favoriteAddresses->count();

            foreach ($this->getUser()->addresses as $historyAddressKey => $address) {
                $questionText .= $historyAddressKey + $key + 1 . ' ' . $address->address . "\n";
            }
        }

        return $questionText;
    }

    public function addAddressesToMessageOnlyFromHistory($questionText)
    {
        if (property_exists($this->bot->getDriver(), 'needToAddAddressesToMessage')) {
            $questionText .= "\n";

            foreach ($this->getUser()->addresses as $historyAddressKey => $address) {
                $questionText .= $historyAddressKey + 1 . ' ' . $address->address . "\n";
            }
        }

        return $questionText;
    }

    public function addAddressesFromApi($questionText, $addresses)
    {
        if (property_exists($this->bot->getDriver(), 'needToAddAddressesToMessage')) {
            $questionText .= "\n";
            $addresses = $addresses->values();

            foreach ($addresses as $key => $address) {
                $questionText .= $key + 1 . ' ' . Address::toString($address) . "\n";
            }
        }

        return $questionText;
    }
}