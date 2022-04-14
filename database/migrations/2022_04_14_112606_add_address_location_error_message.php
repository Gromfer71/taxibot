<?php

use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Database\Migrations\Migration;

class AddAddressLocationErrorMessage extends Migration
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
                'key' => 'address location error',
                'value' => 'По заданными координатам в радиусе не найден ни один адрес!',
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
