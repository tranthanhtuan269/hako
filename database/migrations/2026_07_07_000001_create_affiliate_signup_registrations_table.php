<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_signup_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('scan_project_id')->unique();
            $table->string('project')->nullable();
            $table->string('signup_link', 512);
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('registered_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_signup_registrations');
    }
};
