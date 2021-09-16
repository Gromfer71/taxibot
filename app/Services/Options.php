<?php


namespace App\Services;

use App\Models\Config;
use App\Models\Log;
use BotMan\BotMan\Storages\Storage;

/**
 * Получаем настройки бота с файла. По коду обращаемся к данным настройкам
 */
class Options
{
	private $options;
	public function __construct()
	{
		if(!Config::getTaxibotConfig()) {
			$this->options = json_decode(file_get_contents('https://sk-taxi.ru/tmfront/config.json'));
			
		} else {
			$this->options = Config::getTaxibotConfig();
		}
	}

	public function getCities()
	{
		return $this->options->cities;
	}

	public function getWishes()
	{
		return ['wishOptions' => $this->options->wishOptions,
                'carOptions' => $this->options->carOptions,
                'changeOptions' => $this->options->changeOptions];
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
		if(!$currentCity) {
			return  $this->options->overpriceOptions;
		}

		foreach ($this->options->overpriceOptions as $key => $option) {
			if(isset($option->exclude)) {
				foreach ($option->exclude as $city) {
					if($city->city == $currentCity) {
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

        if (config('app.debug')) {
            $orderParams->push(78);
        }


		if($storage->get('changed_price')) {
			$orderParams->push($storage->get('changed_price')['id']);
		}
        if($storage->get('changed_price_in_order')) {
            $orderParams->push($storage->get('changed_price_in_order')['id']);
        }

        $orderParams->push($storage->get('platform_option'));

		$options = collect($this->options->wishOptions)->merge(collect($this->options->carOptions))->merge(collect($this->options->changeOptions));
		foreach ((array)$storage->get('wishes') as $wish) {

		    $wishItem = $options->filter(function ($item) use ($wish) {
                if($item->name == $wish) {
                    return $item;
                }
            })->first();

		    if ($wishItem) $orderParams->push($wishItem->id);

		}
		$orderParams = $orderParams->filter();

		return $orderParams;
	}

	public function getCrewGroupIdFromCity($city)
	{
		$cities = collect($this->options->cities);
		$result = $cities->filter(function ($item) use ($city) {
			if($item->name == $city) {
				return $item;
			}
		});

		return $result->first()->crewGroupId ?? 25;
	}
    public function getDistrictFromCity($city)
    {
        $cities = collect($this->options->cities);
        $result = $cities->filter(function ($item) use ($city) {
            if($item->name == $city) {
                return $item;
            }
        });

        return $result->first()->district ?? '';
    }




}