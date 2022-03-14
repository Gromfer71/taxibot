<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeFileNameNullable extends Migration
{
    public function up()
    {
        Schema::table('global_messages', function (Blueprint $table) {
            $table->string('file_name')->nullable()->change();
        });
    }

    public function down()
    {
    }
}