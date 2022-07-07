<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('remoteId')->unique()->index();
            $table->string('name')->unique()->index();
            $table->unsignedBigInteger('rank')->default(0);
            $table->string('symbol')->unique()->index();
            $table->string('fullname')->nullable();
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
        Schema::dropIfExists('event_tokens');
    }
}
