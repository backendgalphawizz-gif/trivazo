<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carpool_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->unique(); // one per booking
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('passenger_id');
            $table->tinyInteger('rating'); // 1–5
            $table->text('comment')->nullable();
            $table->enum('status', ['published', 'hidden'])->default('published');
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('carpool_bookings')->onDelete('cascade');
            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carpool_reviews');
    }
};
