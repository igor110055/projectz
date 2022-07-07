<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->index(); 
            $table->string('timeframe');
            $table->string('open')->nullable();
            $table->string('high')->nullable();
            $table->string('low')->nullable();
            $table->string('close');
            $table->bigInteger('openTime')->nullable();
            $table->bigInteger('closeTime')->nullable();
            $table->string('assetVolume')->nullable();
            $table->string('baseVolume')->nullable();
            $table->integer('trades')->nullable();
            $table->string('assetBuyVolume')->nullable();
            $table->string('takerBuyVolume')->nullable();
            $table->tinyInteger('ignored')->default(0);
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
        Schema::dropIfExists('prices');
    }
}
