<?php

use Illuminate\Database\Migrations\Migration;

class AddAddressesRadiusToConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\Config::create(['name' => 'addresses_search_radius', 'value' => '100']);
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
