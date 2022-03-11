<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGlobalMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('global_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('admin_phone');
            $table->string('platform');
            $table->string('recipients_type');
            $table->text('recipients');
            $table->text('message');
            $table->string('file')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('global_messages');
    }
}