<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nav_links')) {
            Schema::create('nav_links', function (Blueprint $table) {
                $table->id();
                $table->string('type', 40);
                $table->string('label');
                $table->string('url', 1000);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('open_in_new_tab')->default(false);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('nav_links', 'open_in_new_tab')) {
            Schema::table('nav_links', function (Blueprint $table) {
                $table->boolean('open_in_new_tab')->default(false)->after('is_active');
            });
        }

        if (DB::table('nav_links')->where('type', 'header')->count() > 0) {
            return;
        }

        $now = now();

        DB::table('nav_links')->insert([
            ['type' => 'header', 'label' => 'Coupons', 'url' => '/coupons', 'sort_order' => 10, 'is_active' => true, 'open_in_new_tab' => false, 'starts_at' => null, 'ends_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'header', 'label' => 'Deals', 'url' => '/coupons?type=discount', 'sort_order' => 20, 'is_active' => true, 'open_in_new_tab' => false, 'starts_at' => null, 'ends_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'header', 'label' => 'Stores', 'url' => '/stores', 'sort_order' => 30, 'is_active' => true, 'open_in_new_tab' => false, 'starts_at' => null, 'ends_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'header', 'label' => 'Categories', 'url' => '/categories', 'sort_order' => 40, 'is_active' => true, 'open_in_new_tab' => false, 'starts_at' => null, 'ends_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'header', 'label' => 'Blog', 'url' => '/blog', 'sort_order' => 50, 'is_active' => true, 'open_in_new_tab' => false, 'starts_at' => null, 'ends_at' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('nav_links')->where('type', 'header')->delete();
    }
};
