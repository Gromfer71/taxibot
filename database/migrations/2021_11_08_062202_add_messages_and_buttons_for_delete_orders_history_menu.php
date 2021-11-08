<?php

use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Database\Migrations\Migration;

class AddMessagesAndButtonsForDeleteOrdersHistoryMenu extends Migration
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
                'key' => 'delete orders history menu',
                'value' => 'Меню удаления истории поездок',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'delete order',
                'value' => 'Подтвердите удаление поездки',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'delete all orders',
                'value' => 'Удалил все поездки',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'order has been deleted',
                'value' => 'Любой каприз!!! Поездку из истории я удалил, продолжаем)',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'problems with delete order',
                'value' => 'Проблемы с удалением поездки!',
            ]
        );
        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'buttons',
                'key' => 'clear orders history menu',
                'value' => 'Сносим историю поездок',
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
