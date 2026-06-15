<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_number', 64)->unique();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->boolean('commission_credited')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['referrer_user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_orders');
    }
};
