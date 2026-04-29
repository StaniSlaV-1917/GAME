<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 / Batch A — таблица подписок follower → followed.
 *
 * Семантика:
 *   follower_id  — кто подписался
 *   followed_id  — на кого подписался
 *
 * Unique constraint защищает от дубликатов (один юзер не может
 * подписаться на одного и того же дважды).
 *
 * Денормализованные счётчики:
 *   users.followers_count — сколько у меня подписчиков
 *   users.following_count — на скольких я подписан
 *
 * Поддерживаются триггерами на insert/delete (в FollowController
 * через DB::transaction → User::increment/decrement).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('followed_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['follower_id', 'followed_id'], 'follows_pair_unique');
            $table->index('follower_id');
            $table->index('followed_id');
        });

        // Денормализованные счётчики на users
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('followers_count')->default(0)->after('frozen_at');
            $table->unsignedInteger('following_count')->default(0)->after('followers_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['followers_count', 'following_count']);
        });
        Schema::dropIfExists('follows');
    }
};
