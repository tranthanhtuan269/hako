<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unsignedInteger('view_count')->default(0)->after('is_active');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('view_count');
        });
    }
};
