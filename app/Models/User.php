<?php

namespace App\Models;

use App\Services\BonusesApi;
use Barryvdh\TranslationManager\Models\LangPackage;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public $timestamps = false;
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
        $this->save();
    }

    /**
     * Запрашивает бонусный баланс пользователя
     *
     * @return mixed
     */
    public function getBonusBalance()
    {
        return BonusesApi::getClientInfo($this->server_id)->data->bonus_balance;
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
        return $this->hasMany(AddressHistory::class, 'user_id', 'id')->orderBy('updated_at', 'desc')->take(25);
    }

    /**
     * Сохраненные избранные адреса пользователя
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Query\Builder
     */
    public function favoriteAddresses()
    {
        return $this->hasMany(FavoriteAddress::class, 'user_id', 'id')->orderBy('created_at', 'desc')->take(25);
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


}
