<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carpool_saved_passengers', function (Blueprint $table) {
            $table->unsignedTinyInteger('age')->nullable()->after('gender');
        });

        Schema::table('carpool_booking_passengers', function (Blueprint $table) {
            $table->unsignedTinyInteger('age')->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('carpool_saved_passengers', function (Blueprint $table) {
            $table->dropColumn('age');
        });

        Schema::table('carpool_booking_passengers', function (Blueprint $table) {
            $table->dropColumn('age');
        });
    }
};
