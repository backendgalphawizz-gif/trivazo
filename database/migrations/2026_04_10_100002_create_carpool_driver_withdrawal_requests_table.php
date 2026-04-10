<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carpool_driver_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->json('account_details'); // bank/mobile money details
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('carpool_drivers')->onDelete('cascade');
            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carpool_driver_withdrawal_requests');
    }
};
