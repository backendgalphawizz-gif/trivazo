<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carpool_driver_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id')->unique();
            $table->decimal('pending_balance', 12, 2)->default(0.00);   // in-ride, not yet settled
            $table->decimal('available_balance', 12, 2)->default(0.00); // settled, withdrawable
            $table->decimal('total_earned', 12, 2)->default(0.00);
            $table->decimal('total_withdrawn', 12, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('carpool_drivers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carpool_driver_wallets');
    }
};
