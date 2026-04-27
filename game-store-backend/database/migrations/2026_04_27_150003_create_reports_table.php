<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v2.0 / Phase 1 / Batch C — жалобы (модерация).
 *
 * Полиморфный target: можно жаловаться на post / comment / message / user.
 * Phase 8 строит UI очереди модерации (`/admin/moderation/reports`).
 *
 * См. project_v2_plan.md C2 (антиспам) и Phase 8.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->morphs('target');                            // target_type + target_id + index
            $table->enum('reason', [
                'spam',
                'harassment',
                'hate',
                'illegal',
                'sexual',
                'misinformation',
                'other',
            ]);
            $table->text('description')->nullable();             // дополнительное пояснение от reporter
            $table->enum('status', ['open', 'in_review', 'resolved', 'dismissed'])
                  ->default('open');
            $table->foreignId('reviewed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution_note')->nullable();         // что сделал модератор
            $table->timestamps();

            $table->index(['target_type', 'target_id', 'status']); // "сколько open-жалоб на этот пост"
            $table->index(['status', 'created_at']);             // очередь модерации
            $table->index('reporter_id');
            // Не делаю unique(target_type, target_id, reporter_id) — один юзер
            // может оставить несколько жалоб на один объект с разными reason'ами,
            // если это разные нарушения. Дедупликация на стороне UI.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
