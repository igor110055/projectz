<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('symbol');
            $table->unsignedBigInteger('balanceId')->nullable();
            $table->unsignedBigInteger('pairId')->nullable();
            $table->unsignedBigInteger('remoteOrderId')->nullable();
            $table->string('type');
            $table->string('side');
            $table->string('price');
            $table->string('origQty');
            $table->string('icebergQty')->default('0.000');
            $table->string('executedQty')->default('0.000');
            $table->string('cumulativeQuoteQty')->default('0.000');
            $table->string('status')->default('NEW');
            $table->boolean('isWorking')->default(1);
            $table->string('timeInForce')->default('GTC');
            $table->float('stopPrice',8,6,true)->default(0.000000);
            $table->unsignedBigInteger('orderTimestamp');
            $table->unsignedBigInteger('orderUpdateTimestamp');
            $table->dateTime('orderDateTime');
            $table->unsignedBigInteger('userId');
            $table->unsignedBigInteger('exchanges')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
