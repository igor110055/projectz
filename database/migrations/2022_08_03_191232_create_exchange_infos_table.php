<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_infos', function (Blueprint $table) {
            $table->id();
            $table->string("symbol");
            $table->string('status');
            $table->string('baseAsset');
            $table->string('baseAssetPrecision');
            $table->string('quoteAsset');
            $table->string('quotePrecision');
            $table->string('quoteAssetPrecision');
            $table->string('baseCommissionPrecision');
            $table->string('quoteCommissionPrecision');
            $table->json('orderTypes');
            $table->boolean("icebergAllowed");
            $table->boolean("ocoAllowed");
            $table->boolean("quoteOrderQtyMarketAllowed");
            $table->boolean("isSpotTradingAllowed");
            $table->boolean("isMarginTradingAllowed");
            $table->json('filters');
            $table->json('permissions');
            $table->string('exchange')->default('Binance');
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
        Schema::dropIfExists('exchange_infos');
    }
}

