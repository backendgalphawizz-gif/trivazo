<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carpool_booking_passengers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('gender')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('carpool_bookings')->onDelete('cascade');
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carpool_booking_passengers');
    }
};
