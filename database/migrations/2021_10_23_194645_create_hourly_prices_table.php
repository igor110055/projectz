<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHourlyPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hourly_prices', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->index();
            $table->float('price');
            $table->float('open')->nullable();
            $table->float('high')->nullable();
            $table->float('low')->nullable();
            $table->string('count')->nullable();
            $table->string('timestamp')->nullable();
            $table->float('volume')->nullable();

            $table->unsignedBigInteger('pair_id')->nullable();
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
        Schema::dropIfExists('hourly_prices');
    }
}
