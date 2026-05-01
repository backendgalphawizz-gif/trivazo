<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carpool_bookings', function (Blueprint $table) {
            $table->decimal('tax_amount', 10, 2)->default(0)->after('fare_total');
            $table->decimal('final_amount', 10, 2)->default(0)->after('tax_amount');
        });
    }

    public function down(): void
    {
        Schema::table('carpool_bookings', function (Blueprint $table) {
            $table->dropColumn(['tax_amount', 'final_amount']);
        });
    }
};
