<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWishlistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('MovieID');
            $table->unsignedBigInteger('UserID');
            $table->timestamps();

            // Foreign keys
            $table->foreign('MovieID')->references('id')->on('movies')->onDelete('cascade');
            $table->foreign('UserID')->references('id')->on('users')->onDelete('cascade');

            // Unique composite index
            $table->unique(['MovieID', 'UserID']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wishlists');
    }
}