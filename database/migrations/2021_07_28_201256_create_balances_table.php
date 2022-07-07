<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->string('token');
            $table->string('total');
            $table->string('onOrder')->default('0.00');
            $table->string('available')->default('0.00');
            $table->string('estimatedPrice')->nullable();
            $table->unsignedBigInteger('userId');
            $table->string('exchange')->default('binance');
            $table->json('orderIds')->nullable();
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
        Schema::dropIfExists('balances');
    }
}
