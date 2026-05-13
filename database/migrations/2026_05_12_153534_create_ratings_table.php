<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('MovieID');
            $table->unsignedBigInteger('UserID');
            $table->integer('Rating');
            $table->text('Description')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('MovieID')->references('id')->on('movies')->onDelete('cascade');
            $table->foreign('UserID')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ratings');
    }
}