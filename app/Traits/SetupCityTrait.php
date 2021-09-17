<?php

namespace App\Traits;

use App\Services\Options;
use Illuminate\Support\Facades\Log;

/**
 * Трейт отвечает за установку и смену города пользователя
 */
trait SetupCityTrait
{
    /**
     * Возвращает коллекцию имён городов взятых из конфига
     *
     * @return array
     */
    public function getCitiesArray(): array
    {
        $options = new Options();
        $cities = collect($options->getCities());
        $cities = $cities->map(function ($city) {
           return $city->name;
        });

        return $cities->toArray();
    }

    /**
     * Проверяет, является ли введенный пользователем текст городом из списка, взятого из конфига
     */
    public function isUserInputIsCity($text): bool
    {
        return in_array($text, $this->getCitiesArray());
    }



}