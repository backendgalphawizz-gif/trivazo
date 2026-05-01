<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carpool_drivers', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_category_id')->nullable()->after('vehicle_capacity');
            $table->foreign('vehicle_category_id')
                ->references('id')
                ->on('carpool_vehicle_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('carpool_drivers', function (Blueprint $table) {
            $table->dropForeign(['vehicle_category_id']);
            $table->dropColumn('vehicle_category_id');
        });
    }
};
