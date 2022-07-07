<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('remoteId');
            $table->string('title')->index();
            $table->json('coins');
            $table->string('dateEvent');
            $table->string('dateCreated');
            $table->json('categories')->nullable();
            $table->json('categoryIds')->nullable();
            $table->json('tokenIds')->nullable();
            $table->tinyText('proof');
            $table->tinyText('source');
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
        Schema::dropIfExists('events');
    }
}
