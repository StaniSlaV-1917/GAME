<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 / Batch A — In-app notifications.
 *
 * Стандартная Laravel notifications таблица.
 * Polymorphic notifiable_type/notifiable_id (у нас всегда User).
 * data — JSON-payload, который пишет каждый Notification class.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Дополнительный индекс для быстрого выбора непрочитанных
            // конкретного юзера (notifiable_id, read_at, created_at).
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notif_user_unread_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
