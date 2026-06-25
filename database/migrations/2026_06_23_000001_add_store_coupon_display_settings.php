<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedSmallInteger('store_coupon_limit')->default(16)->after('sort_order');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('show_on_store')->default(true)->after('is_active');
            $table->unsignedInteger('store_sort_order')->default(0)->after('show_on_store');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['show_on_store', 'store_sort_order']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('store_coupon_limit');
        });
    }
};
