<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->string('payment_method', 50)->nullable();
            $table->text('payment_details')->nullable();
            $table->text('member_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_payout_requests');
    }
};
