<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schema::create('carpools', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('rideOfferId')->nullable(); //foreign key 1
        //     $table->foreign('rideOfferId')->references('id')->on('ride_offers')->onDelete('cascade');
        //     ;

        //     $table->unsignedBigInteger('rideRequestId')->nullable(); //foreign key 2
        //     $table->foreign('rideRequestId')->references('id')->on('ride_requests')->onDelete('cascade');
        //     ;

        //     // $table->integer('numberOfPassenger'); lama
        //     $table->unsignedBigInteger('driverId');
        //     // $table->unsignedBigInteger('passengerId'); lama
        //     $table->string('status');
        //     $table->timestamps();
        // });

        Schema::create('carpools', function (Blueprint $table) {
            $table->id();
            $table->integer('driver_id')->nullable();
            $table->integer('requestor_id')->nullable();
            $table->string('start_location');
            $table->string('destination');
            $table->date('date');
            $table->time('time');
            $table->integer('available_seats')->nullable();
            $table->integer('number_of_passenger')->nullable();
            $table->text('description');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carpools');
    }
};
