<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_excel_import_items', function (Blueprint $table) {
            $table->string('logo', 1000)->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_excel_import_items', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};
