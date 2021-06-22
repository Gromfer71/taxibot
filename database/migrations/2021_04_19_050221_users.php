<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Users extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
        	$table->bigIncrements('id');
        	$table->string('username')->nullable();
        	$table->string('firstname')->nullable();
        	$table->string('lastname')->nullable();
        	$table->text('userinfo')->nullable();
        	$table->string('city')->nullable();
        	$table->string('phone')->nullable();
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
