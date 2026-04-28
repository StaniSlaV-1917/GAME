<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.6 / Batch A — добавляем username для публичных профилей.
 *
 * - nullable: существующие юзеры не имеют username, могут добавить позже
 *   через Profile → Settings
 * - unique: для роута /u/:username
 * - lowercase: всегда нормализуется на уровне приложения
 * - 3..20 символов, [a-z0-9_.] (без @ и пробелов)
 *
 * Зарезервированные слова (admin, root, support, system, api, etc) —
 * проверяются на уровне валидации, не на уровне БД (легче менять список).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 20)
                  ->nullable()
                  ->after('fullname');
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
