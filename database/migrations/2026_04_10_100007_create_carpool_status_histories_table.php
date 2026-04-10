<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carpool_status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('target_type'); // route, booking, withdrawal
            $table->unsignedBigInteger('target_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->string('actor_type')->nullable(); // driver, passenger, admin, system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carpool_status_histories');
    }
};
