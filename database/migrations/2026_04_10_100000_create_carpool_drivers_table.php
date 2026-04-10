<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carpool_drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->unique()->nullable();
            $table->string('password');
            $table->string('device_token')->nullable();
            $table->string('fcm_token')->nullable();
            // Vehicle info
            $table->string('vehicle_type'); // car, van, suv, etc.
            $table->string('vehicle_number');
            $table->string('vehicle_model');
            $table->string('vehicle_color');
            $table->integer('vehicle_capacity')->default(4);
            $table->string('license_number');
            $table->string('license_doc')->nullable(); // file path
            $table->string('profile_image')->nullable();
            // Status & verification
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('is_verified')->default(false);
            $table->tinyInteger('is_online')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('total_completed_rides')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carpool_drivers');
    }
};
