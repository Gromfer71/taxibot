<?php

use App\Models\LtmTranslations;
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
