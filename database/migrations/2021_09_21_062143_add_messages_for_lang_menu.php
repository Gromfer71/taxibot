<?php

use App\Models\LtmTranslations;
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
        LtmTranslations::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => 'choose lang',
                'value' => 'Выберите язык. По умолчанию используется русский. Если для какого-либо выражения или кнопки не будет перевода на Ваш язык, будет использоваться русский',
            ]
        );

        LtmTranslations::firstOrCreate(
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
