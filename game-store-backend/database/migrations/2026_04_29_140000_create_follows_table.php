<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 / Batch A — добавляем счётчики подписок в users.
 *
 * Таблица follows УЖЕ создана Phase 1 миграцией
 * (2026_04_27_150001_create_social_graph_tables.php) со столбцами
 *   follower_id  — кто подписался
 *   following_id — на кого подписался   ← заметь имя
 *
 * Эта миграция только добавляет денормализованные счётчики на users.
 * (Изначально я ошибочно добавил Schema::create — это привело к
 *  silent fail. Текущая версия — идемпотентная: только колонки.)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Безопасно добавляем только если колонок ещё нет
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'followers_count')) {
                $table->unsignedInteger('followers_count')->default(0);
            }
            if (!Schema::hasColumn('users', 'following_count')) {
                $table->unsignedInteger('following_count')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['followers_count', 'following_count']);
        });
    }
};
