<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carpool_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('payer_id');  // customer id
            $table->unsignedBigInteger('driver_id');
            $table->enum('transaction_type', [
                'booking_payment',
                'commission',
                'driver_credit',
                'refund',
                'withdrawal',
            ]);
            $table->decimal('amount', 12, 2);
            $table->decimal('admin_commission', 10, 2)->default(0.00);
            $table->decimal('driver_amount', 10, 2)->default(0.00);
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'disburse'])->default('pending');
            $table->string('gateway_reference')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('carpool_bookings')->onDelete('cascade');
            $table->index(['driver_id', 'transaction_type']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carpool_transactions');
    }
};
