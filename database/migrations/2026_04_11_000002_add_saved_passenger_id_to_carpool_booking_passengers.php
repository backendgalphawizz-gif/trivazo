<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carpool_booking_passengers', function (Blueprint $table) {
            $table->unsignedBigInteger('saved_passenger_id')->nullable()->after('booking_id')
                ->comment('FK to carpool_saved_passengers if added from master list');
        });
    }

    public function down(): void
    {
        Schema::table('carpool_booking_passengers', function (Blueprint $table) {
            $table->dropColumn('saved_passenger_id');
        });
    }
};
