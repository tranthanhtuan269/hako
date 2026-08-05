<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_click_dailies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('stat_date');
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();

            $table->unique(['coupon_id', 'stat_date']);
            $table->index(['store_id', 'stat_date']);
            $table->index(['stat_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_click_dailies');
    }
};
