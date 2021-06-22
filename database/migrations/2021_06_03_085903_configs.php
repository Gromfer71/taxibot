<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Configs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configs', function (Blueprint $table) {
            $table->string('name')->unique();
            $table->text('value');
        });

        \App\Models\Config::create(['name' => 'token', 'value' => config('botman.telegram.token', '1524067647:AAFYF0JS-K2-2cHM3Wd28gyqAi2xb0cURcs')]);
        \App\Models\Config::create(['name' => 'config_file', 'value' => config('app.config_file', 'https://sk-taxi.ru/tmfront/config.json')]);

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
