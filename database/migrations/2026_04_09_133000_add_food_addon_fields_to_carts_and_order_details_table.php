<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->longText('food_addons')->nullable()->after('variations');
            $table->double('food_addon_total', 24, 8)->default(0)->after('tax');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->longText('food_addons')->nullable()->after('variation');
            $table->double('food_addon_total', 24, 8)->default(0)->after('tax');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn(['food_addons', 'food_addon_total']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['food_addons', 'food_addon_total']);
        });
    }
};
