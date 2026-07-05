<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('is_pinned_home')->default(false)->after('is_active');
            $table->unsignedSmallInteger('home_pin_sort_order')->default(0)->after('is_pinned_home');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('is_pinned_home')->default(false)->after('is_published');
            $table->unsignedSmallInteger('home_pin_sort_order')->default(0)->after('is_pinned_home');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['is_pinned_home', 'home_pin_sort_order']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['is_pinned_home', 'home_pin_sort_order']);
        });
    }
};
