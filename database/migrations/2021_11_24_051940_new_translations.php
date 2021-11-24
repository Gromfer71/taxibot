<?php

use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Database\Migrations\Migration;

class NewTranslations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'payment with bonuses',
                'value' => 'Оплата бонусами',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'payment with cash',
                'value' => 'оплата наличкой',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'your address',
                'value' => 'Ваш адрес:',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'your route',
                'value' => 'Ваш маршрут:',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'comment',
                'value' => 'Комментарий',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'wishes',
                'value' => 'Пожелания',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'price change',
                'value' => 'Изменение цены',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'currency short',
                'value' => 'р.',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'make your choice',
                'value' => 'Выберите варианты ниже.',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'bonus balance and searching auto',
                'value' => 'Вау! У Вас есть :bonuses бонусов(а)! Ждём-с, я ищу Вам машину. ',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'your address is still',
                'value' => 'Ваш адрес по-прежнему',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'estimated order cost',
                'value' => 'Предварительная стоимость',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'searching auto first',
                'value' => 'И наконец-то я с радостью ищу Вам машину!',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'auto in way',
                'value' => 'Ваш автомобиль уже в пути',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'have a nice trip',
                'value' => 'Приятной поездки',
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
