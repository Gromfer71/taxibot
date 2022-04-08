<?php

use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGetAddressByLocation extends Migration
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
                'group' => 'buttons',
                'key' => 'order by location',
                'value' => 'Заказать по геолокации',
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
