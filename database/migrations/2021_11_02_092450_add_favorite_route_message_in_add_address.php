<?php

use Illuminate\Database\Migrations\Migration;

class AddFavoriteRouteMessageInAddAddress extends Migration
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
                'key' => 'give me end address without say to driver button',
                'value' => 'ЕДЕМ ЗА БОНУСЫ? – пишите, куда поедем или выбирайте адрес из истории!👇 Выбирайте варианты ниже.',
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
    }
}
