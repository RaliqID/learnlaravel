<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('banner')->nullable();

            $table->unsignedInteger('subscriber_count')->default(0);
            $table->unsignedInteger('post_count')->default(0);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_private')->default(false);
            $table->boolean('requires_approval')->default(false);

            $table->text('rules')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('subscriber_count');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
