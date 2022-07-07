<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalColumnsToDailyPrices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('daily_prices', function (Blueprint $table) {
            $table->string('priceChange')->nullable();
            $table->string('priceChangePercent')->nullable();
            $table->string('weightedAvgPrice')->nullable();
            $table->string('volume')->nullable();
            $table->string('quoteVolume')->nullable();
            $table->string('count')->nullable();
            $table->string('lastQty')->nullable();
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
