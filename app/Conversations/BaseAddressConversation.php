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
use Illuminate\Support\Str;
use Throwable;

abstract class BaseAddressConversation extends BaseConversation
{
    use TakingAddressTrait;


    /**
     * Ввод начального адреса пользователя
     *
     * @return \App\Conversations\BaseAddressConversation
     */
    public function getAddress($message, $withFavoriteAddresses = false)
    {
        $this->saveCityInformation();

        $question = ComplexQuestion::createWithSimpleButtons(
            $this->addAddressesToMessage($message),
            [ButtonsStructure::EXIT],
            ['location' => 'addresses']
        );
        // Добавляем в кнопки избранные адреса и адреса из истории
        if ($withFavoriteAddresses) {
            $question = $this->_addAddressFavoriteButtons($question);
        }
        $question = $this->_addAddressHistoryButtons($question);

        return $this->ask($question, function (Answer $answer) use ($withFavoriteAddresses) {
            $this->handleAction($answer->getValue());
            $this->handleFirstAddress($answer, $withFavoriteAddresses);
        });
    }


    /**
     * Меню выбора первого адреса маршрута, после ввода адреса пользователем. Пользователь выбирает из предложенного списка.
     * В зависимости от выбранного адреса бот отправляет в сценарий, если выбрана только улица без номера дома, либо если всё
     * хорошо, то на ввод подъезда. Либо пользователь просто вводит первый адрес снова, тогда он попадает на этот же диалог.
     *
     * @return \App\Conversations\BaseAddressConversation
     * @throws Throwable
     */
    public function getAddressAgain(): BaseAddressConversation
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
            die();
        }

        return $this->ask(
            $question,
            function (Answer $answer) use ($addressesList) {
                $this->handleAction($answer->getValue());
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

        return $this->ask(
            $question,
            function (Answer $answer) {
                $this->handleAction(
                    $answer->getValue(),
                    [ButtonsStructure::GO_AS_INDICATED => 'getEntrance']
                );
                if ($answer->getValue() == ButtonsStructure::BACK) {
                    $this->getAddress(Translator::trans('messages.give me your address'), true);
                    die();
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
            $this->handleAction($answer->getValue(), [ButtonsStructure::BACK]);

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
            $this->handleAction($answer->getValue());
            $this->addEntranceToAddress($answer->getText());
            $this->createAddressHistory($this->getFromStorage('address'));

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
        $addressHistory = $this->getUser()->addresses;

        if ($addressHistory->isNotEmpty()) {
            if ($numberWithoutFavorite) {
                $favoritesAddressesCount = 0;
            } else {
                $favoritesAddressesCount = $this->getUser()->favoriteAddresses->count();
            }

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


}