<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OrdersHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders_history', function (Blueprint $table) {
        	$table->increments('id');
        	$table->bigInteger('user_id');
			$table->text('address')->nullable();
			$table->integer('price')->nullable();
			$table->integer('changed_price')->nullable();
			$table->text('comment')->nullable();
			$table->text('wishes')->nullable();
			$table->integer('relevance')->nullable();
			$table->text('state')->nullable();
			$table->text('fail_reason')->nullable();
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
