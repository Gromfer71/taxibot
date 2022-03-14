<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileNameToGlobalMessages extends Migration
{
    public function up()
    {
        Schema::table('global_messages', function (Blueprint $table) {
            $table->string('file_name')->after('file');
        });
    }

    public function down()
    {
        Schema::table('global_messages', function (Blueprint $table) {
            $table->dropColumn('global_messages');
        });
    }
}