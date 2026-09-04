<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Feed indexes (published + approved + visible) — EXPLAIN-proven: current queries do full scan
            // used by: PostRepository::getFilterStatus + sortByNewest / sortByTop / sortByFollowers
            $table->index(['status', 'is_approved', 'is_locked', 'published_at'], 'posts_idx_feed_newest');
            $table->index(['status', 'is_approved', 'is_locked', 'hot_score'], 'posts_idx_feed_trending');
            $table->index(['status', 'is_approved', 'is_locked', 'vote_score'], 'posts_idx_feed_popular');

            // cover common bookmark count sort
            $table->index(['status', 'is_approved', 'is_locked', 'comment_count'], 'posts_idx_feed_commented');
        });

        Schema::table('comments', function (Blueprint $table) {
            // comment list: parent null/non-deleted
            $table->index(['post_id', 'parent_id', 'is_deleted'], 'comments_idx_post_notdeleted');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_idx_feed_newest');
            $table->dropIndex('posts_idx_feed_trending');
            $table->dropIndex('posts_idx_feed_popular');
            $table->dropIndex('posts_idx_feed_commented');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_idx_post_notdeleted');
        });
    }
};
