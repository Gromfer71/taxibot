<?php

use Illuminate\Database\Migrations\Migration;

class AddFavoriteRoutesButtons extends Migration
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
                'key' => 'create route',
                'value' => 'Создать маршрут',
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
