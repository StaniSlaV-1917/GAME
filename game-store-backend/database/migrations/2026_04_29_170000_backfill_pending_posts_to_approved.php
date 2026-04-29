<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Бэкфилл существующих постов с moderation_status='pending' → 'approved'.
 *
 * Премодерация была временно включена до Phase 8 (UI модерации не готов),
 * из-за чего посты обычных юзеров оставались невидимыми в ленте.
 * После выключения премодерации в PostController нужно проапрувить
 * уже созданные pending-посты, иначе фикс работает только для новых.
 *
 * Down — no-op: возвращать в pending бессмысленно (мы не помним, кто
 * был verified в момент создания).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('posts')
            ->where('moderation_status', 'pending')
            ->whereNull('deleted_at')
            ->update(['moderation_status' => 'approved']);
    }

    public function down(): void
    {
        // no-op
    }
};
