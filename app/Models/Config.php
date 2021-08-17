<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Config extends Model
{
    const MESSAGE_LABELS = [
        'welcome message' => 'Приветственное сообщение',
        'choose menu' => 'Сообщение в главном меню',
        'give me your address' => 'Просим первый адрес у пользователя',
        'give address again' => 'Предлагаем выбрать адрес из списка',
        'give entrance' => 'Укажите номер подъезда',
        'address not found' => 'Адрес не найден',
        'user address' => '"Ваш адрес"',
        'give me end address' => 'Укажите конечный адрес',
        'cost' => 'Стоимость поездки',
        'currency' => 'Валюта в родительном падеже (пр. "рублей")',
        'add additional address' => 'Добавление дополнительного адреса',
        'I am searching auto now' => 'Я ищу вам машину',
        'cancel order' => 'Сообщение после отмены заказа',
        'zero bonus balance' => 'Сообщение в случае если бонусный баланс равен 0',
        'pls wait we are searching auto now' => 'Сообщение при запроса информации о заказе',
        'addres_naznachen_za_bonusi_punkt_21' => 'Информация о заказе',
        'current price' => 'Показывает текущую стоимость поездки',
        'change price up' => 'Увеличить цену на :price рублей',
        'change price down' => 'Уменьшить цену на :price рублей',
        'menu without end address' => 'Меню если пользователь не выбрал конечный адрес',
        'menu without end address with route' => 'Меню если пользователь не выбрал конечный адрес но с проложенным маршрутом',
        'wait for dispatcher' => 'Запрос на звонок от диспетчера',
        'write comment or choose' => 'Просим написать комментарий или выбрать из списка',
        'order info with comment' => 'Информация о поездке с комментарием',
        'order full info' => 'Полная информация о поездке',
        'choose wish' => 'Выберите пожелания',
        'forget write house' => 'Пользователь забыл указать номер дома',
        'choose city' => 'Выберите город (при этом уже выбран какой-то, и мы показываем какой именно)',
        'choose city without current city' => 'Выберите город (при этом текущего города нет)',
        'city has been changed' => 'Город изменен на :city',
        'price too low' => 'Тоже что и полная информация о поездке',
        'price list' => 'Прейскурант цен',
        'enter phone' => 'Введите номер телефона',
        'phone changed' => 'Получили телефон от пользователя',
        'incorrect phone code' => 'Неправильный введенный код',
        'enter phone first' => 'Просим ввести номер телефона',
        'incorrect phone format' => 'Неверный фомрат номера телефона',
        'enter sms code' => 'Введите смс код',
        'enter call code' => 'Введите продиктованный от диспетчера код',
        'wrong sms code' => 'Неправильный смс код',
        'connect with driver' => 'Сообщение после запроса водителю о перезвоне',
        'clean addresses history' => 'Почистили историю адресов',
        'queue aborted by driver' => 'Водитель отказался от заказа',
        'driver aborted from order' => 'Сняли водителя с заказа',
        'thx for order' => 'Сообщение после успешного заказа',
        'not found address' => 'Адрес не найден',
        'dont come out' => 'Пользователь не выходит',
        'aborted order' => 'Заказ был отменен кем-то но не пользователем',
        'bonuses menu' => 'Меню бонусов',
        'get bonus balance' => 'Бонусный баланс',
        'work as driver' => 'Работа водителем',
        'our site' => 'Наш сайт',
        'our app' => 'Наше приложение',

        'addres_naznachen_za_bonusi_punkt_29' => 'Адрес назначен за бонусы (пункт 29 ТЗ)',
        'skazhu_voditelu_punkt_20' => 'Сообщение если адрес скажем водителю',
        'komment_i_pozhelanie_skazhu_voditelu_punkt_6' => 'Инфо о заказе если коммент и пожелание скажем водителю',
        'select wishes' => 'Выберите пожелания',
        'komment_i_pozhelanie_skazhu_voditelu_punkt_43' => '43 пункт ТЗ',
        'not found address dorabotka bota' => 'Бот не понял адрес',
        'ask for second address if first address incorrect' => 'Спрашиваем второй адрес если первый был неверный',
        'menu with first address from history incorrect' => 'menu with first address from history incorrect',
        'menu without third address' => 'menu without third address',
        'addresses menu' => 'Меню адресов',
        'address menu' => 'Меню выбранного адреса',
        'address has been deleted' => 'Сообщение после удаления адреса',
        'problems with delete address' => 'Сообщение если по каким-то причинам не удалось удалить адрес',
        'need map message while driver goes' => 'Геолокация водителя когда он едет',
        'need map message when driver at place' => 'Геолокация водителя когда он на месте',
        'favorite addresses menu' => 'Меню избранных адресов',
        'favorite address menu' => 'Меню выбранного избранного адреса',
        'give me your favorite address' => 'Сообщение перед вводом избранного адреса',
        'give favorite address again' => 'Когда ввели избранный адрес и бот предлагает выбрать предложенные варианты',
        'not found favorite address' => 'Не нашелся избранный адрес',
        'forget write house in favorite address' => 'Забыли написать номер дома при добавлении избранного адреса',
        'give entrance in favorite address' => 'Сообщение с просьбой указать подъезд при добавлении избранного адреса',
        'get address name' => 'Сообщение с просьбой указать псевдоним избранного адреса',
        'address name too long' => 'Сообщение если псевдоним введенного адреса превышает 32 символа',
        'favorite address' => 'Сообщение с избранным адресом',
        'auto info with time' => 'Информация о назначенной машине',
        'auto info without time' => 'Информация о назначенной машине без указания времени',
        'auto waits for client' => 'Вас ожидает авто',
        'invalid message' => 'Сообщение, на которое заменяется пустое сообщение если отправили стикер и т.д.',
        'program error message' => 'Сообщение в случае программной ошибки бота',
        'create order error' => 'Сообщение выходит в случае если запрос на создание заказа ответил ошибкой. Например если два заказа одинаковых',





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
        $config = json_decode(File::get(storage_path('app/taxi_config.json')));

        return $config;
    }

    public static function setTaxibotConfig($data)
    {
        file_put_contents(storage_path('app/taxi_config.json'), $data);
    }


}
