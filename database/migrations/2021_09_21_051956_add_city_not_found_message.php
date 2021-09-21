<?php

use App\Models\LtmTranslations;
use App\Services\Bot\ButtonsStructure;
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
        LtmTranslations::firstOrCreate(
            [
                'locale' => 'ru',
                'group' => 'messages',
                'key' => ButtonsStructure::CITY_NOT_FOUND,
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
