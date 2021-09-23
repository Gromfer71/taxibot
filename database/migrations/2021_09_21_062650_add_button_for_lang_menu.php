<?php


use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Database\Migrations\Migration;

class AddButtonForLangMenu extends Migration
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
                'key' => 'lang menu',
                'value' => 'Изменить язык',
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
