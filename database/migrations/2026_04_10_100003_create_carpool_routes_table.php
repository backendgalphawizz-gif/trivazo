<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carpool_routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            // Origin
            $table->string('origin_name');
            $table->decimal('origin_lat', 10, 7);
            $table->decimal('origin_lng', 10, 7);
            // Destination
            $table->string('destination_name');
            $table->decimal('destination_lat', 10, 7);
            $table->decimal('destination_lng', 10, 7);
            // Optional intermediate waypoints
            $table->json('waypoints')->nullable();
            // Timing
            $table->enum('ride_type', ['instant', 'scheduled'])->default('scheduled');
            $table->dateTime('departure_at');
            $table->integer('estimated_duration_min')->nullable();
            $table->decimal('estimated_distance_km', 8, 2)->nullable();
            // Seats & pricing
            $table->unsignedSmallInteger('total_seats');
            $table->unsignedSmallInteger('available_seats');
            $table->decimal('price_per_seat', 10, 2);
            $table->string('currency', 10)->default('USD');
            // Status & meta
            $table->enum('route_status', ['open', 'full', 'departed', 'completed', 'cancelled'])->default('open');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('carpool_drivers')->onDelete('cascade');
            $table->index(['route_status', 'departure_at']);
            $table->index(['driver_id', 'route_status']);
            $table->index(['origin_lat', 'origin_lng']);
            $table->index(['destination_lat', 'destination_lng']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carpool_routes');
    }
};
