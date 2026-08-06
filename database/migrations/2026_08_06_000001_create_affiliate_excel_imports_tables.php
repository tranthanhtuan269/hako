<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_excel_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_path')->nullable();
            $table->string('status', 32)->default('parsed');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_excel_import_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_excel_import_id')
                ->constrained('affiliate_excel_imports')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sheet_name', 120)->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->string('stt', 40)->nullable();
            $table->string('category_name')->nullable();
            $table->string('store_name');
            $table->string('website', 500)->nullable();
            $table->string('affiliate_url', 1000);
            $table->json('offers');
            $table->string('status', 32)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->unsignedInteger('coupons_added')->default(0);
            $table->boolean('was_existing_store')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_excel_import_items');
        Schema::dropIfExists('affiliate_excel_imports');
    }
};
