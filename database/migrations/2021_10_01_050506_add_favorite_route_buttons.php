<?php

use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Database\Migrations\Migration;

class AddFavoriteRouteButtons extends Migration
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
                'key' => 'added favorite route menu',
                'value' => 'Итак, ваш маршрут ',
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
