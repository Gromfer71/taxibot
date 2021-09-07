<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LangTranslations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lang_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('package_id');
            $table->integer('key_id');
            $table->text('translate');

            $table->foreign('package_id')->references('id')->on('lang_packages')->onDelete('cascade');
            $table->foreign('key_id')->references('id')->on('lang_keys')->onDelete('cascade');
        });
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
