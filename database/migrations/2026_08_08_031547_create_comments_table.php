<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            
            $table->text('content');
            $table->integer('vote_score')->default(0);
            
            $table->integer('upvote_count')->default(0);
            $table->integer('downvote_count')->default(0);
            $table->integer('reply_count')->default(0);
            
            $table->integer('depth')->default(0);
            
            $table->boolean('is_deleted')->default(false);
            
            $table->timestamp('edited_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['post_id', 'parent_id']);
            $table->index(['post_id', 'created_at']);
            $table->index('user_id');
            $table->index('parent_id');
            $table->index('vote_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
