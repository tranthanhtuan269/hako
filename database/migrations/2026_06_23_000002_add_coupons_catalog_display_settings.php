<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('show_on_coupons')->default(true)->after('store_sort_order');
            $table->unsignedInteger('coupons_sort_order')->default(0)->after('show_on_coupons');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['show_on_coupons', 'coupons_sort_order']);
        });
    }
};
