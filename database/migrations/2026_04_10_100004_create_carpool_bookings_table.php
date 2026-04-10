<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carpool_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('passenger_id'); // FK → customers.id
            // Pickup & drop for this passenger (may differ slightly from route origin/dest)
            $table->string('pickup_name');
            $table->decimal('pickup_lat', 10, 7);
            $table->decimal('pickup_lng', 10, 7);
            $table->string('drop_name');
            $table->decimal('drop_lat', 10, 7);
            $table->decimal('drop_lng', 10, 7);
            // Booking meta
            $table->unsignedSmallInteger('seat_count')->default(1);
            $table->string('booking_code')->unique();
            $table->enum('status', [
                'pending_payment',
                'confirmed',
                'departed',
                'completed',
                'cancelled',
            ])->default('pending_payment');
            // Finance
            $table->decimal('fare_total', 10, 2);
            $table->decimal('admin_commission_amount', 10, 2)->default(0.00);
            $table->decimal('driver_amount', 10, 2)->default(0.00);
            $table->string('payment_method')->nullable(); // wallet, online
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded'])->default('unpaid');
            $table->string('gateway_reference')->nullable();
            // Cancellation
            $table->string('cancelled_by')->nullable(); // passenger, driver, admin
            $table->text('cancellation_reason')->nullable();
            // Lifecycle timestamps
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('route_id')->references('id')->on('carpool_routes')->onDelete('cascade');
            $table->index(['route_id', 'status']);
            $table->index(['passenger_id', 'status']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carpool_bookings');
    }
};
