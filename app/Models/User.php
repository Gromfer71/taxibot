<?php

namespace App\Models;

use App\Services\Translator;
use Barryvdh\TranslationManager\Models\LangPackage;
use BotMan\Drivers\Telegram\TelegramDriver;
use BotMan\Drivers\VK\VkCommunityCallbackDriver;
use Illuminate\Database\Eloquent\Model;
use App\Services\BonusesApi;
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
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFirstname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserinfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUsername($value)
 * @mixin \Eloquent
 */
class User extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;

	public function updatePhone($phone) {
		$this->phone = $phone;
        $this->server_id = null;
		$this->save();
		$this->registerServerId();
	}
    public function registerServerId()
    {
        if($this->server_id || !$this->phone) {
            return;
        }
        $response = BonusesApi::analyzePhone($this->phone);
        if($response->code == BonusesApi::USER_NOT_FOUND) {
            return false;
        } else {
            $this->server_id = $response->data->id;
            $this->save();
        }
    }

    public function getBonusBalance()
    {
        if(!$this->server_id) {
          $this->registerServerId();
        }
            return BonusesApi::getClientInfo($this->server_id)->data->bonus_balance;


    }

    public function orders()
    {
        return $this->hasMany(OrderHistory::class, 'user_id', 'id')->orderBy('created_at', 'desc');
    }

    public function addresses()
    {
        return $this->hasMany(AddressHistory::class, 'user_id', 'id')->orderBy('updated_at', 'desc')->take(25);
    }

    public function favoriteAddresses()
    {
        return $this->hasMany(FavoriteAddress::class, 'user_id', 'id')->orderBy('created_at', 'desc')->take(25);
    }

    public function block()
    {
        $this->isBlocked = true;
        $this->save();
    }

    public function unblock()
    {
        $this->isBlocked = false;
        $this->save();
    }

    public static function find($id)
    {
        return self::where('vk_id', $id)->orWhere('telegram_id', $id)->first();
    }

    public function setPlatformId($bot)
    {
        if(get_class($bot->getDriver()) == VkCommunityCallbackDriver::class) {
            $this->vk_id = $bot->getUser()->getId();
        } elseif(get_class($bot->getDriver()) == TelegramDriver::class) {
            $this->telegram_id = $bot->getUser()->getId();
        }
    }

    public function setUserNeedDispatcher()
    {
        $this->need_call =  1;
        $this->save();
    }

    public function setDefaultLang()
    {
        $defaultPackage = LangPackage::where('code', config('app.locale', 'ru'))->first();
        if(!$defaultPackage) {
            $this->lang_id = LangPackage::all()->first()->id ?? null;
        } else {
            $this->lang_id = $defaultPackage->id;
        }
        $this->save();
    }

}
