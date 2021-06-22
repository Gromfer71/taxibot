<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddressHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('address_history', function(Blueprint $table) {
        	$table->increments('id');
        	$table->bigInteger('user_id');
        	$table->string('address')->nullable();
        	$table->string('lat')->nullable();
        	$table->string('lon')->nullable();

        	$table->timestamps();
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
