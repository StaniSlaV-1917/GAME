<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.6 / Batch B — поля для модерации пользователей админами.
 *
 * - banned_at:    timestamp когда юзера забанили (NULL = активен)
 * - ban_reason:   причина бана (отображается юзеру при попытке логина)
 * - frozen_at:    timestamp заморозки (мягче бана — заморозка временная,
 *                 юзер видит сайт но не может постить/комментить/реакции)
 * - freeze_reason: причина заморозки
 *
 * Soft delete (deleted_at) уже реализуется через role/destroy в существующем
 * AdminUserController — оставляем как было.
 *
 * Триггер для логина: AuthController::login проверяет banned_at →
 * если NOT NULL, возвращает 403 с сообщением «Аккаунт заблокирован: {reason}».
 * Frozen-юзеры могут логиниться, но при попытке создать пост/коммент бэк
 * вернёт 403 (проверка в FormRequest или middleware).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('role');
            $table->text('ban_reason')->nullable()->after('banned_at');
            $table->timestamp('frozen_at')->nullable()->after('ban_reason');
            $table->text('freeze_reason')->nullable()->after('frozen_at');

            // Индекс для админ-фильтра «забаненные/замороженные»
            $table->index('banned_at');
            $table->index('frozen_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['banned_at']);
            $table->dropIndex(['frozen_at']);
            $table->dropColumn(['banned_at', 'ban_reason', 'frozen_at', 'freeze_reason']);
        });
    }
};
