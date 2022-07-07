<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTickersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickers', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->index();
            $table->string('open')->nullable();
            $table->string('close')->nullable();
            $table->string('low')->nullable();
            $table->string('high')->nullable();
            $table->string('volume')->nullable();
            $table->string('assetVolume')->nullable();
            $table->string('baseVolume')->nullable();
            $table->string('assetBuyVolume')->nullable();
            $table->string('takerBuyVolume')->nullable();
            $table->string('code')->nullable();
            $table->bigInteger('openTime')->index()->nullable();
            $table->unsignedBigInteger('closeTime')->index()->nullable();
            $table->unsignedBigInteger('pairId')->index()->nullable();
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
        Schema::dropIfExists('tickers');
    }
}
