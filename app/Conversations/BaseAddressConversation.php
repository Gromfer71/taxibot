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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

abstract class BaseAddressConversation extends BaseConversation
{

    public function _hasEntrance($address){
        return Str::contains($address, AddressHistory::ENTRANCE_SIGNATURE);
    }

    public function _getCrewGroupIdByCity($city)
    {
        $options = new Options($this->bot->userStorage());
        return $options->getCrewGroupIdFromCity($city);
    }

    public function _getAddressFromHistoryByAnswer($answer)
    {
        $address =  AddressHistory::where(['address' => $answer->getText(), 'user_id' => $this->bot->getUser()->getId()])->get()->first();
        if(!$address) {

            $address = FavoriteAddress::where(['name' => explode('⭐️', $answer->getText())[1] ?? null, 'user_id' => $this->bot->getUser()->getId()])->get()->first();
        }
        if ($address) $address->touch();
        return $address;
    }

    public function _addAddressHistoryButtons($question)
    {
        $addressHistory = AddressHistory::where('user_id', $this->bot->getUser()->getId())
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        if ($addressHistory->isNotEmpty()) {
            foreach ($addressHistory as $address) {
                $question = $question->addButton(Button::create($address->address)->value($address->address));
            }
        }
        return $question;
    }

    public function _addAddressFavoriteButtons($question)
    {
        $favoriteAddresses = FavoriteAddress::where('user_id', $this->getUser()->id)
            ->take(10)
            ->get();

        if ($favoriteAddresses->isNotEmpty()) {
            foreach ($favoriteAddresses as $address) {
                $question = $question->addButton(Button::create('⭐️' . $address->name)->value($address->address));
            }
        }
        return $question;
    }

    public function _addToLastAnotherAddress($answer){

        $data =   collect($this->bot->userStorage()->get('address'));
        $lastAnotherAddress = $data->pop();
        $data =  $data->push($lastAnotherAddress.$answer->getText());


        $this->_sayDebug('Сохраняем дополнительный адрес - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->bot->userStorage()->save( [ 'address' =>$data]);
    }

    public function _forgetLastAddress(){
        $data =  [
            'address' => collect($this->bot->userStorage()->get('address')),
            'lat'     => collect($this->bot->userStorage()->get('lat')),
            'lon'     => collect($this->bot->userStorage()->get('lon'))
        ];
        foreach ($data as $item){
            $item->pop();
        }
        $this->_sayDebug('Забываем введенный адрес - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->bot->userStorage()->save($data);
    }

    public function _saveAnotherAddress($answer,$lat = 0,$lon = 0,$withForgetLast = false){
        if (!is_string($answer)){
            $answer = $answer->getText();
        }
        if ($withForgetLast){
            $data =  [
                'address' => collect($this->bot->userStorage()->get('address')),
                'lat'     => collect($this->bot->userStorage()->get('lat')),
                'lon'     => collect($this->bot->userStorage()->get('lon'))
            ];
            foreach ($data as $item){
                $item->pop();
            }
        } else{
            $data =  [
                'address' => collect($this->bot->userStorage()->get('address')),
                'lat'     => collect($this->bot->userStorage()->get('lat')),
                'lon'     => collect($this->bot->userStorage()->get('lon'))
            ];

        }
        $data['address'] =  $data['address']->push($answer);
        $data['lat'] =  $data['lat']->push($lat);
        $data['lon'] =  $data['lon']->push($lon);

        $this->_sayDebug('Сохраняем дополнительный адрес - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->bot->userStorage()->save($data);
    }

    public function _saveFirstAddress($address, $crew_group_id = false, $lat = 0, $lon = 0,$city='')
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
}