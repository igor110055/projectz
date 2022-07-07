<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAllPriceColumnsToDailyPrices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->string('open')->nullable();
            $table->string('high')->nullable();
            $table->string('low')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('daily_prices', function (Blueprint $table) {
            //
        });
    }
}
