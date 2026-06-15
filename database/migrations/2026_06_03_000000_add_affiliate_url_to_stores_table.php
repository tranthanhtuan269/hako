<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('affiliate_url', 500)->nullable()->after('website');
        });

        DB::table('stores')
            ->whereNotNull('website')
            ->whereNull('affiliate_url')
            ->update(['affiliate_url' => DB::raw('website')]);
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('affiliate_url');
        });
    }
};
