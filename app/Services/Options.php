<?php


namespace App\Services;

use App\Models\Config;
use App\Models\User;
use BotMan\BotMan\Storages\Storage;

/**
 * Получаем настройки бота с файла. По коду обращаемся к данным настройкам
 */
class Options
{
    private $options;

    public function __construct()
    {
        if (!Config::getTaxibotConfig()) {
            $this->options = json_decode(file_get_contents('https://sk-taxi.ru/tmfront/config.json'));
        } else {
            $this->options = json_decode(Config::getTaxibotConfig());
        }
    }

    public function getWishes()
    {
        return [
            'wishOptions' => $this->options->wishOptions,
            'carOptions' => $this->options->carOptions,
            'changeOptions' => $this->options->changeOptions
        ];
    }

    public function getChangePriceOptions()
    {
        return $this->options->overpriceOptions;
    }

    public function getChangePriceOptionsInOrderMenu()
    {
        return $this->options->overpriceOptionsAfterOrderCreated;
    }

    public function filterChangePriceOptions($currentCity)
    {
        if (!$currentCity) {
            return $this->options->overpriceOptions;
        }

        foreach ($this->options->overpriceOptions as $key => $option) {
            if (isset($option->exclude)) {
                foreach ($option->exclude as $city) {
                    if ($city->city == $currentCity) {
                        unset($this->options->overpriceOptions[$key]);
                    }
                }
            }
        }

        return $this->options->overpriceOptions;
    }

    public function getMinPrice()
    {
        return $this->options->minPrice;
    }

    public function getOrderParamsArray(Storage $storage)
    {
        $orderParams = collect();

        $user = User::find($storage->get('user_id'));
        if (config('app.debug') || $user->phone == '9618690400') {
            $orderParams->push(78);
        }


        if ($storage->get('changed_price')) {
            $orderParams->push($storage->get('changed_price')['id']);
        }

        if ($storage->get('changed_price_in_order')) {
            $orderParams->push($storage->get('changed_price_in_order')['id']);
        }

        $orderParams->push($storage->get('platform_option'));

        $options = collect($this->options->wishOptions)->merge(collect($this->options->carOptions))->merge(
            collect($this->options->changeOptions)
        );
        foreach ((array)$storage->get('wishes') as $wish) {
            $wishItem = $options->filter(function ($item) use ($wish) {
                if ($item->id == $wish) {
                    return $item;
                }
            })->first();

            if ($wishItem) {
                $orderParams->push($wishItem->id);
            }
        }
        $orderParams = $orderParams->filter();

        return $orderParams->toArray();
    }

    public function getCrewGroupIdFromCity($city)
    {
        $cities = collect($this->options->cities);
        $result = $cities->filter(function ($item) use ($city) {
            if ($item->name == $city) {
                return $item;
            }
        });

        return $result->first()->crewGroupId ?? 25;
    }

    public function getCityFromCrewId($crewId)
    {
        $cities = collect($this->options->cities);
        $result = $cities->filter(function ($item) use ($crewId) {
            if ($item->crewGroupId == $crewId) {
                return $item;
            }
        });

        return $result->first()->name ?? null;
    }

    public function getDistrictFromCity($city)
    {
        $cities = collect($this->options->cities);
        $result = $cities->filter(function ($item) use ($city) {
            if ($item->name == $city) {
                return $item;
            }
        });

        return $result->first()->district ?? '';
    }

    /**
     * Проверяет, является ли введенный пользователем текст городом из списка, взятого из конфига
     *
     * @param $userInput
     * @return bool
     */
    public function isUserInputIsCity($userInput): bool
    {
        return in_array($userInput, $this->getCitiesArray());
    }

    /**
     * Возвращает коллекцию имён городов взятых из конфига
     *
     * @return array
     */
    public function getCitiesArray(): array
    {
        $cities = collect($this->getCities());
        return $cities->map(function ($city) {
            return $city->name;
        })->toArray();
    }

    public function getCities()
    {
        return $this->options->cities;
    }

    public function getChangedPrice($id)
    {
        $options = collect($this->options->overpriceOptions)->merge($this->options->overpriceOptionsAfterOrderCreated);

        return $options->where('id', $id)->first() ?: null;
    }

    public function isOrderParamWish($id)
    {
        $options = collect($this->options->wishOptions)->merge($this->options->carOptions)->merge($this->options->changeOptions);

        return $options->where('id', $id)->first() ?: null;
    }


}