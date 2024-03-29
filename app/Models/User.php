<?php

namespace App\Models;

use App\Services\Address;
use App\Services\BonusesApi;
use Barryvdh\TranslationManager\Models\LangPackage;
use BotMan\BotMan\Storages\Storage;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string|null $username
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $userinfo
 * @property string|null $city
 * @property string|null $phone
 * @property string|null $server_id
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereCity($value)
 * @method static Builder|User whereFirstname($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereLastname($value)
 * @method static Builder|User wherePhone($value)
 * @method static Builder|User whereUserinfo($value)
 * @method static Builder|User whereUsername($value)
 * @mixin \Eloquent
 * @property int $isBlocked
 * @property int|null $telegram_id
 * @property int|null $vk_id
 * @property int $need_call
 * @property int $should_reset
 * @property int $lang_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AddressHistory[] $addresses
 * @property-read int|null $addresses_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FavoriteAddress[] $favoriteAddresses
 * @property-read int|null $favorite_addresses_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrderHistory[] $orders
 * @property-read int|null $orders_count
 * @method static Builder|User whereIsBlocked($value)
 * @method static Builder|User whereLangId($value)
 * @method static Builder|User whereNeedCall($value)
 * @method static Builder|User whereServerId($value)
 * @method static Builder|User whereShouldReset($value)
 * @method static Builder|User whereTelegramId($value)
 * @method static Builder|User whereVkId($value)
 */
class User extends Model
{
    public const UPDATED_AT = null;
    protected $table = 'users';
    protected $guarded = [];

    /**
     * Переопределение базового метода, потому что в боте пользователь идентифицируется по id своих платформ,
     * и искать нужно по ним
     *
     * @param $id
     * @return \App\Models\User|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public static function find($id)
    {
        return self::where('vk_id', $id)->orWhere('telegram_id', $id)->first();
    }

    /**
     * Обновление телефона пользователя с сохранением
     *
     * @param $phone
     */
    public function updatePhone($phone)
    {
        $this->phone = $phone;
        $this->server_id = null;
        $this->registerServerId();
        $this->save();
    }

    /**
     * Запрашивает бонусный баланс пользователя
     *
     * @return mixed
     */
    public function getBonusBalance()
    {

        $this->registerServerId();

        $response = BonusesApi::getClientInfo($this->server_id);
        if(Arr::get($response, 'code') === 0) {
            return Arr::get($response, 'data.bonus_balance', 0);
        }

        return 0;
    }

    /**
     * История заказов пользователя
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(OrderHistory::class, 'user_id', 'id')->orderBy('created_at', 'desc');
    }

    /**
     * История адресов пользователя
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Query\Builder
     */
    public function addresses()
    {
        return $this->hasMany(AddressHistory::class, 'user_id', 'id')->orderBy('updated_at', 'desc')->take(24);
    }

    /**
     * Сохраненные избранные адреса пользователя
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Query\Builder
     */
    public function favoriteAddresses()
    {
        return $this->hasMany(FavoriteAddress::class, 'user_id', 'id')->orderBy('created_at', 'desc')->take(24);
    }

    /**
     * Блокирует (банит) пользователя
     */
    public function block()
    {
        $this->isBlocked = true;
        $this->save();
    }

    /**
     * Разблокирует пользователя
     */
    public function unblock()
    {
        $this->isBlocked = false;
        $this->save();
    }

    /**
     * Установка id платформы с которой зашел пользователь. Проверка должна происходить каждый раз
     *
     * @param $bot
     */
    public function setPlatformId($bot)
    {
        if (get_class($bot->getDriver()) == VkCommunityCallbackDriver::class) {
            $this->vk_id = $bot->getUser()->getId();
        } elseif (get_class($bot->getDriver()) == TelegramDriver::class) {
            $this->telegram_id = $bot->getUser()->getId();
        }
        $this->save();
    }

    /**
     * Включает необходимость связаться с диспетчером. Звонок обрабатывается в крон команде
     */
    public function setUserNeedDispatcher()
    {
        $this->need_call = 1;
        $this->save();
    }

    /**
     * Установка языка по умолчанию если он null. По умолчанию берется ru, но если его нет то первый попавшийся
     */
    public function setDefaultLang()
    {
        $defaultPackage = LangPackage::where('code', config('app.locale', 'ru'))->first();
        if (!$defaultPackage) {
            $this->lang_id = LangPackage::all()->first()->id ?? null;
        } else {
            $this->lang_id = $defaultPackage->id;
        }
        $this->save();
    }

    /**
     * Обновляет город пользователя
     *
     * @param $newCity
     */
    public function updateCity($newCity)
    {
        $this->city = $newCity;
        $this->save();
    }

    /**
     * Поиск адреса пользователя по названию
     *
     * @param $address
     * @return \App\Models\AddressHistory|null
     */
    public function getUserAddressByName($address): ?AddressHistory
    {
        return $this->addresses->where('address', $address)->first();
    }

    public function getUserOrderByName($address): ?AddressHistory
    {
        return $this->addresses->where('address', $address)->first();
    }

    /**
     * Регистрирует server_id. Поле используется для бонусов
     *
     * @return void
     */
    public function registerServerId()
    {
        $response = BonusesApi::analyzePhone($this->phone);
        if ($response->code !== BonusesApi::USER_NOT_FOUND) {
            $this->server_id = $response->data->id;
            $this->save();
        }
    }

    /**
     * Переключение языка пользователя
     *
     * @param $packageId
     */
    public function switchLang($packageId)
    {
        $this->lang_id = $packageId;
        $this->save();
    }

    /**
     * Языковой пакет пользователя
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function lang(): HasOne
    {
        return $this->hasOne(LangPackage::class, 'id', 'lang_id');
    }

    public function getOrderInfoByImplodedAddress($address, Storage $storage)
    {
        if (!$address) {
            return null;
        }
        $storage->save(['crew_group_id' => collect($storage->get('crews'))->get($address)]);
        \Illuminate\Support\Facades\Log::debug($storage->get('crew_group_id'));
        $address = Address::removeEllipsisFromAddressIfExists($address);
        $addresses = $this->orders->map(function ($item) {
            return collect(json_decode($item->address));
        });
        $addressInfo = $addresses->transform(function ($item) {
            $item['address'] = implode(' – ', $item['address']);
            return $item;
        });

        if ($addressInfo->where('address', $address)->isEmpty()) {
            $addressInfo = $addressInfo->filter(function ($item) use ($address) {
                //return false !== stristr($item['address'], $address);
                \Illuminate\Support\Facades\Log::debug($item['address']);
                \Illuminate\Support\Facades\Log::debug($address);
                return $item['address'] == $address;
            })->first();
        } else {
            $addressInfo = $addressInfo->where('address', $address)->first();
        }
        if (!$addressInfo) {
            return null;
        }

        $addressInfo['address'] = explode(' – ', $addressInfo['address']);

        if (count($addressInfo['address']) != count($addressInfo['lat']) || count($addressInfo['address']) != count(
                $addressInfo['lon']
            )) {
            throw new Exception('Количество адресов маршрута и координат отличается');
        }


        return $addressInfo;
    }

    public function getOrderByImplodedRoute($route)
    {
        $route = Address::removeEllipsisFromAddressIfExists($route);
    }

    public function favoriteRoutes()
    {
        return $this->hasMany(FavoriteRoute::class, 'user_id', 'id')->orderByDesc('id');
    }


}
