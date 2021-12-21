<?php

use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Database\Migrations\Migration;

class CraeteAddressChangedMessages extends Migration
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
                'key' => 'order state changed',
                'value' => 'Диспетчер изменил некоторые параметры в вашем заказе',
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
