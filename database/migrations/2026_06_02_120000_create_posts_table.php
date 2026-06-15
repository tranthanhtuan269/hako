<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('content');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('featured_image')->nullable();
            $table->string('author_name')->default('THUOC360 Team');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
