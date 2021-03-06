<?php

namespace App\Conversations;


use App\Models\AddressHistory;
use App\Models\FavoriteAddress;
use App\Models\User;
use App\Services\Address;
use App\Services\Bot\ButtonsStructure;
use App\Services\Bot\ComplexQuestion;
use App\Services\ButtonsFormatterService;
use App\Services\Options;
use App\Services\Translator;
use App\Traits\TakingAddressTrait;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

abstract class BaseAddressConversation extends BaseConversation
{
    use TakingAddressTrait;

    public const ADDRESSES_HISTORY_COUNT = 24;

    public function getActions($replaceActions = []): array
    {
        $actions = [
            ButtonsStructure::ORDER_BY_LOCATION => 'orderByLocation',
        ];

        return parent::getActions(array_replace_recursive($actions, $replaceActions));
    }


    /**
     * Ввод начального адреса пользователя
     *
     * @return \App\Conversations\BaseAddressConversation
     */
    public function getAddress($message = null, $withFavoriteAddresses = false)
    {
        if (!$message) {
            $message = Translator::trans('messages.give me your address');
        }
        $this->saveCityInformation();
        if(get_class($this->getBot()->getDriver())=== VkCommunityCallbackDriver::class) {
            $config = ['location' => 'addresses', 'config' => ButtonsFormatterService::SPLIT_BY_THREE_EXCLUDE_TWO_LINES];
        } else {
            $config = ['location' => 'addresses'];
        }
        $question = ComplexQuestion::createWithSimpleButtons(
            $withFavoriteAddresses ? $this->addAddressesToMessage($message) : $this->addAddressesToMessageOnlyFromHistory($message),
            [$this->backButton() ],
            //['text' => ButtonsStructure::ORDER_BY_LOCATION,
            //                                   'additional' => ['request_location' => true, 'action' => ['type' => 'location']]]
            $config
        );
        // Добавляем в кнопки избранные адреса и адреса из истории
        if ($withFavoriteAddresses) {
            $question = $this->_addAddressFavoriteButtons($question);
        }
        $question = $this->_addAddressHistoryButtons($question, !$withFavoriteAddresses);

        return $this->askForLocation($question, function ($answer) use ($withFavoriteAddresses) {
            $address = $this->getLocation($answer);
            $this->saveFirstAddress($address);
            $this->say(Translator::trans('messages.user address') . ' ' . Arr::get($address, 'address'));
            $this->getEntrance();
        }, function (Answer $answer) use ($withFavoriteAddresses) {
            if ($this->handleAction($answer)) {
                return;
            }

            try {
                $coords = [
                    'lat' => $answer->getMessage()->getLocation()->getLatitude(),
                    'lon' => $answer->getMessage()->getLocation()->getLongitude(),
                ];
            } catch (\Exception $exception) {
                $coords = null;
            }
            if(is_array($coords)) {
                $address = $this->getLocation($answer);
                $this->saveFirstAddress($address);
                $this->say(Translator::trans('messages.user address') . ' ' . Arr::get($address, 'address'));
                $this->getEntrance();
                return;
            }
             $this->handleFirstAddress($answer, $withFavoriteAddresses);
        });
    }

    public function getAddressAgain()
    {
        $addressesList = $this->getAddressesList();
        $question = ComplexQuestion::createWithSimpleButtons(
            $this->addAddressesFromApi(Translator::trans('messages.give address again'), $addressesList),
            [ButtonsStructure::EXIT],
            ['location' => 'addresses']
        );

        $question = ComplexQuestion::setAddressButtons(
            $question,
            $addressesList->map(function ($address) {
                return Address::toString($address);
            })
        );
        if ($addressesList->isEmpty()) {
            $this->streetNotFound();
            return;
        }

        return $this->askForLocation($question, function ($answer) {
            $address = $this->getLocation($answer);
            $this->saveFirstAddress($address);
            $this->getEntrance();
        }, function (Answer $answer) use ($addressesList) {
            if ($this->handleAction($answer)) {
                return;
            }
            $address = Address::findByAnswer($addressesList, $answer);
            if ($address) {
                $this->handleFirstChosenAddress($address);

                $this->getEntrance();
            } else {
                $this->_saveFirstAddress($answer->getText());
                $this->getAddressAgain();
            }
        }
        );
    }

    public function streetNotFound()
    {
        $question = ComplexQuestion::createWithSimpleButtons(
            Translator::trans('messages.not found address dorabotka bota'),
            [ButtonsStructure::BACK, ButtonsStructure::GO_AS_INDICATED, ButtonsStructure::EXIT_TO_MENU],
            ['config' => ButtonsFormatterService::AS_INDICATED_MENU_FORMAT]
        );

        return $this->askForLocation($question, function ($answer) {
            $address = $this->getLocation($answer);
            $this->saveFirstAddress($address);
            $this->getEntrance();
        }, function (Answer $answer) {
            if ($this->handleAction(
                $answer,
                [ButtonsStructure::GO_AS_INDICATED => 'getEntrance']
            )) {
                return;
            }
            if ($answer->getValue() == ButtonsStructure::BACK) {
                $this->getAddress(Translator::trans('messages.give me your address'), true);
                return;
            }
            $this->_saveFirstAddress($answer->getText());
            $this->getAddressAgain();
        }
        );
    }

    public function forgetWriteHouse()
    {
        $question = ComplexQuestion::createWithSimpleButtons(Translator::trans('messages.forget write house'),
                                                             [ButtonsStructure::EXIT]
        );

        return $this->ask($question, function (Answer $answer) {
            if ($this->handleAction($answer, [ButtonsStructure::BACK])) {
                return;
            }

            if (count((array)$this->bot->userStorage()->get('address')) > 1) {
                $this->handleForgetWriteHouse($answer->getText());
                $this->getAddressToAgain();
            } else {
                $this->bot->userStorage()->save(
                    ['address' => $this->bot->userStorage()->get('address') . $answer->getText()]
                );
                $this->getAddressAgain();
            }
        });
    }

    public function getEntrance()
    {
        $question = ComplexQuestion::createWithSimpleButtons(Translator::trans('messages.give entrance'),
                                                             [ButtonsStructure::NO_ENTRANCE, ButtonsStructure::EXIT]
        );

        return $this->ask($question, function (Answer $answer) {
            if ($this->handleAction($answer, [ButtonsStructure::BACK => 'run'])) {
                return;
            }
            $this->addEntranceToAddress($answer->getText());
            if ($this->needToSaveAddressToHistory()) {
                $this->createAddressHistory($this->getFromStorage('address'));
            }

            $this->redirectAfterGetEntrance();
        });
    }

    public function redirectAfterGetEntrance()
    {
    }

    public function _hasEntrance($address)
    {
        return Str::contains($address, AddressHistory::ENTRANCE_SIGNATURE);
    }

    public function _getCrewGroupIdByCity($city)
    {
        $options = new Options();
        return $options->getCrewGroupIdFromCity($city);
    }

    public function _getAddressFromHistoryByAnswer(Answer $answer)
    {
        $address = AddressHistory::getAddressFromAnswer($answer, $this->getUser()->id);

        if (!$address) {
            $address = FavoriteAddress::where(
                ['name' => explode('⭐️', $answer->getText())[1] ?? null, 'user_id' => $this->getUser()->id]
            )->get()->first();
            if (!$address) {
                $address = FavoriteAddress::where(['address' => $answer->getText(), 'user_id' => $this->getUser()->id]
                )->get()->first();
            }
        }

        if ($address) {
            $address->touch();
        }

        return $address;
    }

    public function _addAddressHistoryButtons($question, $numberWithoutFavorite = false)
    {
        if ($numberWithoutFavorite) {
            $favoritesAddressesCount = 0;
        } else {
            $favoritesAddressesCount = $this->getUser()->favoriteAddresses->count();
        }
        $addressHistory = $this->getUser()->addresses->take(self::ADDRESSES_HISTORY_COUNT - $favoritesAddressesCount);

        if ($addressHistory->isNotEmpty()) {
            foreach ($addressHistory as $key => $address) {
                $question = $question->addButton(
                    Button::create($address->address)->value($address->address)->additionalParameters(
                        ['number' => $favoritesAddressesCount + $key + 1]
                    )
                );
            }
        }
        return $question;
    }

    public function _addAddressFavoriteButtons($question)
    {
        $favoriteAddresses = $this->getUser()->favoriteAddresses;

        if ($favoriteAddresses->isNotEmpty()) {
            foreach ($favoriteAddresses as $key => $address) {
                $question = $question->addButton(
                    Button::create('⭐️' . $address->name)->value($address->address)->additionalParameters(
                        ['number' => $key + 1]
                    )
                );
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
            'lon' => collect($this->bot->userStorage()->get('lon')),
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
                'lon' => collect($this->bot->userStorage()->get('lon')),
            ];
            foreach ($data as $item) {
                $item->pop();
            }
        } else {
            $data = [
                'address' => collect($this->bot->userStorage()->get('address')),
                'lat' => collect($this->bot->userStorage()->get('lat')),
                'lon' => collect($this->bot->userStorage()->get('lon')),
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
            'address' => collect($this->bot->userStorage()->get('address'))->put(
                1,
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

    public function needToSaveAddressToHistory()
    {
        return !$this->getFromStorage('dont_save_address_to_history');
    }

    public function backButton()
    {
        return ButtonsStructure::EXIT;
    }


}