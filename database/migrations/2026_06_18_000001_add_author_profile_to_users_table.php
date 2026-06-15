<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('author_slug')->nullable()->unique()->after('name');
            $table->string('author_title')->nullable()->after('author_slug');
            $table->text('author_bio')->nullable()->after('author_title');
            $table->string('author_avatar')->nullable()->after('author_bio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['author_slug', 'author_title', 'author_bio', 'author_avatar']);
        });
    }
};
