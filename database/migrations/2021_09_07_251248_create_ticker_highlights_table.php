<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTickerHighlightsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticker_highlights', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->index();
            $table->string('price');
            $table->string('eventType')->nullable()->index();
            $table->string('eventTime')->nullable()->index();
            $table->string('priceChanging');
            $table->string('baseVolumeChanging')->nullable();
            $table->string('quoteVolumeChanging')->nullable();
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
        Schema::dropIfExists('ticker_highlights');
    }
}
