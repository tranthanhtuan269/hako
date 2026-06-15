<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_visit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('landing_url', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['referrer_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_visit_logs');
    }
};
