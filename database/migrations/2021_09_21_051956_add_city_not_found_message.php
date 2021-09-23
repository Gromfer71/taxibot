<?php

use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Database\Migrations\Migration;

class AddCityNotFoundMessage extends Migration
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
                'key' => 'city not found',
                'value' => 'Город не найден!',
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
