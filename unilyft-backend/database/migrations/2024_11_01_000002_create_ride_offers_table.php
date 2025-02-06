<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ride_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId')->nullable(false); //foreign key
            $table->foreign('userId')->references('id')->on('users');
            $table->string('startLocation');
            $table->string('destination');
            $table->date('date');
            $table->time('time');
            $table->integer('availableSeats');
            $table->string('description');
            $table->string('status')->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ride_offers');
    }
};
