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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('carpoolId'); //foreign key 1
            $table->unsignedBigInteger('authorId'); //foreign key 2
            $table->foreign('carpoolId')->references('id')->on('carpools');
            $table->foreign('authorId')->references('id')->on('users');
            $table->integer('rating');
            $table->string('comment');
            // $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            // $table('feedback_carpoolid_foreign');
            // $table->dropForeign('feedback_authorid_foreign');

            $table->foreign('carpoolId')
                ->references('id')
                ->on('carpools')
                ->onDelete('cascade');
        });

        Schema::dropIfExists('feedback');
    }
};
