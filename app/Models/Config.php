<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

/**
 * App\Models\Config
 *
 * @property int $name
 * @property string $value
 * @method static \Illuminate\Database\Eloquent\Builder|Config newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Config newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Config query()
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Config whereValue($value)
 * @mixin \Eloquent
 */
class Config extends Model
{
    const MESSAGE_LABELS = [
        'welcome message' => 'Приветственное сообщение',
        'choose menu' => 'Сообщение в главном меню',
        'give me your address' => 'Сообщение при вводе адреса подачи',
        'give address again' => 'Сообщение с результатом поиска адреса',
        'give entrance' => 'Сообщение при уточнении подъезда',
        'address not found' => 'Сообщение пустом результате поиска адреса',
        'user address' => 'Сообщение с выбранным адресом подачи 1',
        'give me end address' => 'Сообщение с выбранным адресом подачи 2',
        'cost' => 'Сообщение с выбранным адресом подачи и назначения 1 Стоимость поездки',
        'currency' => 'Сообщение с выбранным адресом подачи и назначения 2 Валюта',
        'add additional address' => 'Сообщение при вводе дополнительных адресов',
        'I am searching auto now' => 'Сообщение о поиске машины',
        'cancel order' => 'Сообщение при отмене заказа',
        'zero bonus balance' => 'Сообщение при нулевом бонусном балансе',
        'pls wait we are searching auto now' => 'Сообщение при запросе состояния заказа',
        'current price' => 'Сообщение при изменении стоимости заказа',
        'change price up' => 'Сообщение увеличения цены',
        'change price down' => 'Сообщение уменьшения цены',
        'menu without end address' => 'Сообщение при заказе без конечного адреса',
        'menu without end address with route' => 'Сообщение при заказе с некорректными адресами',
        'wait for dispatcher' => 'Сообщение при запросе на звонок от диспетчера',
        'write comment or choose' => 'Сообщение при запросе комментария',
        'order info with comment' => 'Сообщение с информацией о поездке с комментарием',
        'order full info' => 'Сообщение с информацией о поездке с комментарием пожеланиями',
        'choose wish' => 'Сообщение при запросе пожеланий',
        'forget write house' => 'Сообщение при отсутствии номера дома',
        'choose city' => 'Сообщение при изменении города',
        'choose city without current city' => 'Сообщение при выборе города',
        'city has been changed' => 'Сообщение о изменении города',
        'price too low' => 'Сообщение с информацией о поездке',
        'price list' => 'Сообщение с информацией о ценах',
        'enter phone' => 'Сообщение при смене номера телефона',
        'phone changed' => 'Сообщение о получении номера телефона',
        'incorrect phone code' => 'Сообщение о неверном коде подтверждения из голосового сообщения',
        'enter phone first' => 'Сообщение с запросом номера телефона',
        'incorrect phone format' => 'Сообщение о неверном формате номера телефона',
        'enter sms code' => 'Сообщение с запросом ввода СМС кода',
        'enter call code' => 'Сообщение с запросом ввода продиктованного кода',
        'wrong sms code' => 'Сообщение о неверном коде подтверждения из СМС сообщения',
        'connect with driver' => 'Сообщение при запросе на звонок от водителя',
        'clean addresses history' => 'Сообщение о очистке истории адресов',
        'queue aborted by driver' => 'Сообщение о отказе от заказа водителем',
        'driver aborted from order' => 'Сообщение о снятии заказа с водителя',
        'thx for order' => 'Сообщение о успешно выполненном заказе',
        'dont come out' => 'Сообщение если пользователь не выходит',
        'aborted order' => 'Сообщение если заказ был кем-то отменен, но не пользователем',
        'bonuses menu' => 'Сообщение при переходе в раздел бонусов',
        'get bonus balance' => 'Сообщение с информацией о бонусном балансе',
        'work as driver' => 'Сообщение с информацией о работе водителем',
        'our site' => 'Сообщение с информацией о сайте',
        'our app' => 'Сообщение с информацией о приложении',

        'addres_naznachen_za_bonusi_punkt_29' => 'Сообщение о поиске машины за бонусы',
        'skazhu_voditelu_punkt_20' => 'Сообщение о поиске машины без конкретного адреса',
        'komment_i_pozhelanie_skazhu_voditelu_punkt_6' => 'Сообщение с информацией о заказе с комментарием пожеланиями без конкретного адреса',
        'select wishes' => 'Сообщение при выборе пожеланий',
        'komment_i_pozhelanie_skazhu_voditelu_punkt_43' => 'Сообщение о начале поиска машины при заказе с корректными адресами',
        'not found address dorabotka bota' => 'Сообщение когда бот не понял адрес',
        'ask for second address if first address incorrect' => 'Сообщение с запросом второго адреса если первый адрес был не корректный',
        'menu with first address from history incorrect' => 'Сообщение о адресе с некорректным первым адресом выбранным из истории',
        'menu without third address' => 'Сообщение о маршруте без доп. Адресов',
        'you are blocked' => 'Сообщение о блокировке',
        'addresses menu' => 'Сообщение в истории адресов',
        'address menu' => 'Сообщение перед удалением адреса из истории',
        'address has been deleted' => 'Сообщение после удаления адреса из истории',
        'problems with delete address' => 'Сообщение о ошибке удаления адреса из истории',
        'need map message while driver goes' => 'Сообщение с геолокацией водителя когда он в пути',
        'need map message when driver at place' => 'Сообщение с геолокацией водителя когда он на месте',
        'favorite addresses menu' => 'Сообщение в меню любимых адресов',
        'favorite address menu' => 'Сообщение при выборе любимого адреса',
        'give me your favorite address' => 'Сообщение перед вводом любимого адреса',
        'give favorite address again' => 'Сообщение на поиске в любимых адресах',
        'not found favorite address' => 'Сообщение о неверном адресе при поиске в любимых адресах',
        'forget write house in favorite address' => 'Сообщение с запросом номера дома при поиске в любимых адресах',
        'give entrance in favorite address' => 'Сообщение с запросом подъезда при поиске в любимых адресах',
        'get address name' => 'Сообщение с запросом псевдонима для найденного адреса в любимых адресах',
        'address name too long' => 'Сообщение о превышении лимита (32 символа) символов в псевдониме для адреса в любимых адресах',
        'favorite address' => 'Сообщение с добавленным любимым адресом',
        'auto info with time' => 'Сообщение с информацией о назначенной машине с временем подачи',
        'auto info without time' => 'Сообщение с информацией о назначенной машине без времени подачи',
        'auto waits for client' => 'Сообщение с информацией о ожидающей машине',
        'invalid message' => 'Сообщение на которое заменяется пустое сообщение если отправили стикер и т.д.',
        'program error message' => 'Сообщение в случае программной ошибки бота',
        'create order error' => 'Сообщение выходит в случае если запрос на создание заказа ответил ошибкой. Например если два заказа одинаковых',
        'user reseted' => 'Сброс пользователя админом',
        'error driver location' => 'Сообщение когда не удалось определить геолокацию водителя',
    ];

    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'name';

    public static function getToken()
    {
        return self::where('name', 'token')->first();
    }

    public function setValue($value)
    {
        $this->value = $value;
        $this->save();
    }



    public static function getTaxibotConfig()
    {
        return File::get(storage_path('app/taxi_config.json'));
    }

    public static function setTaxibotConfig($data)
    {
        file_put_contents(storage_path('app/taxi_config.json'), $data);
    }

    public static function setUpEmailsForErrorReports()
    {
        if(self::where('name', 'errorReportEmails')->get()->isEmpty()) {
            self::create([
                'name' => 'errorReportEmails',
                'value' => '',
            ]);
        }
    }

    public static function updateErrorReportEmails($emails)
    {
        $config = self::where('name', 'errorReportEmails')->first();
        if(!$config) {
            return false;
        }
        $config->value = $emails;
        $config->save();

    }

    public static function getErrorReportEmails()
    {
        return explode(',', self::find('errorReportEmails')->value);
    }



}
