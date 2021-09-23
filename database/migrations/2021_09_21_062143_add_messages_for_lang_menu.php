<?php

use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Database\Migrations\Migration;

class AddMessagesForLangMenu extends Migration
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
                'key' => 'choose lang',
                'value' => 'Выберите язык. По умолчанию используется русский. Если для какого-либо выражения или кнопки не будет перевода на Ваш язык, будет использоваться русский',
            ]
        );

        Translation::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'lang not found',
                'value' => 'Язык не найден!',
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
