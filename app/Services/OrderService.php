<?php

namespace App\Services;

use BotMan\BotMan\Storages\Storage;
use Exception;

class OrderService
{
    /** @var \BotMan\BotMan\Storages\Storage */
    private $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function calcPrice()
    {
        $options = new Options();
        $crewGroupId = collect($this->getStorage()->get('crew_group_id'))->first();

        if (!$crewGroupId) {
            $city = User::find($this->getStorage()->get('user_id'))->city;
            $crewGroupId = $options->getCrewGroupIdFromCity($city ?? null);
        }
        $api = new OrderApiService();
        $tariff = $api->selectTariffForOrder(
            $crewGroupId,
            $this->getStorage()->get('lat'),
            $this->getStorage()->get('lon')
        );
        $priceResponse = $api->calcOrderPrice(
            $tariff->data->tariff_id,
            $options->getOrderParamsArray($this->getStorage()),
            $this->getStorage()
        );
        if (!isset($priceResponse->data->sum)) {
            throw new Exception('Calc price error from api. Code ' . $priceResponse->code ?? 'error code');
        }

        $this->getStorage()->save(['price' => $priceResponse->data->sum]);
        $this->getStorage()->save(['tariff_id' => $tariff->data->tariff_id]);
        $this->getStorage()->save(['crew_group_id' => $crewGroupId]);
    }

    public function getStorage(): Storage
    {
        if (!$this->storage) {
            throw new Exception('Try to get null user storage in Order class');
        } else {
            return $this->storage;
        }
    }

    public function setStorage(Storage $storage): void
    {
        $this->storage = $storage;
    }


}