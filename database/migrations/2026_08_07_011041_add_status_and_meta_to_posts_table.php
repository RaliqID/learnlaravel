<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->after('is_approved');
            $table->string('slug')->nullable()->after('title');
            $table->text('content_html')->nullable()->after('content');
            $table->string('canonical_url', 2048)->nullable()->after('url');
            $table->string('meta_title')->nullable()->after('canonical_url');
            $table->string('meta_description')->nullable()->after('meta_title');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->unique('slug');
        });

        DB::table('posts')->whereNotNull('published_at')->update(['status' => 'published']);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique('posts_slug_unique');
            $table->dropColumn(['status', 'slug', 'content_html', 'canonical_url', 'meta_title', 'meta_description']);
        });
    }
};