<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('code')->nullable();
            $table->enum('type', ['coupon', 'discount'])->default('coupon');
            $table->enum('discount_type', ['percent', 'fixed', 'free_shipping', 'other'])->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->string('affiliate_url')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'is_featured']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
