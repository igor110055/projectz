<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderLoggersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_loggers', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->index();
            $table->string('side')->index();
            $table->string('price');
            $table->string('stopPrice')->nullable();
            $table->string('stopLimitPrice')->nullable();
            $table->string('quantity');
            $table->string('cummulativeQuoteQty')->nullable();
            $table->string('status')->nullable();
            $table->string('type')->default('NEW');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('order_loggers');
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
        Schema::dropIfExists('order_loggers');
    }
}
