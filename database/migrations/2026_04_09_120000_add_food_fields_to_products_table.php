<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_food')->default(false)->after('product_type');
            $table->string('food_type', 20)->nullable()->after('is_food');
            $table->unsignedInteger('prep_time')->nullable()->after('food_type');
            $table->time('available_from')->nullable()->after('prep_time');
            $table->time('available_to')->nullable()->after('available_from');
            $table->longText('food_addons')->nullable()->after('available_to');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'is_food',
                'food_type',
                'prep_time',
                'available_from',
                'available_to',
                'food_addons',
            ]);
        });
    }
};