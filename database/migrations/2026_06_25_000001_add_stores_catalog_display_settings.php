<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('show_on_stores')->default(true)->after('sort_order');
            $table->unsignedInteger('stores_list_sort_order')->default(0)->after('show_on_stores');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['show_on_stores', 'stores_list_sort_order']);
        });
    }
};
