<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v2.0 / Phase 1 / Batch C — социальный граф: подписки + уведомления.
 *
 * Phase 3 будет читать follows для "followers feed", Phase 4 будет писать
 * в notifications при новых комментах/follows/реакциях/DM.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── follows: односторонние подписки (Twitter-стиль) ────────────────
        // Никаких request/accept — клик и подписан. Взаимность опциональна.
        // См. project_v2_plan.md A1.
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')                     // кто подписывается
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('following_id')                    // на кого
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['follower_id', 'following_id']);
            $table->index('follower_id');                        // "на кого подписан"
            $table->index('following_id');                       // "кто подписан на меня"
        });

        // ── notifications: in-app уведомления ──────────────────────────────
        // Совместим с Laravel Notifications API: uuid pk, notifiable morph,
        // data jsonb. Это позволит использовать $user->notify(new Foo()),
        // $user->unreadNotifications, маркер read и т.п. без кастома.
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');                              // App\Notifications\NewComment etc.
            $table->morphs('notifiable');                        // notifiable_type + _id (юзер-получатель)
            $table->json('data');                                // полезная нагрузка: {actor_id, post_id, ...}
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->index(['notifiable_type', 'notifiable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('follows');
    }
};
