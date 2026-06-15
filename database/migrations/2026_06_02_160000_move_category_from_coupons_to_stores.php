<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'category_id')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->foreignId('category_id')
                    ->nullable()
                    ->after('description')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        DB::table('stores')->orderBy('id')->each(function ($store) {
            if ($store->category_id) {
                return;
            }

            $categoryId = DB::table('coupons')
                ->where('store_id', $store->id)
                ->whereNotNull('category_id')
                ->value('category_id');

            if ($categoryId) {
                DB::table('stores')->where('id', $store->id)->update(['category_id' => $categoryId]);
            }
        });

        if (! Schema::hasColumn('coupons', 'category_id')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->dropCouponCategoryOnSqlite();

            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }

    private function dropCouponCategoryOnSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        DB::statement('CREATE TABLE coupons_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            store_id INTEGER NOT NULL,
            title VARCHAR NOT NULL,
            slug VARCHAR NOT NULL,
            description TEXT,
            code VARCHAR,
            type VARCHAR NOT NULL DEFAULT \'coupon\',
            discount_type VARCHAR,
            discount_value NUMERIC,
            affiliate_url VARCHAR,
            starts_at DATETIME,
            expires_at DATETIME,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            click_count INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME,
            updated_at DATETIME,
            user_id INTEGER,
            FOREIGN KEY(store_id) REFERENCES stores(id) ON DELETE CASCADE
        )');

        DB::statement('INSERT INTO coupons_new (
            id, store_id, title, slug, description, code, type, discount_type,
            discount_value, affiliate_url, starts_at, expires_at, is_featured,
            is_active, click_count, created_at, updated_at, user_id
        ) SELECT
            id, store_id, title, slug, description, code, type, discount_type,
            discount_value, affiliate_url, starts_at, expires_at, is_featured,
            is_active, click_count, created_at, updated_at, user_id
        FROM coupons');

        DB::statement('DROP TABLE coupons');
        DB::statement('ALTER TABLE coupons_new RENAME TO coupons');
        DB::statement('CREATE UNIQUE INDEX coupons_slug_unique ON coupons (slug)');
        DB::statement('CREATE INDEX coupons_is_active_is_featured_index ON coupons (is_active, is_featured)');
        DB::statement('CREATE INDEX coupons_expires_at_index ON coupons (expires_at)');

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('coupons', 'category_id')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->after('store_id')->constrained()->nullOnDelete();
            });

            DB::table('coupons')
                ->join('stores', 'stores.id', '=', 'coupons.store_id')
                ->whereNotNull('stores.category_id')
                ->update(['coupons.category_id' => DB::raw('stores.category_id')]);
        }

        if (Schema::hasColumn('stores', 'category_id')) {
            Schema::table('stores', function (Blueprint $table) {
                if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                    $table->dropForeign(['category_id']);
                }

                $table->dropColumn('category_id');
            });
        }
    }
};
