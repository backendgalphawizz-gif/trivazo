<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carpool_drivers', function (Blueprint $table) {
            $table->string('country_code', 12)->default('+91')->after('phone');
            $table->string('gender', 20)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('carpool_drivers', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'gender']);
        });
    }
};
