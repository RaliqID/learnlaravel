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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title', 300);
            $table->text('content')->nullable();
            $table->string('url', 2048)->nullable();
            $table->enum('post_type', ['link', 'text', 'image'])->default('text');

            $table->string('image_url')->nullable();
            $table->string('thumbnail_url')->nullable();

            $table->integer('vote_score')->default(0);
            $table->unsignedInteger('upvote_count')->default(0);
            $table->unsignedInteger('downvote_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('bookmark_count')->default(0);

            $table->decimal('hot_score', 10, 4)->default(0);
            $table->decimal('controversy_score', 10, 4)->default(0);

            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_nsfw')->default(false);
            $table->boolean('is_approved')->default(true);

            $table->timestamp('featured_at')->nullable();
            $table->foreignId('featured_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('published_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('topic_id');
            $table->index('post_type');
            $table->index('vote_score');
            $table->index('hot_score');
            $table->index('published_at');
            $table->index('last_activity_at');
            $table->index(['topic_id', 'vote_score']);
            $table->index(['topic_id', 'hot_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
